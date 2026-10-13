<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDoctorPatientRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentBooking;
use App\Support\AppointmentPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function __construct(private AppointmentBooking $booking) {}

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

        $users = User::query()
            ->whereIn('id', $ids)
            ->orderBy('name')
            ->get();

        $patients = $users->map(function (User $user) use ($summaries, $registeredIds) {
            $row = $summaries->get($user->id);

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'visits' => (int) ($row->visit_count ?? 0),
                'last_visit' => $row ? AppointmentPresenter::date($row->last_visit_on) : 'Not seen yet',
                'last_visit_on' => $row->last_visit_on ?? null,
                'source' => $registeredIds->contains($user->id) ? 'Added by you' : 'From a visit',
            ];
        })->sortByDesc('last_visit_on')->values()->all();

        return view('dashboard.doctor.patients.index', [
            'patients' => $patients,
        ]);
    }

    public function create(Request $request): View
    {
        $doctor = $this->doctor($request);
        $doctor->load(['user', 'timeSlots']);

        $selectedDate = old('date', $request->input('date', now()->toDateString()));

        if (! is_string($selectedDate)) {
            $selectedDate = now()->toDateString();
        }

        $schedule = $this->booking->slotsForNextDays($doctor);

        return view('dashboard.doctor.patients.create', [
            'schedule' => $schedule,
            'selectedDate' => $selectedDate,
            'times' => $schedule[$selectedDate] ?? [],
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays(AppointmentBooking::DAYS - 1)->toDateString(),
        ]);
    }

    public function store(StoreDoctorPatientRequest $request): RedirectResponse
    {
        $doctor = $this->doctor($request);
        $data = $request->validated();

        $existing = User::query()->where('email', $data['email'])->first();

        if ($existing && $existing->role !== 'patient') {
            throw ValidationException::withMessages([
                'email' => 'That email is already used by a staff account.',
            ]);
        }

        if ($existing) {
            $doctor->registeredPatients()->syncWithoutDetaching([$existing->id]);

            $appointment = $this->booking->create(
                patient: $existing,
                doctor: $doctor->load('user'),
                date: $data['date'],
                time: $data['time'],
                reason: $data['reason'] ?? null,
                visitType: $data['visit_type'],
                status: 'confirmed',
            );

            return redirect()
                ->route('doctor.appointments.show', $appointment)
                ->with('status', 'Appointment booked for '.$existing->name.'.');
        }

        $patient = DB::transaction(function () use ($data) {
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

            return $patient;
        });

        $doctor->registeredPatients()->syncWithoutDetaching([$patient->id]);

        $appointment = $this->booking->create(
            patient: $patient,
            doctor: $doctor->load('user'),
            date: $data['date'],
            time: $data['time'],
            reason: $data['reason'] ?? null,
            visitType: $data['visit_type'],
            status: 'confirmed',
        );

        return redirect()
            ->route('doctor.appointments.show', $appointment)
            ->with('status', 'Appointment booked for '.$patient->name.'.');
    }

    public function show(Request $request, User $patient): View
    {
        $doctor = $this->doctor($request);

        abort_unless($patient->role === 'patient', 404);

        $linked = $doctor->registeredPatients()->where('users.id', $patient->id)->exists();

        $appointments = Appointment::query()
            ->with('patient')
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->orderByDesc('id')
            ->get();

        abort_if($appointments->isEmpty() && ! $linked, 404);

        $patient->load('patient_profile');

        return view('dashboard.doctor.patients.show', [
            'patient' => $patient,
            'profile' => $patient->patient_profile,
            'visits' => $appointments
                ->map(fn (Appointment $appointment) => AppointmentPresenter::doctor($appointment))
                ->all(),
        ]);
    }

    private function doctor(Request $request): Doctor
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        return $doctor;
    }
}
