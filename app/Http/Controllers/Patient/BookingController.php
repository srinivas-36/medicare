<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAppointmentRequest;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\TimeSlot;
use App\Support\AppointmentPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'patient', 403);

        if ($redirect = AppointmentPage::redirectCanonical($request)) {
            return $redirect;
        }

        $filters = AppointmentPage::filters($request);
        $query = $user->appointmentsAsPatient()->with('doctor.user');
        $hasAny = (clone $query)->exists();
        AppointmentPage::apply($query, $filters, 'doctor');

        $doctorIds = $user->appointmentsAsPatient()->distinct()->pluck('doctor_id');

        return view('dashboard.appointments.index', [
            'role' => 'patient',
            'appointments' => $query
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->paginate(10)
                ->withQueryString(),
            'filters' => $filters,
            'filtersActive' => AppointmentPage::active($filters),
            'hasAny' => $hasAny,
            'slots' => AppointmentPage::slotOptions(
                TimeSlot::query()->whereIn('doctor_id', $doctorIds)->orderBy('starting_time')->orderBy('day')->get(),
                $filters,
            ),
            'listRoute' => 'patient.bookings',
            'showRoute' => 'patient.bookings.show',
        ]);
    }

    public function show(Request $request, Appointment $appointment): View
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);

        $appointment->load([
            'doctor.user',
            'patient',
            'reports',
            'consultation',
        ]);

        return view('dashboard.appointments.show', [
            'role' => 'patient',
            'appointment' => $appointment,
            'backUrl' => route('patient.bookings', AppointmentPage::listQuery($request)),
            'backLabel' => 'Back to bookings',
        ]);
    }

    public function report(Request $request, Appointment $appointment, AppointmentReport $report): StreamedResponse
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);
        abort_unless(Storage::disk('public')->exists($report->path), 404);

        return Storage::disk('public')->response($report->path, $report->original_name);
    }

    public function cancel(CancelAppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->role === 'patient', 403);
        abort_unless($appointment->patient_id === $user->id, 403);

        if (! $appointment->canPatientCancel()) {
            throw ValidationException::withMessages([
                'appointment' => 'You can only cancel a future appointment that is pending or confirmed.',
            ]);
        }

        $appointment->status = 'cancelled';
        $appointment->cancelled_by = $user->id;
        $appointment->cancel_reason = $request->validated('cancel_reason');
        $appointment->save();

        return back()->with('status', 'Appointment cancelled.');
    }
}
