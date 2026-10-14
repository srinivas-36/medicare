<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorReceptionistRequest;
use App\Models\Doctor;
use App\Models\Receptionist;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceptionistController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $this->doctor($request);

        return view('dashboard.doctor.receptionists.index', [
            'receptionists' => $doctor->receptionists()
                ->with('user')
                ->get()
                ->sortBy(fn (Receptionist $receptionist) => $receptionist->user?->name)
                ->values(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->doctor($request);

        return view('dashboard.doctor.receptionists.create');
    }

    public function store(StoreDoctorReceptionistRequest $request): RedirectResponse
    {
        $doctor = $this->doctor($request);
        $data = $request->validated();

        $receptionist = DB::transaction(function () use ($data, $doctor) {
            $user = User::create([
                'name' => $data['name'],
                'slug' => User::uniqueSlug($data['name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'],
                'gender' => $data['gender'] ?? null,
                'role' => 'receptionist',
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->receptionist()->create([
                'doctor_id' => $doctor->id,
            ]);

            return $user;
        });

        return redirect()
            ->route('doctor.receptionists')
            ->with('status', $receptionist->name.' can now sign in and book visits on your open hours.');
    }

    public function destroy(Request $request, User $receptionist): RedirectResponse
    {
        $doctor = $this->doctor($request);

        abort_unless(
            $receptionist->role === 'receptionist'
            && (int) $receptionist->receptionist?->doctor_id === (int) $doctor->id,
            404
        );

        $name = $receptionist->name;

        $receptionist->tokens()->delete();
        DB::table('sessions')->where('user_id', $receptionist->id)->delete();
        $receptionist->delete();

        return redirect()
            ->route('doctor.receptionists')
            ->with('status', $name.' has been removed and can no longer sign in.');
    }

    private function doctor(Request $request): Doctor
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        return $doctor;
    }
}
