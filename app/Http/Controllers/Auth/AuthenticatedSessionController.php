<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $draft = $request->session()->get('booking_draft');
        $hasDraft = is_array($draft) && ! empty($draft['doctor_id']);

        if ($hasDraft && $request->user()->role === 'patient') {
            $input = is_array($draft['input'] ?? null) ? $draft['input'] : [];

            return redirect()
                ->route('doctors.book', $draft['doctor_id'])
                ->withInput($input)
                ->with('status', 'You are signed in. Your date, time, and notes are filled in again. Attach any reports once more before confirming.');
        }

        if ($hasDraft) {
            $request->session()->forget('booking_draft');
            $request->session()->forget('url.intended');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
