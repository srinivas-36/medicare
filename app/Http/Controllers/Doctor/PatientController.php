<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorPatientRequest;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentBooking;
use App\Support\AppointmentPresenter;
use App\Support\ClinicSchedule;
use App\Support\PatientRoster;
use App\Support\ReferralDesk;
use App\Support\ReferralSuggestions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(
        private AppointmentBooking $booking,
    ) {}

    public function index(Request $request): View
    {
        $doctor = $this->doctor($request);

        $summaries = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->selectRaw('patient_id, COUNT(*) as visit_count, MAX(appointment_date) as last_visit_on')
            ->groupBy('patient_id')
            ->get()
            ->keyBy('patient_id');

        $registeredIds = $doctor->registeredPatients()->pluck('users.id');
        $ids = $summaries->keys()->merge($registeredIds)->unique()->filter()->values();

        $search = trim($request->string('q')->toString());

        $users = User::query()
            ->whereIn('id', $ids)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();

        $patientIds = $users->pluck('id');

        $completed = $patientIds->isEmpty()
            ? collect()
            : Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->where('status', 'completed')
                ->whereIn('patient_id', $patientIds)
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->get()
                ->unique('patient_id')
                ->keyBy('patient_id');

        $nextVisits = $patientIds->isEmpty()
            ? collect()
            : Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('patient_id', $patientIds)
                ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                ->upcoming()
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get()
                ->unique('patient_id')
                ->keyBy('patient_id');

        $followUps = $patientIds->isEmpty()
            ? collect()
            : Consultation::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('patient_id', $patientIds)
                ->whereNotNull('follow_up_on')
                ->whereDate('follow_up_on', '>=', today())
                ->orderBy('follow_up_on')
                ->get()
                ->unique('patient_id')
                ->keyBy('patient_id');

        $sources = PatientRoster::labelsFor($doctor, $users, $request->user());

        $patients = $users->map(function (User $user) use ($summaries, $completed, $nextVisits, $followUps, $sources) {
            $row = $summaries->get($user->id);
            $last = $completed->get($user->id);
            $next = $nextVisits->get($user->id);
            $followUp = $followUps->get($user->id);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'visits' => (int) ($row->visit_count ?? 0),
                'last_visit' => $last ? AppointmentPresenter::date($last->appointment_date) : 'No completed visit',
                'last_visit_on' => $row->last_visit_on ?? null,
                'next' => $next ? AppointmentPresenter::date($next->appointment_date).' · '.AppointmentPresenter::time($next->appointment_time) : null,
                'follow_up' => $followUp ? AppointmentPresenter::date($followUp->follow_up_on) : null,
                'source' => $sources[$user->id] ?? 'From a visit',
            ];
        })->sortByDesc('last_visit_on')->values()->all();

        return view('dashboard.doctor.patients.index', [
            'patients' => $patients,
            'search' => $search,
            'hasPatients' => $ids->isNotEmpty(),
        ]);
    }

    public function create(Request $request): View
    {
        $doctor = $this->doctor($request);
        $doctor->load(['user', 'timeSlots']);

        $minDate = now()->toDateString();
        $maxDate = AppointmentBooking::bookableUntil()->toDateString();
        $hours = ClinicSchedule::hours($doctor);
        $days = ClinicSchedule::days($doctor, $this->booking, $hours);
        $selectedDate = (string) old('date', $request->input('date', ''));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || $selectedDate < $minDate || $selectedDate > $maxDate) {
            $selectedDate = '';
        }

        if ($selectedDate === '' || ! isset($days[$selectedDate])) {
            $selectedDate = ClinicSchedule::firstOpenDate($days);
        }

        $selectedSlot = (string) old('slot', '');

        if ($selectedSlot === '' && is_string(old('time')) && old('time') !== '') {
            $selectedSlot = ClinicSchedule::slotStarting($days, $selectedDate, (string) old('time'));
        }

        $search = trim($request->string('q')->toString());
        $selectedPatientId = (string) old('patient_id', $request->input('patient_id', ''));
        $roster = ClinicSchedule::patients(null, $selectedPatientId);
        $patients = $search !== ''
            ? ClinicSchedule::patients($search, $selectedPatientId)
            : $roster;

        return view('dashboard.doctor.patients.create', [
            'doctorName' => $doctor->user?->name ?? 'Your practice',
            'specialization' => $doctor->specialization,
            'hours' => $hours,
            'desk' => [
                'searchUrl' => route('doctor.patients.search'),
                'query' => $search,
                'searched' => $search !== '',
                'roster' => $roster,
                'patients' => $patients,
                'patientId' => $selectedPatientId,
                'patientMode' => old('mode', $request->input('mode') === 'new' ? 'new' : 'existing'),
                'newName' => old('name', ''),
                'newEmail' => old('email', ''),
                'newPhone' => old('phone', ''),
                'newAge' => old('age', ''),
                'newGender' => old('gender', ''),
                'newBlood' => old('blood_type', ''),
                'date' => $selectedDate,
                'slot' => $selectedSlot,
                'days' => $days,
                'minDate' => $minDate,
                'maxDate' => $maxDate,
                'month' => ClinicSchedule::visibleMonth($request->input('month'), $selectedDate, $minDate, $maxDate)->format('Y-m'),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->doctor($request);
        $term = trim($request->string('q')->toString());

        if (mb_strlen($term) < 2) {
            return response()->json(['patients' => []]);
        }

        return response()->json([
            'patients' => ClinicSchedule::patients($term, null, 20),
        ]);
    }

    public function store(StoreDoctorPatientRequest $request): RedirectResponse
    {
        $doctor = $this->doctor($request);
        $data = $request->validated();

        if (($data['mode'] ?? 'new') === 'existing') {
            $patient = User::query()->where('role', 'patient')->findOrFail($data['patient_id']);

            return $this->bookAddedPatient($doctor, $patient, $data, $request->user());
        }

        $existing = User::query()->where('email', $data['email'])->first();

        if ($existing && $existing->role !== 'patient') {
            throw ValidationException::withMessages([
                'email' => 'That email is already used by a staff account.',
            ]);
        }

        if ($existing) {
            return $this->bookAddedPatient($doctor, $existing, $data, $request->user());
        }

        return DB::transaction(function () use ($doctor, $data, $request) {
            $patient = User::create([
                'name' => $data['name'],
                'slug' => User::uniqueSlug($data['name']),
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'],
                'gender' => $data['gender'] ?? null,
                'role' => 'patient',
                'is_active' => true,
            ]);

            $patient->patient_profile()->create([
                'age' => $data['age'],
                'blood_type' => $data['blood_type'] ?? null,
            ]);

            return $this->bookAddedPatient($doctor, $patient, $data, $request->user());
        });
    }

    public function show(Request $request, User $patient): View
    {
        $doctor = $this->doctor($request);

        abort_unless($patient->role === 'patient', 404);

        $linked = $doctor->registeredPatients()->where('users.id', $patient->id)->exists();

        $appointments = Appointment::query()
            ->with(['patient', 'consultation', 'reports', 'referrals.toDoctor.user'])
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->orderByDesc('id')
            ->get();

        abort_if($appointments->isEmpty() && ! $linked, 404);

        $patient->load('patient_profile');

        $chart = $appointments
            ->filter(function (Appointment $appointment) {
                return in_array($appointment->status, ['pending', 'confirmed', 'checked_in'], true)
                    && $appointment->appointment_date?->greaterThanOrEqualTo(today());
            })
            ->sortBy(fn (Appointment $appointment) => $appointment->appointment_date->format('Y-m-d').' '.(string) $appointment->appointment_time)
            ->first() ?? $appointments->first();

        $referralAppointment = $appointments
            ->filter(fn (Appointment $appointment) => $appointment->canRefer() && $appointment->appointment_date?->greaterThanOrEqualTo(today()))
            ->sortBy(fn (Appointment $appointment) => $appointment->appointment_date->format('Y-m-d').' '.(string) $appointment->appointment_time)
            ->first()
            ?? $appointments->first(fn (Appointment $appointment) => $appointment->canRefer());

        return view('dashboard.doctor.patients.show', [
            'patient' => $patient,
            'addedLabel' => PatientRoster::labelFor($doctor, $patient, $request->user()),
            'profile' => $patient->patient_profile,
            'chartId' => $chart?->id,
            'referralAppointment' => $referralAppointment,
            'actionDefaultId' => $referralAppointment?->id,
            'referralDesk' => $referralAppointment ? ReferralDesk::for($doctor, $referralAppointment) : null,
            'visits' => $appointments
                ->map(function (Appointment $appointment) {
                    $row = AppointmentPresenter::doctor($appointment);
                    $row['visit'] = AppointmentPresenter::VISIT_LABELS[$appointment->visit_type] ?? 'Visit';
                    $row['diagnosis'] = $appointment->consultation?->diagnosis;
                    $row['plan'] = $appointment->consultation?->plan;
                    $clinical = collect([
                        $appointment->reason,
                        $appointment->consultation?->diagnosis,
                        $appointment->consultation?->clinical_notes,
                    ])->filter(fn ($value) => filled($value))->implode("\n");
                    $symptoms = ReferralSuggestions::analyze($clinical)['symptoms'];
                    $row['clinical_notes'] = $appointment->consultation?->clinical_notes ?? '';
                    $row['follow_up_instructions'] = $appointment->consultation?->follow_up_instructions ?? '';
                    $row['follow_up_on'] = $appointment->consultation?->follow_up_on?->toDateString() ?? '';
                    $row['can_act'] = $appointment->canRefer();
                    $row['consult_url'] = route('doctor.appointments.consult.update', $appointment);
                    $row['refer_url'] = route('doctor.referrals.store', $appointment);
                    $row['preview_url'] = route('doctor.referrals.preview.store', $appointment);
                    $row['draft'] = $symptoms === []
                        ? ''
                        : 'Please assess '.($appointment->patient?->name ?: 'this patient').' for '.implode(', ', $symptoms).'.';
                    $row['report_files'] = $appointment->reports->map(fn ($report) => [
                        'id' => $report->id,
                        'name' => $report->original_name,
                        'url' => route('doctor.appointments.reports.show', [$appointment, $report]),
                    ])->values()->all();
                    $row['reports'] = $appointment->reports->count();
                    $row['referrals'] = $appointment->referrals->map(fn ($referral) => [
                        'id' => $referral->id,
                        'doctor' => $referral->toDoctor?->user?->name ?? 'Doctor',
                        'status' => $referral->statusLabel(),
                    ])->all();

                    return $row;
                })
                ->all(),
        ]);
    }

    public function update(Request $request, User $patient): RedirectResponse
    {
        $doctor = $this->doctor($request);

        abort_unless($patient->role === 'patient', 404);

        $linked = $doctor->registeredPatients()->where('users.id', $patient->id)->exists();
        $hasVisit = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->exists();

        abort_if(! $linked && ! $hasVisit, 404);

        foreach (['phone', 'age', 'gender', 'blood_type', 'bp', 'diabetic', 'hyperthyroidism'] as $key) {
            if ($request->input($key) === '') {
                $request->merge([$key => null]);
            }
        }

        $data = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'age' => ['nullable', 'integer', 'min:0', 'max:120'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'blood_type' => ['nullable', 'string', 'max:20'],
            'bp' => ['nullable', 'string', 'max:20'],
            'diabetic' => ['nullable', Rule::in(['yes', 'no'])],
            'hyperthyroidism' => ['nullable', Rule::in(['yes', 'no'])],
        ]);

        $patient->update([
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'] ?? null,
        ]);

        $profile = $patient->patient_profile()->firstOrNew([]);
        $notes = is_array($profile->health_notes) ? $profile->health_notes : [];

        foreach (['bp', 'diabetic', 'hyperthyroidism'] as $key) {
            $previous = is_array($notes[$key] ?? null) ? $notes[$key] : ['known' => true, 'value' => null];
            $value = $data[$key] ?? null;
            $notes[$key] = filled($value)
                ? ['known' => true, 'value' => $value]
                : ['known' => filter_var($previous['known'] ?? true, FILTER_VALIDATE_BOOLEAN), 'value' => null];
        }

        $profile->age = $data['age'] ?? null;
        $profile->blood_type = $data['blood_type'] ?? null;
        $profile->health_notes = $notes;
        $profile->user()->associate($patient);
        $profile->save();

        return redirect()
            ->route('doctor.patients.show', $patient)
            ->with('status', 'Patient chart updated.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function bookAddedPatient(Doctor $doctor, User $patient, array $data, User $actor): RedirectResponse
    {
        $appointment = $this->booking->create(
            patient: $patient,
            doctor: $doctor->load('user'),
            date: $data['date'],
            time: $data['time'],
            reason: $data['reason'] ?? null,
            visitType: $data['visit_type'],
            status: 'confirmed',
            bookedBy: $actor,
        );

        return redirect()
            ->route('doctor.appointments.show', $appointment)
            ->with('status', 'Appointment booked for '.$patient->name.'.');
    }

    private function doctor(Request $request): Doctor
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        return $doctor;
    }
}
