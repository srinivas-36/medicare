<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDoctorProfileRequest;
use App\Http\Requests\UpdatePatientProfileRequest;
use App\Services\AppointmentBooking;
use App\Support\ProfilePhoto;
use App\Support\ProfileViewData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        if ($user->role === 'patient') {
            return view('dashboard.patient.show', [
                'patient' => ProfileViewData::patient($user),
            ]);
        }

        if ($user->role === 'doctor') {
            return view('dashboard.doctor.profile.show', [
                'doctor' => ProfileViewData::doctor($user),
            ]);
        }

        abort(403, 'You are not authorized to access this profile.');
    }

    public function edit(Request $request): View
    {
        $user = $request->user();

        if ($user->role === 'patient') {
            return view('dashboard.patient.edit', [
                'patient' => ProfileViewData::patient($user),
            ]);
        }

        if ($user->role === 'doctor') {
            return view('dashboard.doctor.profile.edit', [
                'doctor' => ProfileViewData::doctor($user),
            ]);
        }

        abort(403, 'You are not authorized to edit this profile.');
    }

    public function updatePatient(UpdatePatientProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'patient', 403);

        $data = $request->validated();
        $newPhoto = ProfilePhoto::store($request->file('photo'), 'patient');
        $previousPhoto = $user->photo;

        try {
            DB::transaction(function () use ($user, $request, $data, $newPhoto) {
                $user->fill([
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'gender' => $data['gender'] ?? null,
                ]);

                if ($newPhoto) {
                    $user->photo = $newPhoto;
                }

                if (! empty($data['password'])) {
                    $user->password = $data['password'];
                }

                $user->save();

                $profile = $user->patient_profile()->firstOrNew([]);
                $profile->age = $data['age'];
                $profile->blood_type = $data['bloodType'] ?? null;
                $profile->health_notes = [
                    'bp' => [
                        'known' => $request->boolean('bp.known'),
                        'value' => $data['bp']['value'] ?? null,
                    ],
                    'diabetic' => [
                        'known' => $request->boolean('diabetic.known'),
                        'value' => $data['diabetic']['value'] ?? null,
                    ],
                    'hyperthyroidism' => [
                        'known' => $request->boolean('hyperthyroidism.known'),
                        'value' => $data['hyperthyroidism']['value'] ?? null,
                    ],
                ];
                $profile->save();
            });
        } catch (\Throwable $exception) {
            ProfilePhoto::delete($newPhoto);

            throw $exception;
        }

        $user->unsetRelation('patient_profile');

        if ($newPhoto) {
            ProfilePhoto::delete($previousPhoto);
        }

        return redirect()
            ->route('patient.profile.show')
            ->with('status', 'Profile saved.');
    }

    public function updateDoctor(UpdateDoctorProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $doctor = $user->doctor;

        abort_unless($user->role === 'doctor', 403);
        abort_unless($doctor, 404);

        $data = $request->validated();
        $newPhoto = ProfilePhoto::store($request->file('photo'), 'doctor');
        $previousPhoto = $user->photo;

        try {
            DB::transaction(function () use ($user, $doctor, $data, $newPhoto) {
                $user->fill([
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'gender' => $data['gender'] ?? null,
                ]);

                if ($newPhoto) {
                    $user->photo = $newPhoto;
                }

                $user->save();

                $doctor->update([
                    'specialization' => $data['specialization'] ?? null,
                    'ticket_price' => $data['ticketPrice'],
                    'bio' => $data['bio'] ?? null,
                    'about' => $data['about'] ?? null,
                ]);

                $doctor->qualifications()->delete();

                foreach ($data['qualifications'] ?? [] as $row) {
                    $doctor->qualifications()->create([
                        'degree' => $row['degree'],
                        'institution' => $row['university'],
                        'start_date' => $row['startingDate'] ?? null,
                        'end_date' => $row['endingDate'] ?? null,
                    ]);
                }

                $doctor->experiences()->delete();

                foreach ($data['experiences'] ?? [] as $row) {
                    $doctor->experiences()->create([
                        'designation' => $row['position'],
                        'hospital_name' => $row['hospital'],
                        'start_date' => $row['startingDate'],
                        'end_date' => $row['endingDate'] ?? null,
                        'is_current' => blank($row['endingDate'] ?? null),
                    ]);
                }

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
        } catch (\Throwable $exception) {
            ProfilePhoto::delete($newPhoto);

            throw $exception;
        }

        $user->unsetRelation('doctor');

        if ($newPhoto) {
            ProfilePhoto::delete($previousPhoto);
        }

        return redirect()
            ->route('doctor.profile.show')
            ->with('status', 'Profile saved.');
    }
}
