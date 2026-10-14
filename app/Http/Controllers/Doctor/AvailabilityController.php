<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDoctorAvailabilityRequest;
use App\Models\DoctorAvailabilityException;
use App\Services\AppointmentBooking;
use App\Support\ProfileViewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function __construct(private AppointmentBooking $booking) {}

    public function edit(Request $request): View
    {
        $user = $request->user();
        $doctor = $user->doctor;
        abort_unless($doctor, 404);

        $profile = ProfileViewData::doctor($user);

        return view('dashboard.doctor.availability', [
            'timeSlots' => old('timeSlots', $profile['timeSlots']),
            'appointmentDuration' => (int) old('appointmentDuration', $profile['appointmentDuration']),
            'appointmentBuffer' => (int) old('appointmentBuffer', $profile['appointmentBuffer']),
            'exceptions' => $doctor->availabilityExceptions()->orderBy('starts_on')->get(),
        ]);
    }

    public function update(UpdateDoctorAvailabilityRequest $request): RedirectResponse
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 404);
        $data = $request->validated();

        DB::transaction(function () use ($doctor, $data) {
            $doctor->update([
                'appointment_duration' => (int) ($data['appointmentDuration'] ?? $doctor->appointment_duration ?? 30),
                'appointment_buffer' => (int) ($data['appointmentBuffer'] ?? $doctor->appointment_buffer ?? 0),
            ]);

            $doctor->timeSlots()->delete();

            foreach ($data['timeSlots'] ?? [] as $row) {
                $doctor->timeSlots()->create([
                    'day' => strtolower($row['day']),
                    'starting_time' => AppointmentBooking::normalizeTime($row['startingTime']),
                    'ending_time' => AppointmentBooking::normalizeTime($row['endingTime']),
                    'is_available' => filter_var($row['isAvailable'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        });

        return redirect()->route('doctor.availability')->with('status', 'Working hours saved.');
    }

    public function storeException(Request $request): RedirectResponse
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor, 404);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(['unavailable', 'extra', 'override'])],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if (in_array($data['kind'], ['extra', 'override'], true) && (blank($data['starts_at'] ?? null) || blank($data['ends_at'] ?? null) || $data['starts_at'] >= $data['ends_at'])) {
            throw ValidationException::withMessages([
                'ends_at' => 'The ending time must be after the starting time.',
            ]);
        }

        if ($data['kind'] === 'unavailable') {
            $conflicts = $this->booking->conflictingAppointments(
                $doctor,
                Carbon::parse($data['starts_on']),
                Carbon::parse($data['ends_on']),
                $data['starts_at'] ?? null,
                $data['ends_at'] ?? null,
            );

            if ($conflicts->isNotEmpty()) {
                $names = $conflicts->load('patient')->map(function ($appointment) {
                    return ($appointment->patient?->name ?? 'Patient').' on '.$appointment->appointment_date->format('M j');
                })->implode(', ');

                throw ValidationException::withMessages([
                    'starts_on' => 'Resolve these booked visits before blocking the time: '.$names.'.',
                ]);
            }
        }

        $doctor->availabilityExceptions()->create([
            'title' => $data['title'],
            'kind' => $data['kind'],
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'],
            'starts_at' => filled($data['starts_at'] ?? null) ? AppointmentBooking::normalizeTime($data['starts_at']) : null,
            'ends_at' => filled($data['ends_at'] ?? null) ? AppointmentBooking::normalizeTime($data['ends_at']) : null,
            'reason' => $data['reason'] ?? null,
        ]);

        return redirect()->route('doctor.availability')->with('status', 'Calendar exception saved.');
    }

    public function destroyException(Request $request, DoctorAvailabilityException $exception): RedirectResponse
    {
        $doctor = $request->user()->doctor;
        abort_unless($doctor && (int) $exception->doctor_id === (int) $doctor->id, 404);
        $exception->delete();

        return redirect()->route('doctor.availability')->with('status', 'Calendar exception removed.');
    }
}
