<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceptionistAppointmentRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Services\AppointmentBooking;
use App\Support\AppointmentPresenter;
use App\Support\ClinicSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentBooking $booking,
    ) {}

    public function index(Request $request): View
    {
        $doctor = $this->clinic($request);

        $range = $request->string('range')->toString();

        if (! in_array($range, ['today', 'upcoming', 'all'], true)) {
            $range = 'today';
        }

        $status = $request->string('status')->toString();
        $search = trim($request->string('q')->toString());
        $appointments = [];

        if ($doctor) {
            $query = Appointment::query()
                ->with(['patient', 'doctor.user'])
                ->where('doctor_id', $doctor->id);

            if ($range === 'upcoming') {
                $query->upcoming();
            } elseif ($range === 'today') {
                $query->whereDate('appointment_date', today());
            }

            if (array_key_exists($status, AppointmentPresenter::STATUS_LABELS)) {
                $query->where('status', $status);
            } else {
                $status = '';
            }

            if ($search !== '') {
                $query->whereHas('patient', function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            }

            $appointments = $query
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get()
                ->map(fn (Appointment $appointment) => AppointmentPresenter::receptionist($appointment))
                ->all();
        } else {
            $status = '';
        }

        return view('dashboard.receptionist.appointments', [
            'appointments' => $appointments,
            'range' => $range,
            'status' => $status,
            'doctorName' => $doctor?->user?->name,
            'specialization' => $doctor?->specialization,
            'search' => $search ?? '',
        ]);
    }

    public function create(Request $request): View
    {
        $doctor = $this->clinic($request);

        if (! $doctor) {
            return view('dashboard.receptionist.appointments.create', [
                'doctorName' => null,
            ]);
        }

        $doctor->load(['user', 'timeSlots']);
        $duration = max(5, (int) ($doctor->appointment_duration ?: AppointmentBooking::INTERVAL_MINUTES));
        $hours = ClinicSchedule::hours($doctor);
        $days = ClinicSchedule::days($doctor, $this->booking, $hours);

        $minDate = now()->toDateString();
        $maxDate = AppointmentBooking::bookableUntil()->toDateString();
        $selectedDate = (string) old('date', $request->input('date', ''));

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate) || $selectedDate < $minDate || $selectedDate > $maxDate) {
            $selectedDate = '';
        }

        if ($selectedDate === '' || ! isset($days[$selectedDate])) {
            $selectedDate = ClinicSchedule::firstOpenDate($days);
        }

        $selectedSlot = (string) old('slot', '');

        if ($selectedSlot === '' && is_string(old('starting_time')) && is_string(old('ending_time'))) {
            $selectedSlot = old('starting_time').'|'.old('ending_time');
        }

        $search = trim($request->string('q')->toString());
        $selectedPatientId = (string) old('patient_id', $request->input('patient_id', ''));
        $roster = ClinicSchedule::patients(null, $selectedPatientId);
        $patients = $search !== ''
            ? ClinicSchedule::patients($search, $selectedPatientId)
            : $roster;
        $month = ClinicSchedule::visibleMonth($request->input('month'), $selectedDate, $minDate, $maxDate);

        return view('dashboard.receptionist.appointments.create', [
            'doctorName' => $doctor->user?->name ?? 'Your doctor',
            'specialization' => $doctor->specialization,
            'hours' => $hours,
            'duration' => $duration,
            'desk' => [
                'searchUrl' => route('receptionist.patients.search'),
                'query' => $search,
                'searched' => $search !== '',
                'roster' => $roster,
                'patients' => $patients,
                'patientId' => $selectedPatientId,
                'patientMode' => old('patient_mode', $request->input('mode') === 'new' ? 'new' : 'existing'),
                'newName' => old('name', ''),
                'newEmail' => old('email', ''),
                'newPhone' => old('phone', ''),
                'newAge' => old('age', ''),
                'newGender' => old('gender', ''),
                'date' => $selectedDate,
                'slot' => $selectedSlot,
                'days' => $days,
                'minDate' => $minDate,
                'maxDate' => $maxDate,
                'month' => $month->format('Y-m'),
                'duration' => $duration,
            ],
        ]);
    }

    public function store(StoreReceptionistAppointmentRequest $request): RedirectResponse
    {
        $doctor = $this->clinic($request);

        abort_unless($doctor, 403);

        $postedDoctor = $request->input('doctor_id');

        if ($postedDoctor !== null && $postedDoctor !== '' && (int) $postedDoctor !== (int) $doctor->id) {
            abort(403);
        }

        $data = $request->validated();
        $doctor->load('user');

        $patient = DB::transaction(function () use ($request, $doctor, $data) {
            $patient = $data['patient_mode'] === 'new'
                ? $this->patientFromForm($data)
                : User::query()->where('role', 'patient')->findOrFail($data['patient_id']);

            $this->booking->confirmWindow(
                patient: $patient,
                doctor: $doctor,
                date: $data['date'],
                startingTime: $data['starting_time'],
                endingTime: $data['ending_time'],
                reason: $data['reason'] ?? null,
                visitType: $data['visit_type'],
                bookedBy: $request->user(),
            );

            return $patient;
        });

        return redirect()
            ->route('receptionist.appointments')
            ->with('status', 'Appointment booked for '.$patient->name.'.');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function patientFromForm(array $data): User
    {
        $existing = User::query()->where('email', $data['email'])->first();

        if ($existing && $existing->role !== 'patient') {
            throw ValidationException::withMessages([
                'email' => 'That email is already used by a staff account.',
            ]);
        }

        if ($existing) {
            return $existing;
        }

        return DB::transaction(function () use ($data) {
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
            ]);

            return $patient;
        });
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): RedirectResponse
    {
        $doctor = $this->clinic($request);

        abort_unless($doctor && (int) $appointment->doctor_id === (int) $doctor->id, 403);

        $appointment->applyStatus(
            $request->validated('status'),
            $request->user(),
            $request->validated('cancel_reason'),
        );

        return back()->with('status', 'Appointment updated.');
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $doctor = $this->clinic($request);
        abort_unless($doctor && (int) $appointment->doctor_id === (int) $doctor->id, 403);
        $appointment->load('patient');

        return view('dashboard.receptionist.appointments.show', [
            'appointment' => $appointment,
            'openSlots' => $this->booking->calendar($doctor, null, null, $appointment->id),
            'minDate' => now()->toDateString(),
            'maxDate' => AppointmentBooking::bookableUntil()->toDateString(),
        ]);
    }

    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        $doctor = $this->clinic($request);
        abort_unless($doctor && (int) $appointment->doctor_id === (int) $doctor->id, 403);

        $slot = (string) $request->input('slot', '');

        if (preg_match('/^(\d{4}-\d{2}-\d{2})\|(\d{2}:\d{2})$/', $slot, $matches)) {
            $request->merge([
                'date' => $matches[1],
                'time' => $matches[2],
            ]);
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $this->booking->reschedule($appointment, $request->user(), $data['date'], $data['time'], $data['reason'] ?? null);

        return back()->with('status', 'Appointment rescheduled.');
    }

    private function clinic(Request $request): ?Doctor
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $doctor = $request->user()->receptionist?->doctor;

        if ($doctor) {
            $doctor->loadMissing('user');
        }

        return $doctor;
    }
}
