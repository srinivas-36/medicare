<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAppointmentStatusRequest;
use App\Http\Requests\UpdateConsultationRequest;
use App\Jobs\GenerateClinicalAnalysis;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Support\AppointmentPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AppointmentController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $doctor = $this->doctor($request);

        if ($redirect = AppointmentPage::redirectCanonical($request)) {
            return $redirect;
        }

        $filters = AppointmentPage::filters($request);
        $query = $doctor->appointments()->with('patient');
        $hasAny = (clone $query)->exists();
        AppointmentPage::apply($query, $filters, 'patient');

        return view('dashboard.appointments.index', [
            'role' => 'doctor',
            'appointments' => $query
                ->orderByDesc('appointment_date')
                ->orderByDesc('appointment_time')
                ->paginate(10)
                ->withQueryString(),
            'filters' => $filters,
            'filtersActive' => AppointmentPage::active($filters),
            'hasAny' => $hasAny,
            'slots' => AppointmentPage::slotOptions(
                $doctor->timeSlots()->orderBy('starting_time')->orderBy('day')->get(),
                $filters,
            ),
            'listRoute' => 'doctor.appointments',
            'showRoute' => 'doctor.appointments.show',
        ]);
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
        ]);

        return view('dashboard.appointments.show', [
            'role' => 'doctor',
            'appointment' => $appointment,
            'backUrl' => route('doctor.appointments', AppointmentPage::listQuery($request)),
            'backLabel' => 'Back to appointments',
        ]);
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
        abort_unless(in_array($appointment->status, ['confirmed', 'completed'], true), 403);

        $consultation = Consultation::query()->firstOrNew([
            'appointment_id' => $appointment->id,
        ]);

        $consultation->doctor_id = $doctor->id;
        $consultation->patient_id = $appointment->patient_id;
        $consultation->clinical_notes = $request->validated('clinical_notes');
        $consultation->diagnosis = $request->validated('diagnosis');
        $consultation->plan = $request->validated('plan');

        if (blank(config('services.ai.key'))) {
            $consultation->ai_status = 'skipped';
            $consultation->ai_summary = null;
        } else {
            $consultation->ai_status = 'pending';
        }

        $consultation->save();

        GenerateClinicalAnalysis::dispatch($consultation->id);

        return back()->with('status', 'Consult saved.');
    }

    public function report(Request $request, Appointment $appointment, AppointmentReport $report): StreamedResponse
    {
        $doctor = $this->doctor($request);

        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);
        abort_unless((int) $report->appointment_id === (int) $appointment->id, 404);
        abort_unless(Storage::disk('public')->exists($report->path), 404);

        return Storage::disk('public')->response($report->path, $report->original_name);
    }

    private function doctor(Request $request): Doctor
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        return $doctor;
    }
}
