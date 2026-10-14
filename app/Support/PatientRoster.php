<?php

namespace App\Support;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PatientRoster
{
    public static function remember(Doctor $doctor, User $patient, string $origin, ?int $addedBy): void
    {
        $row = DB::table('doctor_patients')
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->first();

        if ($row && filled($row->origin)) {
            return;
        }

        if ($row) {
            DB::table('doctor_patients')->where('id', $row->id)->update([
                'origin' => $origin,
                'added_by' => $addedBy,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('doctor_patients')->insert([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'origin' => $origin,
            'added_by' => $addedBy,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public static function labelFor(Doctor $doctor, User $patient, ?User $viewer): string
    {
        $link = DB::table('doctor_patients')
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->first();

        $actors = $link && $link->added_by
            ? User::query()->whereKey($link->added_by)->get()->keyBy('id')
            : collect();

        return self::format($link, $actors, $viewer);
    }

    /**
     * @param  Collection<int, User>  $patients
     * @return array<int, string>
     */
    public static function labelsFor(Doctor $doctor, Collection $patients, ?User $viewer): array
    {
        $links = DB::table('doctor_patients')
            ->where('doctor_id', $doctor->id)
            ->whereIn('patient_id', $patients->pluck('id'))
            ->get()
            ->keyBy('patient_id');

        $actors = User::query()
            ->whereIn('id', $links->pluck('added_by')->filter()->unique()->values())
            ->get()
            ->keyBy('id');

        $labels = [];

        foreach ($patients as $patient) {
            $labels[$patient->id] = self::format($links->get($patient->id), $actors, $viewer);
        }

        return $labels;
    }

    /**
     * @param  Collection<int, User>  $actors
     */
    private static function format(?object $link, Collection $actors, ?User $viewer): string
    {
        $actor = $link?->added_by ? $actors->get($link->added_by) : null;

        return match ($link->origin ?? null) {
            'booking' => 'Booked by the patient',
            'doctor' => ! $actor || ($viewer && (int) $actor->id === (int) $viewer->id)
                ? 'Added by you'
                : 'Added by '.$actor->name,
            'receptionist' => 'Added by '.($actor->name ?? 'a receptionist'),
            'referral' => 'Added from a referral',
            default => 'From a visit',
        };
    }
}
