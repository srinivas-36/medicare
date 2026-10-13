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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentBooking $booking) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $range = $request->string('range')->toString();

        if (! in_array($range, ['today', 'upcoming', 'all'], true)) {
            $range = 'today';
        }

        $status = $request->string('status')->toString();
        $query = Appointment::query()->with(['patient', 'doctor.user']);

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

        $appointments = $query
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->get()
            ->map(fn (Appointment $appointment) => AppointmentPresenter::receptionist($appointment))
            ->all();

        return view('dashboard.receptionist.appointments', [
            'appointments' => $appointments,
            'range' => $range,
            'status' => $status,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $doctors = Doctor::query()
            ->with([
                'user',
                'timeSlots',
                'appointments' => function ($query) {
                    $query->where('status', '!=', 'cancelled')
                        ->whereDate('appointment_date', '>=', today()->toDateString())
                        ->whereDate('appointment_date', '<=', today()->addDays(AppointmentBooking::DAYS - 1)->toDateString());
                },
            ])
            ->where('approval_status', 'approved')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->orderBy('id')
            ->get();

        $schedule = [];

        foreach ($doctors as $doctor) {
            $schedule[(string) $doctor->id] = $this->booking->slotsForNextDays($doctor);
        }

        $search = $request->string('q')->toString();
        $patients = collect();

        if ($search !== '') {
            $patients = User::query()
                ->where('role', 'patient')
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                })
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        $selectedPatientId = (string) old('patient_id', $request->input('patient_id', ''));

        if ($selectedPatientId !== '' && $patients->doesntContain('id', (int) $selectedPatientId)) {
            $selected = User::query()->where('role', 'patient')->find($selectedPatientId);

            if ($selected) {
                $patients->prepend($selected);
            }
        }

        return view('dashboard.receptionist.appointments.create', [
            'doctors' => $doctors,
            'patients' => $patients,
            'schedule' => $schedule,
            'search' => $search,
            'searched' => $request->filled('q'),
            'selectedDoctorId' => (string) old('doctor_id', $request->input('doctor_id', '')),
            'selectedPatientId' => $selectedPatientId,
            'selectedDate' => old('date', $request->input('date', now()->toDateString())),
            'minDate' => now()->toDateString(),
            'maxDate' => now()->addDays(AppointmentBooking::DAYS - 1)->toDateString(),
        ]);
    }

    public function store(StoreReceptionistAppointmentRequest $request): RedirectResponse
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $data = $request->validated();
        $doctor = Doctor::query()->with('user')->findOrFail($data['doctor_id']);

        abort_unless(
            $doctor->approval_status === 'approved' && $doctor->user?->is_active,
            403
        );

        $patient = User::query()->where('role', 'patient')->findOrFail($data['patient_id']);

        $this->booking->create(
            patient: $patient,
            doctor: $doctor,
            date: $data['date'],
            time: $data['time'],
            reason: $data['reason'] ?? null,
            visitType: $data['visit_type'],
            status: 'confirmed',
        );

        return redirect()
            ->route('receptionist.appointments')
            ->with('status', 'Appointment booked.');
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $appointment->applyStatus(
            $request->validated('status'),
            $request->user(),
            $request->validated('cancel_reason'),
        );

        return back()->with('status', 'Appointment updated.');
    }
}
