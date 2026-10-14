<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAppointmentMessageRequest;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Support\AppointmentPage;
use App\Support\MedicalFile;
use App\Support\ReferralDesk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentController extends Controller
{
    public function index(Request $request): View|RedirectResponse|StreamedResponse
    {
        $doctor = $this->doctor($request);

        if ($redirect = AppointmentPage::redirectCanonical($request)) {
            return $redirect;
        }

        $filters = AppointmentPage::filters($request);
        $query = $doctor->appointments()->with('patient');
        $hasAny = $doctor->appointments()->exists();
        AppointmentPage::apply($query, $filters, 'patient');
        $query->orderByDesc('appointment_date')->orderByDesc('appointment_time');

        if ($request->query('export') === 'csv') {
            return $this->exportCsv($query->get());
        }

        return view('dashboard.appointments.index', [
            'role' => 'doctor',
            'appointments' => $query->paginate(15)->withQueryString(),
            'filters' => $filters,
            'filtersActive' => AppointmentPage::active($filters),
            'hasAny' => $hasAny,
            'listRoute' => 'doctor.appointments',
            'showRoute' => 'doctor.appointments.show',
        ]);
    }

    private function exportCsv($appointments): StreamedResponse
    {
        return response()->streamDownload(function () use ($appointments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Patient', 'Email', 'Date', 'Time', 'Status', 'Payment']);

            foreach ($appointments as $appointment) {
                fputcsv($out, [
                    $appointment->patient?->name ?: 'Patient',
                    $appointment->patient?->email ?: '',
                    AppointmentPage::date($appointment->appointment_date),
                    AppointmentPage::timeSlot($appointment),
                    AppointmentPage::status($appointment->status)['label'],
                    AppointmentPage::payment($appointment->payment_status)['label'],
                ]);
            }

            fclose($out);
        }, 'appointments.csv', ['Content-Type' => 'text/csv']);
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);

        $appointment->load([
            'patient.patient_profile',
            'doctor.user',
            'reports',
            'consultation',
            'analyses' => fn ($query) => $query->latest('id'),
            'referrals.toDoctor.user',
            'messages' => fn ($query) => $query->with('sender')->orderBy('id'),
        ]);

        $appointment->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $visitHistory = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $appointment->patient_id)
            ->whereKeyNot($appointment->id)
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->orderByDesc('id')
            ->limit(4)
            ->get();

        return view('dashboard.appointments.show', array_merge([
            'role' => 'doctor',
            'appointment' => $appointment,
            'visitHistory' => $visitHistory,
            'backUrl' => route('doctor.appointments', AppointmentPage::listQuery($request)),
            'backLabel' => 'Back to appointments',
        ], ReferralDesk::for($doctor, $appointment)));
    }

    public function updateStatus(UpdateAppointmentStatusRequest $request, Appointment $appointment): RedirectResponse
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);

        $appointment->applyStatus(
            $request->validated('status'),
            $request->user(),
            $request->validated('cancel_reason'),
        );

        return back()->with('status', 'Appointment updated.');
    }

    public function updateConsult(UpdateConsultationRequest $request, Appointment $appointment): RedirectResponse
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);
        abort_unless(in_array($appointment->status, ['confirmed', 'checked_in', 'completed'], true), 403);

        $consultation = Consultation::query()->firstOrNew([
            'appointment_id' => $appointment->id,
        ]);

        $consultation->doctor_id = $doctor->id;
        $consultation->patient_id = $appointment->patient_id;
        $consultation->clinical_notes = $request->validated('clinical_notes');
        $consultation->diagnosis = $request->validated('diagnosis');
        $consultation->plan = $request->validated('plan');
        $consultation->follow_up_instructions = $request->validated('follow_up_instructions');
        $consultation->follow_up_on = $request->validated('follow_up_on');
        $consultation->save();

        return back()->with('status', 'Consult saved.');
    }

    public function messages(Request $request, Appointment $appointment): JsonResponse
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);

        return response()->json([
            'messages' => AppointmentPage::messageFeed($appointment, $request->user()->id),
        ]);
    }

    public function storeMessage(StoreAppointmentMessageRequest $request, Appointment $appointment): RedirectResponse|JsonResponse
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);

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

    public function report(Request $request, Appointment $appointment, AppointmentReport $report): StreamedResponse
    {
        $doctor = $this->doctor($request);

        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);
        abort_unless((int) $report->appointment_id === (int) $appointment->id, 404);
        abort_unless(MedicalFile::exists($report->path), 404);

        return MedicalFile::response($report->path, $report->original_name);
    }

    private function doctor(Request $request): Doctor
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        return $doctor;
    }
}
