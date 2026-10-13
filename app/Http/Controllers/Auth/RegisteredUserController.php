<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\ProfilePhoto;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $photo = ProfilePhoto::store($request->file('photo'), $data['role']);

        try {
            $user = DB::transaction(function () use ($data, $photo) {
                $user = User::create([
                    'name' => $data['name'],
                    'slug' => User::uniqueSlug($data['name']),
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'phone' => $data['phone'],
                    'photo' => $photo,
                    'gender' => $data['gender'] ?? null,
                    'role' => $data['role'],
                    'is_active' => true,
                ]);

                if ($user->role === 'patient') {
                    $user->patient_profile()->create([
                        'age' => $data['age'],
                    ]);
                }

                if ($user->role === 'doctor') {
                    $user->doctor()->create([
                        'approval_status' => 'pending',
                    ]);
                }

                return $user;
            });
        } catch (\Throwable $exception) {
            ProfilePhoto::delete($photo);

            throw $exception;
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
