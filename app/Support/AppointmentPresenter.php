<?php

namespace App\Support;

use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Referral;
use Illuminate\Support\Carbon;

class AppointmentPresenter
{
    public const STATUS_LABELS = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'checked_in' => 'At the clinic',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No-show',
    ];

    public const VISIT_LABELS = [
        'check-up' => 'Check-up',
        'consultation' => 'Consultation',
        'follow-up' => 'Follow-up',
        'emergency' => 'Emergency',
    ];

    public static function doctor(Appointment $appointment, bool $returning = false): array
    {
        return [
            'id' => $appointment->id,
            'patient' => $appointment->patient?->name ?? 'Patient',
            'condition' => filled($appointment->reason) ? $appointment->reason : 'Not specified',
            'visit' => self::VISIT_LABELS[$appointment->visit_type] ?? (filled($appointment->visit_type) ? ucfirst((string) $appointment->visit_type) : 'Visit'),
            'time' => self::time($appointment->appointment_time),
            'date' => self::date($appointment->appointment_date),
            'status' => self::STATUS_LABELS[$appointment->status] ?? ucfirst((string) $appointment->status),
            'status_value' => $appointment->status,
            'can_update' => in_array($appointment->status, ['pending', 'confirmed'], true),
            'returning' => $returning,
        ];
    }

    public static function visit(Appointment $appointment): array
    {
        $profile = $appointment->patient?->patient_profile;

        return [
            'patient' => $appointment->patient?->name ?? 'Patient',
            'patient_id' => $appointment->patient_id,
            'age' => $profile?->age,
            'blood_type' => $profile?->blood_type,
            'health_notes' => is_array($profile?->health_notes) ? $profile->health_notes : [],
            'date' => self::date($appointment->appointment_date),
            'time' => self::time($appointment->appointment_time),
            'visit' => self::VISIT_LABELS[$appointment->visit_type] ?? (filled($appointment->visit_type) ? ucfirst((string) $appointment->visit_type) : 'Visit'),
            'status' => self::STATUS_LABELS[$appointment->status] ?? ucfirst((string) $appointment->status),
            'status_value' => $appointment->status,
            'reason' => filled($appointment->reason) ? $appointment->reason : 'Not specified',
            'notes' => filled($appointment->notes) ? $appointment->notes : null,
            'can_update' => in_array($appointment->status, ['pending', 'confirmed'], true),
            'reports' => $appointment->relationLoaded('reports')
                ? self::reportGroups($appointment, 'doctor.appointments.reports.show')
                : [],
        ];
    }

    public static function history(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'date' => self::date($appointment->appointment_date),
            'time' => self::time($appointment->appointment_time),
            'visit' => self::VISIT_LABELS[$appointment->visit_type] ?? ucfirst((string) $appointment->visit_type),
            'reason' => filled($appointment->reason) ? $appointment->reason : 'Not specified',
            'diagnosis' => filled($appointment->consultation?->diagnosis) ? $appointment->consultation->diagnosis : null,
            'plan' => filled($appointment->consultation?->plan) ? $appointment->consultation->plan : null,
            'reports' => $appointment->relationLoaded('reports')
                ? self::reportGroups($appointment, 'doctor.appointments.reports.show')
                : [],
        ];
    }

    public static function patient(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'doctor' => $appointment->doctor?->user?->name ?? 'Doctor',
            'specialization' => $appointment->doctor?->specialization ?? '',
            'time' => filled($appointment->ending_time)
                ? self::time($appointment->appointment_time).' – '.self::time($appointment->ending_time)
                : self::time($appointment->appointment_time),
            'date' => self::date($appointment->appointment_date),
            'status' => self::STATUS_LABELS[$appointment->status] ?? ucfirst((string) $appointment->status),
            'status_value' => $appointment->status,
            'visit' => self::VISIT_LABELS[$appointment->visit_type] ?? (filled($appointment->visit_type) ? ucfirst((string) $appointment->visit_type) : null),
            'reason' => filled($appointment->reason) ? $appointment->reason : null,
            'cancel_reason' => filled($appointment->cancel_reason) ? $appointment->cancel_reason : null,
            'fee' => $appointment->ticket_price !== null
                ? number_format((float) $appointment->ticket_price, 2).' USD'
                : null,
            'photo' => $appointment->doctor?->user?->photo,
            'initials' => self::initials($appointment->doctor?->user?->name ?? 'Doctor'),
            'clinic' => self::clinic($appointment),
            'mode' => 'In-person visit',
            'payment' => AppointmentPage::payment($appointment->payment_status)['label'],
            'can_cancel' => $appointment->canPatientCancel(),
            'reports' => $appointment->relationLoaded('reports')
                ? self::reportGroups($appointment, 'patient.bookings.reports.show')
                : [],
            'referrals' => $appointment->relationLoaded('referrals')
                ? $appointment->referrals->map(function (Referral $referral): array {
                    return [
                        'doctor' => $referral->toDoctor?->user?->name ?? 'Doctor',
                        'specialization' => $referral->toDoctor?->specialization ?? '',
                    ];
                })->values()->all()
                : [],
        ];
    }

    public static function receptionist(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'patient' => $appointment->patient?->name ?? 'Patient',
            'doctor' => $appointment->doctor?->user?->name ?? 'Doctor',
            'time' => self::time($appointment->appointment_time),
            'date' => self::date($appointment->appointment_date),
            'iso' => $appointment->appointment_date?->toDateString() ?? '',
            'type' => self::VISIT_LABELS[$appointment->visit_type] ?? ucfirst((string) $appointment->visit_type),
            'status' => self::STATUS_LABELS[$appointment->status] ?? ucfirst((string) $appointment->status),
            'status_value' => $appointment->status,
            'can_update' => in_array($appointment->status, ['pending', 'confirmed', 'checked_in'], true),
        ];
    }

    public static function time(mixed $value): string
    {
        return Carbon::parse((string) $value)->format('g:i A');
    }

    public static function date(mixed $value): string
    {
        return Carbon::parse($value)->format('d M Y');
    }

    /**
     * @return list<array{name: string, files: list<array{name: string, extension: string, url: string}>}>
     */
    private static function reportGroups(Appointment $appointment, string $routeName): array
    {
        return $appointment->reports
            ->groupBy('group_name')
            ->map(function ($files, string $group) use ($appointment, $routeName) {
                return [
                    'name' => $group,
                    'files' => $files->map(function (AppointmentReport $file) use ($appointment, $routeName) {
                        $extension = pathinfo($file->original_name, PATHINFO_EXTENSION);

                        return [
                            'name' => $file->original_name,
                            'extension' => $extension !== '' ? strtoupper($extension) : 'FILE',
                            'url' => route($routeName, [$appointment, $file]),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private static function clinic(Appointment $appointment): ?string
    {
        $doctor = $appointment->doctor;

        if ($doctor === null || ! $doctor->relationLoaded('experiences')) {
            return null;
        }

        $current = $doctor->experiences->firstWhere('is_current', true)
            ?? $doctor->experiences->sortByDesc('id')->first();

        $label = collect([$current?->hospital_name, $current?->location])
            ->filter(fn ($part) => filled($part))
            ->implode(', ');

        return $label !== '' ? $label : null;
    }

    private static function initials(string $name): string
    {
        $name = trim((string) preg_replace('/^Dr\.?\s+/i', '', $name));
        $parts = preg_split('/\s+/', $name) ?: [];
        $letters = strtoupper(mb_substr((string) ($parts[0] ?? 'D'), 0, 1).mb_substr((string) ($parts[1] ?? ''), 0, 1));

        return $letters !== '' ? $letters : 'DR';
    }
}
