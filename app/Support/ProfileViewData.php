<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;

class ProfileViewData
{
    public static function doctor(User $user): array
    {
        $doctor = $user->doctor;

        abort_unless($doctor, 404);

        $doctor->loadMissing(['qualifications', 'experiences', 'timeSlots']);

        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'photo' => $user->photo,
            'bio' => $doctor->bio,
            'about' => $doctor->about,
            'specialization' => $doctor->specialization,
            'ticketPrice' => $doctor->ticket_price,
            'isApproved' => $doctor->approval_status,
            'qualifications' => $doctor->qualifications->map(fn ($item) => [
                'degree' => $item->degree,
                'university' => $item->institution,
                'startingDate' => self::date($item->start_date),
                'endingDate' => self::date($item->end_date),
            ])->all(),
            'experiences' => $doctor->experiences->map(fn ($item) => [
                'position' => $item->designation,
                'hospital' => $item->hospital_name,
                'startingDate' => self::date($item->start_date),
                'endingDate' => self::date($item->end_date),
            ])->all(),
            'timeSlots' => $doctor->timeSlots->map(fn ($item) => [
                'day' => $item->day,
                'startingTime' => self::clock($item->starting_time),
                'endingTime' => self::clock($item->ending_time),
                'isAvailable' => (bool) $item->is_available,
            ])->all(),
            'appointmentDuration' => (int) ($doctor->appointment_duration ?? 30),
            'appointmentBuffer' => (int) ($doctor->appointment_buffer ?? 0),
        ];
    }

    public static function patient(User $user): array
    {
        $profile = $user->patient_profile;
        $notes = $profile?->health_notes ?? [];

        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'photo' => $user->photo,
            'age' => $profile?->age,
            'bloodType' => $profile?->blood_type,
            'bp' => $notes['bp'] ?? ['known' => true, 'value' => ''],
            'diabetic' => $notes['diabetic'] ?? ['known' => true, 'value' => ''],
            'hyperthyroidism' => $notes['hyperthyroidism'] ?? ['known' => true, 'value' => ''],
        ];
    }

    private static function date(mixed $value): string
    {
        if (blank($value)) {
            return '';
        }

        return Carbon::parse($value)->toDateString();
    }

    private static function clock(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '';
        }

        return strlen($value) >= 5 ? substr($value, 0, 5) : $value;
    }
}
