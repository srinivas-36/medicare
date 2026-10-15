<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReferralRequest;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Doctor;
use App\Models\Referral;
use App\Models\User;
use App\Services\AppointmentBooking;
use App\Services\ClinicNotifier;
use App\Services\ReferralSender;
use App\Support\AppointmentPresenter;
use App\Support\MedicalFile;
use App\Support\MockReferralDirectory;
use App\Support\MockReferralInbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $this->doctor($request);

        $map = function (Referral $referral, string $direction): array {
            $other = $direction === 'incoming' ? $referral->fromDoctor : $referral->toDoctor;

            return [
                'id' => $referral->id,
                'patient' => $referral->patient?->name ?? 'Patient',
                'from' => $other?->user?->name ?? 'Doctor',
                'specialization' => $other?->specialization ?? '',
                'reason' => filled($referral->reason) ? $referral->reason : 'No reason recorded',
                'sent' => AppointmentPresenter::date($referral->created_at).' · '.AppointmentPresenter::time($referral->created_at),
                'status' => $referral->statusLabel(),
                'status_value' => $referral->status,
            ];
        };

        $referrals = $doctor->referralsReceived()
            ->with(['patient', 'fromDoctor.user'])
            ->orderByRaw("case when status = 'sent' then 0 else 1 end")
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Referral $referral) => $map($referral, 'incoming'))
            ->all();

        $outgoing = $doctor->referralsSent()
            ->with(['patient', 'toDoctor.user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Referral $referral) => $map($referral, 'outgoing'))
            ->all();

        $sent = MockReferralInbox::sent((int) $request->user()->id);

        return view('dashboard.doctor.referrals.index', [
            'referrals' => $referrals === [] ? MockReferralInbox::incoming() : $referrals,
            'outgoing' => [...$sent, ...($outgoing === [] ? MockReferralInbox::outgoing() : $outgoing)],
        ]);
    }

    public function storePreview(Request $request, Appointment $appointment): JsonResponse
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);
        abort_unless($appointment->canRefer(), 403);

        $data = $request->validate([
            'to_doctor_id' => ['required', 'string'],
            'reason' => ['required', 'string', 'max:5000'],
            'clinical_question' => ['nullable', 'string', 'max:5000'],
            'urgency' => ['nullable', 'in:routine,soon,urgent'],
            'report_ids' => ['sometimes', 'array'],
            'report_ids.*' => ['integer'],
        ]);

        $recipient = collect(MockReferralDirectory::doctors())->firstWhere('id', $data['to_doctor_id']);
        abort_unless(is_array($recipient), 422);

        $urgency = [
            'routine' => 'Routine',
            'soon' => 'Soon',
            'urgent' => 'Urgent',
        ][$data['urgency'] ?? 'routine'] ?? 'Routine';

        $appointment->load(['patient.patient_profile', 'reports', 'consultation']);
        $reportIds = $request->exists('report_ids')
            ? array_map('intval', (array) $request->input('report_ids', []))
            : null;
        $packet = app(ReferralSender::class)->patientPacket($appointment, $reportIds);
        $profile = $appointment->patient?->patient_profile;
        $notes = $profile?->health_notes;

        $record = MockReferralInbox::remember((int) $request->user()->id, [
            'patient' => $appointment->patient?->name ?? 'Patient',
            'appointment_id' => $appointment->id,
            'to' => $recipient['name'],
            'specialization' => $recipient['specialization'],
            'clinic' => $recipient['clinic'] ?? '',
            'reason' => $data['reason'],
            'question' => $data['clinical_question'] ?? '',
            'urgency' => $urgency,
            'patient_details' => [
                'age' => $profile?->age,
                'gender' => filled($appointment->patient?->gender) ? (string) $appointment->patient->gender : null,
                'health_notes' => is_array($notes) ? $notes : [],
            ],
            'summary' => $packet,
            'reports' => $this->reportCards($appointment, $reportIds),
            'report_ids' => $reportIds ?? $appointment->reports->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ]);

        return response()->json(['referral' => $record]);
    }

    public function preview(Request $request, string $preview): View
    {
        $record = MockReferralInbox::find((int) $request->user()->id, $preview);
        abort_unless($record, 404);

        $appointment = $this->previewAppointment($request, $record);
        $reportIds = array_key_exists('report_ids', $record)
            ? array_map('intval', (array) $record['report_ids'])
            : null;
        $storedSummary = is_array($record['summary'] ?? null) ? $record['summary'] : [];
        $summary = filled($storedSummary['text'] ?? null)
            ? $storedSummary
            : ($appointment ? app(ReferralSender::class)->patientPacket($appointment, $reportIds) : []);
        $reports = is_array($record['reports'] ?? null)
            ? $record['reports']
            : ($appointment ? $this->reportCards($appointment, $reportIds) : []);

        $frozen = is_array($record['patient_details'] ?? null) ? $record['patient_details'] : null;
        $patientUser = $appointment?->patient;
        $liveNotes = $patientUser?->patient_profile?->health_notes;

        return view('dashboard.doctor.referrals.preview', [
            'referral' => $record,
            'summary' => $summary,
            'reports' => $this->withReportLinks($reports, $appointment),
            'demographics' => $frozen !== null
                ? [
                    'age' => $frozen['age'] ?? null,
                    'gender' => filled($frozen['gender'] ?? null) ? (string) $frozen['gender'] : null,
                    'health_notes' => is_array($frozen['health_notes'] ?? null) ? $frozen['health_notes'] : [],
                ]
                : [
                    'age' => $patientUser?->patient_profile?->age,
                    'gender' => filled($patientUser?->gender) ? (string) $patientUser->gender : null,
                    'health_notes' => is_array($liveNotes) ? $liveNotes : [],
                ],
            'viewer' => [
                'name' => $request->user()->name,
                'specialization' => $request->user()->doctor?->specialization ?? '',
            ],
        ]);
    }

    public function show(Request $request, Referral $referral): View
    {
        $doctor = $this->doctor($request);
        abort_unless($this->involved($doctor, $referral), 403);

        $referral->load(['reports', 'fromDoctor.user', 'toDoctor.user', 'toDoctor.experiences', 'patient', 'proposedAppointment']);

        $incoming = (int) $referral->to_doctor_id === (int) $doctor->id;
        $openSlots = $incoming && $referral->status === 'accepted'
            ? app(AppointmentBooking::class)->calendar($doctor)
            : [];

        return view('dashboard.doctor.referrals.show', [
            'referral' => $referral,
            'incoming' => $incoming,
            'snapshot' => $referral->summary_snapshot ?? [],
            'status' => $referral->statusLabel(),
            'sent' => AppointmentPresenter::date($referral->created_at).' · '.AppointmentPresenter::time($referral->created_at),
            'openSlots' => $openSlots,
        ]);
    }

    public function store(StoreReferralRequest $request, Appointment $appointment, ReferralSender $sender): RedirectResponse
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);
        abort_unless($appointment->canRefer(), 403);

        $recipient = Doctor::query()->with('user')->findOrFail($request->validated('to_doctor_id'));
        $reportIds = $request->boolean('report_ids_present')
            ? array_map('intval', (array) $request->input('report_ids', []))
            : $request->validated('report_ids');

        $sender->send(
            $appointment,
            $doctor,
            $recipient,
            $request->validated('reason'),
            $reportIds,
            $request->validated('urgency') ?? 'routine',
            $request->validated('clinical_question'),
        );

        return redirect()
            ->route('doctor.appointments.show', $appointment)
            ->with('status', 'Referral sent to '.($recipient->user?->name ?? 'the specialist').'.');
    }

    public function accept(Request $request, Referral $referral): RedirectResponse
    {
        return $this->respond($request, $referral, 'accepted');
    }

    public function decline(Request $request, Referral $referral): RedirectResponse
    {
        $data = $request->validate([
            'decline_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->respond($request, $referral, 'declined', $data['decline_reason'] ?? null);
    }

    public function schedule(Request $request, Referral $referral, AppointmentBooking $booking): RedirectResponse
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $referral->to_doctor_id === (int) $doctor->id, 403);
        abort_unless($referral->status === 'accepted', 422);

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
        ]);

        $patient = User::query()->whereKey($referral->patient_id)->where('role', 'patient')->firstOrFail();

        $appointment = $booking->create(
            patient: $patient,
            doctor: $doctor->load('user'),
            date: $data['date'],
            time: $data['time'],
            reason: 'Referral: '.$referral->reason,
            visitType: 'consultation',
            status: 'confirmed',
            source: 'referral',
        );

        $appointment->forceFill(['referral_id' => $referral->id])->save();
        $doctor->registeredPatients()->syncWithoutDetaching([$patient->id]);
        $referral->forceFill([
            'status' => 'appointment_booked',
            'proposed_appointment_id' => $appointment->id,
        ])->save();
        app(ClinicNotifier::class)->referralScheduled($referral->fresh());

        return redirect()
            ->route('doctor.appointments.show', $appointment)
            ->with('status', 'Referral appointment booked.');
    }

    public function report(Request $request, Referral $referral, AppointmentReport $report): StreamedResponse
    {
        $doctor = $this->doctor($request);

        abort_unless($this->involved($doctor, $referral), 403);
        abort_unless($referral->reports()->whereKey($report->id)->exists(), 403);
        abort_unless(MedicalFile::exists($report->path), 404);

        return MedicalFile::response($report->path, $report->original_name);
    }

    private function respond(Request $request, Referral $referral, string $status, ?string $declineReason = null): RedirectResponse
    {
        $doctor = $this->doctor($request);

        abort_unless((int) $referral->to_doctor_id === (int) $doctor->id, 403);
        abort_unless($referral->status === 'sent', 422);

        $referral->forceFill([
            'status' => $status,
            'responded_at' => now(),
            'decline_reason' => $status === 'declined' ? $declineReason : null,
        ])->save();

        app(ClinicNotifier::class)->referralAnswered($referral);

        return back()->with('status', $status === 'accepted' ? 'Referral accepted.' : 'Referral declined.');
    }

    private function doctor(Request $request): Doctor
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        return $doctor;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function previewAppointment(Request $request, array $record): ?Appointment
    {
        $id = (int) ($record['appointment_id'] ?? 0);

        if ($id === 0) {
            return null;
        }

        return Appointment::query()
            ->with(['patient.patient_profile', 'reports', 'consultation'])
            ->whereKey($id)
            ->where('doctor_id', $this->doctor($request)->id)
            ->first();
    }

    /**
     * @param  list<int>|null  $reportIds
     * @return list<array{id: int, appointment_id: int, title: string, group: string, kind: string}>
     */
    private function reportCards(Appointment $appointment, ?array $reportIds): array
    {
        $reports = $appointment->reports;

        if (is_array($reportIds)) {
            $allowed = array_map('intval', $reportIds);
            $reports = $reports->filter(
                fn (AppointmentReport $report) => in_array((int) $report->id, $allowed, true),
            );
        }

        return $reports
            ->map(function (AppointmentReport $report) use ($appointment) {
                $extension = strtolower((string) pathinfo((string) $report->original_name, PATHINFO_EXTENSION));
                $kind = match ($extension) {
                    'pdf' => 'PDF',
                    'png', 'jpg', 'jpeg', 'webp', 'gif' => 'Image',
                    default => 'File',
                };
                $stem = (string) pathinfo((string) $report->original_name, PATHINFO_FILENAME);
                $readable = $stem !== '' && ! preg_match('/^[0-9a-f-]{16,}$/i', $stem);
                $group = trim((string) $report->group_name);

                return [
                    'id' => (int) $report->id,
                    'appointment_id' => (int) $appointment->id,
                    'title' => $readable ? (string) $report->original_name : ($group !== '' ? $group : 'Report'),
                    'group' => $group,
                    'kind' => $kind,
                    'date' => $report->created_at?->timezone(config('app.timezone'))->format('j M Y') ?? '',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $reports
     * @return list<array<string, mixed>>
     */
    private function withReportLinks(array $reports, ?Appointment $appointment): array
    {
        return array_map(function (array $report) use ($appointment) {
            $match = $appointment?->reports->firstWhere('id', (int) ($report['id'] ?? 0));

            if ($appointment && $match) {
                $report['url'] = route('doctor.appointments.reports.show', [$appointment, $match]);
            }

            return $report;
        }, $reports);
    }

    private function involved(Doctor $doctor, Referral $referral): bool
    {
        return (int) $referral->to_doctor_id === (int) $doctor->id
            || (int) $referral->from_doctor_id === (int) $doctor->id;
    }
}
