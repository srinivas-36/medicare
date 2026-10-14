<?php

namespace App\Policies;

use App\Models\AiAnalysis;
use App\Models\Appointment;
use App\Models\User;

class AiAnalysisPolicy
{
    public function manage(User $user, Appointment $appointment): bool
    {
        $doctor = $user->doctor;

        return $user->role === 'doctor'
            && $doctor !== null
            && (int) $appointment->doctor_id === (int) $doctor->id;
    }

    public function view(User $user, AiAnalysis $analysis): bool
    {
        $appointment = $analysis->relationLoaded('appointment')
            ? $analysis->appointment
            : $analysis->appointment()->first();

        return $appointment instanceof Appointment && $this->manage($user, $appointment);
    }
}
