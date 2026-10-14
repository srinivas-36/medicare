<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Referral;
use App\Models\User;
use App\Notifications\ClinicNotification;
use Illuminate\Support\Facades\Log;

class ClinicNotifier
{
    public function appointmentBooked(Appointment $appointment): void
    {
        $appointment->loadMissing(['patient', 'doctor.user', 'doctor.receptionists.user']);
        $when = $appointment->appointment_date?->format('M j, Y').' '.$appointment->appointment_time;
        $doctor = $appointment->doctor?->user?->name ?? 'your doctor';
        $patient = $appointment->patient?->name ?? 'A patient';

        $this->send($appointment->patient, [
            'title' => 'Appointment confirmed',
            'body' => 'Your visit with '.$doctor.' is confirmed for '.$when.'.',
            'url' => route('patient.bookings.show', $appointment),
        ]);

        $this->send($appointment->doctor?->user, [
            'title' => 'New appointment',
            'body' => $patient.' is booked for '.$when.'.',
            'url' => route('doctor.appointments.show', $appointment),
        ]);

        $this->notifyReceptionists($appointment, 'New appointment', $patient.' is booked for '.$when.'.');
    }

    public function appointmentChanged(Appointment $appointment, string $title, string $body): void
    {
        $appointment->loadMissing(['patient', 'doctor.user', 'doctor.receptionists.user']);

        $this->send($appointment->patient, [
            'title' => $title,
            'body' => $body,
            'url' => route('patient.bookings.show', $appointment),
        ]);

        $this->send($appointment->doctor?->user, [
            'title' => $title,
            'body' => $body,
            'url' => route('doctor.appointments.show', $appointment),
        ]);

        $this->notifyReceptionists($appointment, $title, $body);
    }

    public function referralReceived(Referral $referral): void
    {
        $referral->loadMissing(['toDoctor.user', 'fromDoctor.user', 'patient']);
        $from = $referral->fromDoctor?->user?->name ?? 'A doctor';
        $to = $referral->toDoctor?->user?->name ?? 'another doctor';
        $patient = $referral->patient?->name ?? 'a patient';
        $specialty = $referral->toDoctor?->specialization;
        $toLabel = $specialty ? $to.' ('.$specialty.')' : $to;

        $this->send($referral->toDoctor?->user, [
            'title' => 'Referral received',
            'body' => $from.' referred '.$patient.'.',
            'url' => route('doctor.referrals.show', $referral),
        ]);

        if ($referral->appointment_id) {
            $this->send($referral->patient, [
                'title' => 'You were referred',
                'body' => $from.' referred you to '.$toLabel.'.',
                'url' => route('patient.bookings.show', $referral->appointment_id),
            ]);
        }
    }

    public function referralAnswered(Referral $referral): void
    {
        $referral->loadMissing(['toDoctor.user', 'fromDoctor.user', 'patient']);
        $accepted = $referral->status === 'accepted';
        $to = $referral->toDoctor?->user?->name ?? 'The doctor';
        $patient = $referral->patient?->name ?? 'the patient';
        $body = $accepted
            ? $to.' accepted the referral for '.$patient.'.'
            : $to.' declined the referral for '.$patient.'.';

        if (! $accepted && filled($referral->decline_reason)) {
            $body .= ' '.$referral->decline_reason;
        }

        $this->send($referral->fromDoctor?->user, [
            'title' => $accepted ? 'Referral accepted' : 'Referral declined',
            'body' => $body,
            'url' => route('doctor.referrals.show', $referral),
        ]);

        if (! $referral->appointment_id) {
            return;
        }

        $this->send($referral->patient, [
            'title' => $accepted ? 'Referral accepted' : 'Referral update',
            'body' => $accepted
                ? $to.' accepted your referral and can book the next visit.'
                : $to.' could not take the referral. Your current doctor still has this visit.',
            'url' => route('patient.bookings.show', $referral->appointment_id),
        ]);
    }

    public function referralScheduled(Referral $referral): void
    {
        $referral->loadMissing(['fromDoctor.user', 'toDoctor.user', 'patient']);

        $this->send($referral->fromDoctor?->user, [
            'title' => 'Referral visit booked',
            'body' => ($referral->toDoctor?->user?->name ?? 'The doctor').' booked the referred visit for '.($referral->patient?->name ?? 'the patient').'.',
            'url' => route('doctor.referrals.show', $referral),
        ]);
    }

    public function doctorReviewed(User $doctorUser, bool $approved): void
    {
        $this->send($doctorUser, [
            'title' => $approved ? 'Doctor profile approved' : 'Doctor profile not approved',
            'body' => $approved
                ? 'Your profile is approved and can accept appointments.'
                : 'Your doctor application was not approved.',
            'url' => route('doctor.profile.show'),
        ]);
    }

    private function notifyReceptionists(Appointment $appointment, string $title, string $body): void
    {
        foreach ($appointment->doctor?->receptionists ?? [] as $receptionist) {
            $user = $receptionist->user;

            if (! $user || $user->role !== 'receptionist') {
                continue;
            }

            $this->send($user, [
                'title' => $title,
                'body' => $body,
                'url' => route('receptionist.appointments.show', $appointment),
            ]);
        }
    }

    /**
     * @param  array{title: string, body: string, url?: string|null}  $payload
     */
    private function send(?User $user, array $payload): void
    {
        if (! $user) {
            return;
        }

        try {
            $user->notify(new ClinicNotification($payload));
        } catch (\Throwable $exception) {
            Log::warning('Clinic notification was not stored.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
