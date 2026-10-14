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

            $keptReports = is_array($draft['files'] ?? null) && $draft['files'] !== [];
            $status = 'You are signed in. Your date, time, and notes are filled in again.';
            $status .= $keptReports
                ? ' Your reports are saved and will be included when you confirm.'
                : ' Attach any reports once more before confirming.';

            return redirect()
                ->route('doctors.book', $draft['doctor_id'])
                ->withInput($input)
                ->with('status', $status);
        }

        if ($hasDraft) {
            $request->session()->forget('booking_draft');
            $request->session()->forget('url.intended');
        }

        $this->forgetIntendedUrlForAnotherRole($request);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * A visit to another role's page should not become the page shown after login.
     */
    private function forgetIntendedUrlForAnotherRole(Request $request): void
    {
        $intended = $request->session()->get('url.intended');

        if (! is_string($intended) || $intended === '') {
            return;
        }

        $path = parse_url($intended, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            $request->session()->forget('url.intended');

            return;
        }

        $path = '/'.ltrim($path, '/');
        $role = (string) $request->user()?->role;

        foreach ([
            'patient' => '/patient',
            'doctor' => '/doctor',
            'receptionist' => '/receptionist',
            'admin' => '/admin',
        ] as $areaRole => $prefix) {
            $matches = $path === $prefix || str_starts_with($path, $prefix.'/');

            if ($matches && $areaRole !== $role) {
                $request->session()->forget('url.intended');

                return;
            }
        }
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
