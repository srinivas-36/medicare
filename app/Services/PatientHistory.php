<?php

namespace App\Services;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Completed visits between one patient and one doctor.
 * Another doctor's chart is never included.
 */
class PatientHistory
{
    /**
     * Earlier completed visits for this pair, newest first.
     *
     * @return Collection<int, Appointment>
     */
    public function priorCompleted(int $patientId, int $doctorId, Appointment $current): Collection
    {
        return Appointment::query()
            ->with(['consultation', 'reports'])
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->where('status', 'completed')
            ->whereKeyNot($current->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Appointment $prior): bool => $this->isBefore($prior, $current))
            ->values();
    }

    /**
     * Completed visits with this doctor, plus the open visit when it is still pending or confirmed.
     */
    public function visitNumber(Appointment $appointment): int
    {
        $completed = Appointment::query()
            ->where('patient_id', $appointment->patient_id)
            ->where('doctor_id', $appointment->doctor_id)
            ->where('status', 'completed')
            ->count();

        if (in_array($appointment->status, ['pending', 'confirmed'], true)) {
            $completed++;
        }

        return $completed;
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return array<int, bool>
     */
    public function earlierCompletedFlags(Collection $appointments): array
    {
        if ($appointments->isEmpty()) {
            return [];
        }

        $doctorId = (int) $appointments->first()->doctor_id;

        $completed = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('patient_id', $appointments->pluck('patient_id')->unique()->all())
            ->where('status', 'completed')
            ->get(['id', 'patient_id', 'appointment_date', 'appointment_time']);

        $flags = [];

        foreach ($appointments as $appointment) {
            $flags[$appointment->id] = $completed->contains(function (Appointment $prior) use ($appointment) {
                return (int) $prior->patient_id === (int) $appointment->patient_id
                    && (int) $prior->id !== (int) $appointment->id
                    && $this->isBefore($prior, $appointment);
            });
        }

        return $flags;
    }

    private function isBefore(Appointment $prior, Appointment $current): bool
    {
        $priorDate = $prior->appointment_date->toDateString();
        $currentDate = $current->appointment_date->toDateString();

        if ($priorDate !== $currentDate) {
            return $priorDate < $currentDate;
        }

        if ((string) $prior->appointment_time !== (string) $current->appointment_time) {
            return (string) $prior->appointment_time < (string) $current->appointment_time;
        }

        return (int) $prior->id < (int) $current->id;
    }
}
