<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'patient') {
            return view('dashboard.patient.profile.show', compact('user'));
        }

        if ($user->role === 'doctor') {
            $doctor = $user->doctor;

            abort_unless($doctor, 404, 'Doctor profile not found.');

            return view(
                'dashboard.doctor.profile.show',
                compact('user', 'doctor')
            );
        }

        abort(403, 'You are not authorized to access this profile.');
    }

    /**
     * Display the correct edit form.
     */
    public function edit(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'patient') {
            return view('patient.profile.edit', compact('user'));
        }

        if ($user->role === 'doctor') {
            $doctor = $user->doctor;

            abort_unless($doctor, 404, 'Doctor profile not found.');

            return view(
                'dashboard.doctor.profile.edit',
                compact('user', 'doctor')
            );
        }

        abort(403, 'You are not authorized to edit this profile.');
    }
}
