<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Http\Requests\CancelAppointmentRequest;
use App\Http\Requests\StoreAppointmentMessageRequest;
use App\Http\Requests\StoreAppointmentReportsRequest;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\TimeSlot;
use App\Services\AppointmentBooking;
use App\Services\AppointmentReports;
use App\Support\AppointmentPage;
use App\Support\MedicalFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookingController extends Controller
{
    public function __construct(
        private AppointmentReports $reports,
        private AppointmentBooking $booking,
    ) {}

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
            'patient.patient_profile',
            'reports',
            'consultation',
            'messages' => fn ($query) => $query->with('sender')->orderBy('id'),
        ]);

        $appointment->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('dashboard.appointments.show', [
            'role' => 'patient',
            'appointment' => $appointment,
            'openSlots' => $appointment->canPatientCancel()
                ? $this->booking->calendar($appointment->doctor, null, null, $appointment->id)
                : [],
            'backUrl' => route('patient.bookings', AppointmentPage::listQuery($request)),
            'backLabel' => 'Back to bookings',
        ]);
    }

    public function report(Request $request, Appointment $appointment, AppointmentReport $report): StreamedResponse
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);
        abort_unless(MedicalFile::exists($report->path), 404);

        return MedicalFile::response($report->path, $report->original_name);
    }

    public function storeReports(StoreAppointmentReportsRequest $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);
        abort_if($appointment->reports()->count() >= 24, 422, 'This booking already has the maximum number of reports.');

        $stored = $this->reports->store(
            $request,
            $request->user()->id,
            $appointment->appointment_date->toDateString(),
        );

        try {
            foreach ($stored as $report) {
                $appointment->reports()->create($report);
            }
        } catch (\Throwable $exception) {
            $this->reports->discard($stored);

            throw $exception;
        }

        return back()->with('status', 'Reports added.');
    }

    public function destroyReport(Request $request, Appointment $appointment, AppointmentReport $report): RedirectResponse
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);
        abort_unless($appointment->status !== 'cancelled', 403);

        MedicalFile::delete($report->path);
        $report->delete();

        return back()->with('status', 'Report removed.');
    }

    public function messages(Request $request, Appointment $appointment): JsonResponse
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);

        return response()->json([
            'messages' => AppointmentPage::messageFeed($appointment, $request->user()->id),
        ]);
    }

    public function storeMessage(StoreAppointmentMessageRequest $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);

        $message = $appointment->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);
        $message->setRelation('sender', $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => AppointmentPage::presentMessage($message, $appointment),
            ]);
        }

        return back()->with('status', 'Message sent.');
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

    public function reschedule(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);
        abort_unless($appointment->canPatientCancel(), 422);

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
}
