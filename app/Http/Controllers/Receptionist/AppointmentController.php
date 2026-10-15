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
use Illuminate\Support\Carbon;
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
        $calendarRows = [];

        if ($doctor) {
            $query = Appointment::query()
                ->with(['patient', 'doctor.user'])
                ->where('doctor_id', $doctor->id);

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

            $table = clone $query;

            if ($range === 'upcoming') {
                $table->upcoming();
            } elseif ($range === 'today') {
                $table->whereDate('appointment_date', today());
            }

            $present = fn (Appointment $appointment) => AppointmentPresenter::receptionist($appointment);

            $appointments = $table
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get()
                ->map($present)
                ->all();

            $calendarRows = $query
                ->whereDate('appointment_date', '>=', now()->startOfMonth()->subMonth()->toDateString())
                ->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->get()
                ->map($present)
                ->all();
        } else {
            $status = '';
        }

        $selectedDate = $request->string('date')->toString();

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
            $selectedDate = '';
        }

        $calendar = $doctor
            ? $this->appointmentCalendar($calendarRows, $request->string('month')->toString(), $selectedDate)
            : null;

        if ($selectedDate !== '') {
            $appointments = array_values(array_filter(
                $calendarRows,
                fn (array $row) => ($row['iso'] ?? '') === $selectedDate,
            ));
        }

        return view('dashboard.receptionist.appointments', [
            'appointments' => $appointments,
            'calendar' => $calendar,
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
        $bookedOn = $appointment->appointment_date?->toDateString();
        $canMove = in_array($appointment->status, ['pending', 'confirmed'], true);
        $openSlots = $canMove
            ? collect($this->booking->calendar($doctor, null, null, $appointment->id))
                ->filter(fn (array $slots, string $date) => $bookedOn === null || $date > $bookedOn)
                ->all()
            : [];
        $bookedLabel = AppointmentPresenter::time($appointment->appointment_time);

        if (filled($appointment->ending_time)) {
            $bookedLabel .= ' – '.AppointmentPresenter::time($appointment->ending_time);
        }

        return view('dashboard.receptionist.appointments.show', [
            'appointment' => $appointment,
            'openSlots' => $openSlots,
            'bookedDate' => $bookedOn ?? '',
            'bookedLabel' => $bookedLabel,
            'canMove' => $canMove,
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

        $bookedOn = $appointment->appointment_date?->toDateString();
        $chosen = Carbon::parse($data['date'])->toDateString();

        if ($bookedOn !== null && $chosen <= $bookedOn) {
            throw ValidationException::withMessages([
                'date' => 'Choose a day after the booked date.',
            ]);
        }

        $this->booking->reschedule($appointment, $request->user(), $chosen, $data['time'], $data['reason'] ?? null);

        return back()->with('status', 'Appointment rescheduled.');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{month: string, label: string, prevMonth: string, nextMonth: string, selected: string, selectedLabel: ?string, weeks: list<list<array<string, mixed>|null>>}
     */
    private function appointmentCalendar(array $rows, string $month, string $selected): array
    {
        $dates = collect($rows)
            ->pluck('iso')
            ->filter(fn ($date) => is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date))
            ->values();

        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            if ($selected !== '') {
                $month = substr($selected, 0, 7);
            } else {
                $current = now()->format('Y-m');
                $earliest = $dates->sort()->first();
                $month = ($dates->contains(fn (string $date) => str_starts_with($date, $current)) || ! is_string($earliest))
                    ? $current
                    : substr($earliest, 0, 7);
            }
        }

        $cursor = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfMonth();
        $byDate = collect($rows)->groupBy('iso');
        $pad = $cursor->dayOfWeek;
        $cells = array_fill(0, $pad, null);

        for ($day = 1; $day <= $cursor->daysInMonth; $day++) {
            $date = $cursor->copy()->day($day)->toDateString();
            $items = collect($byDate->get($date, []))
                ->map(fn (array $row) => [
                    'id' => $row['id'],
                    'patient' => $row['patient'],
                    'time' => $row['time'],
                ])
                ->values()
                ->all();

            $cells[] = [
                'date' => $date,
                'day' => $day,
                'count' => count($items),
                'items' => $items,
                'today' => $date === now()->toDateString(),
                'selected' => $date === $selected,
            ];
        }

        while (count($cells) % 7 !== 0) {
            $cells[] = null;
        }

        return [
            'month' => $cursor->format('Y-m'),
            'label' => $cursor->format('F Y'),
            'prevMonth' => $cursor->copy()->subMonth()->format('Y-m'),
            'nextMonth' => $cursor->copy()->addMonth()->format('Y-m'),
            'selected' => $selected,
            'selectedLabel' => $selected !== '' ? Carbon::parse($selected)->format('l, F j, Y') : null,
            'weeks' => array_chunk($cells, 7),
        ];
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
