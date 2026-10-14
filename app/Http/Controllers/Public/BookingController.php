<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorExperience;
use App\Models\TimeSlot;
use App\Services\AppointmentBooking;
use App\Services\AppointmentReports;
use App\Support\MedicalFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        private AppointmentBooking $booking,
        private AppointmentReports $reports,
    ) {}

    public function create(Request $request, Doctor $doctor): View
    {
        return view('public.doctors.book', $this->formData($request, $doctor));
    }

    /**
     * Keep a guest's booking answers, then send them to sign in.
     */
    public function remember(Request $request, Doctor $doctor): RedirectResponse
    {
        $this->ensureBookable($doctor);

        if ($request->user()?->role === 'patient') {
            return redirect()->route('patient.book.create', $doctor)->withInput();
        }

        if ($request->user() !== null) {
            return back()->withErrors([
                'appointment_date' => 'Only a patient account can confirm this booking.',
            ]);
        }

        $file = ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf', 'max:10240'];
        $input = $request->validate([
            'appointment_date' => ['nullable', 'date'],
            'health_issues' => ['nullable', 'string', 'max:2000'],
            'symptom_duration' => ['nullable', 'string', 'max:80'],
            'current_medicines' => ['nullable', 'string', 'max:500'],
            'slot_day' => ['nullable', 'string', 'max:20'],
            'starting_time' => ['nullable', 'string', 'max:8'],
            'ending_time' => ['nullable', 'string', 'max:8'],
            'blood_report_name' => ['nullable', 'string', 'max:120'],
            'xray_report_name' => ['nullable', 'string', 'max:120'],
            'other_report_name' => ['nullable', 'string', 'max:120'],
            'blood_reports' => ['nullable', 'array', 'max:8'],
            'blood_reports.*' => $file,
            'xray_reports' => ['nullable', 'array', 'max:8'],
            'xray_reports.*' => $file,
            'other_reports' => ['nullable', 'array', 'max:8'],
            'other_reports.*' => $file,
        ]);

        unset($input['blood_reports'], $input['xray_reports'], $input['other_reports']);

        $request->session()->put('booking_draft', [
            'doctor_id' => $doctor->id,
            'input' => $input,
            'files' => $this->stashDraftFiles($request),
        ]);
        $request->session()->put('url.intended', route('doctors.book', $doctor));

        return redirect()->route('login');
    }

    /**
     * @return array{doctor: array<string, mixed>, blocked: mixed}
     */
    public function formData(Request $request, Doctor $doctor): array
    {
        $this->ensureBookable($doctor);
        $this->releaseAbandonedCheckout($request, $doctor);

        if ($request->user()?->role === 'patient') {
            $draft = $request->session()->get('booking_draft');

            if (is_array($draft) && (int) ($draft['doctor_id'] ?? 0) === $doctor->id) {
                $request->session()->put('booking_input', [
                    'doctor_id' => $doctor->id,
                    'input' => is_array($draft['input'] ?? null) ? $draft['input'] : [],
                ]);
                $request->session()->put('booking_files', [
                    'doctor_id' => $doctor->id,
                    'files' => is_array($draft['files'] ?? null) ? $draft['files'] : [],
                ]);
            }

            $request->session()->forget('booking_draft');
        }

        $doctor->load(['user', 'experiences', 'timeSlots']);

        return [
            'doctor' => $this->present($doctor),
            'openSlots' => $this->booking->calendar($doctor),
            'draftReports' => array_map(
                fn (array $file) => (string) ($file['original_name'] ?? 'Report'),
                $this->draftFiles($request, $doctor),
            ),
        ];
    }

    public function store(StorePatientAppointmentRequest $request, Doctor $doctor): RedirectResponse
    {
        $this->ensureBookable($doctor);

        $data = $request->validated();

        try {
            $this->booking->assertWindow(
                $doctor->loadMissing('user'),
                $data['appointment_date'],
                $data['starting_time'],
                $data['ending_time'],
            );
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        $reports = array_merge(
            $this->reports->store($request, $request->user()->id, $data['appointment_date']),
            $this->copyDraftReports($request, $doctor, $request->user()->id, $data['appointment_date']),
        );

        try {
            $appointment = $this->booking->reserveWindow(
                patient: $request->user(),
                doctor: $doctor,
                date: $data['appointment_date'],
                startingTime: $data['starting_time'],
                endingTime: $data['ending_time'],
                reason: $this->reasonFrom($data),
                reports: $reports,
            );
        } catch (ValidationException $exception) {
            $this->reports->discard($reports);

            return back()->withErrors($exception->errors())->withInput();
        } catch (\Throwable $exception) {
            $this->reports->discard($reports);

            throw $exception;
        }

        $this->clearDraftReports($request, $doctor);

        return redirect()->route('checkout.success', $appointment);
    }

    public function success(Request $request, Appointment $appointment): View
    {
        abort_unless($request->user()?->id === $appointment->patient_id, 403);

        $appointment->load(['doctor.user', 'doctor.qualifications']);

        return view('public.checkout.success', [
            'appointment' => $appointment,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function reasonFrom(array $data): ?string
    {
        $parts = [];
        $issues = trim((string) ($data['health_issues'] ?? ''));
        $duration = trim((string) ($data['symptom_duration'] ?? ''));
        $medicines = trim((string) ($data['current_medicines'] ?? ''));

        if ($issues !== '') {
            $parts[] = $issues;
        }

        if ($duration !== '') {
            $parts[] = 'Duration: '.$duration;
        }

        if ($medicines !== '') {
            $parts[] = 'Medicines: '.$medicines;
        }

        return $parts === [] ? null : implode("\n", $parts);
    }

    /**
     * @return list<array{group: string, path: string, original_name: string}>
     */
    private function stashDraftFiles(Request $request): array
    {
        $stored = [];
        $groups = [
            'blood_reports' => ['blood_report_name', 'Blood Test Reports'],
            'xray_reports' => ['xray_report_name', 'X-Ray Scans'],
        ];

        foreach ($groups as $field => [$nameField, $fallback]) {
            $group = trim((string) $request->input($nameField, ''));

            if ($group === '') {
                $group = $fallback;
            }

            foreach ($request->file($field, []) as $file) {
                if ($file instanceof UploadedFile) {
                    $stored[] = $this->keepDraft($request, $file, $group);
                }
            }
        }

        $otherName = trim((string) $request->input('other_report_name', ''));

        if ($otherName === '') {
            $otherName = 'Other reports';
        }

        foreach ($request->file('other_reports', []) as $file) {
            if ($file instanceof UploadedFile) {
                $stored[] = $this->keepDraft($request, $file, $otherName);
            }
        }

        return $stored;
    }

    /**
     * @return array{group: string, path: string, original_name: string}
     */
    private function keepDraft(Request $request, UploadedFile $file, string $group): array
    {
        return [
            'group' => $group,
            'path' => $file->store('booking-drafts/'.$request->session()->getId(), 'local'),
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * @return list<array{group: string, path: string, original_name: string}>
     */
    private function draftFiles(Request $request, Doctor $doctor): array
    {
        $draft = $request->session()->get('booking_files');

        if (! is_array($draft) || (int) ($draft['doctor_id'] ?? 0) !== $doctor->id) {
            return [];
        }

        return array_values(array_filter(
            $draft['files'] ?? [],
            fn ($file) => is_array($file) && filled($file['path'] ?? null),
        ));
    }

    /**
     * @return list<array{group_name: string, path: string, original_name: string}>
     */
    private function copyDraftReports(Request $request, Doctor $doctor, int $patientId, string $date): array
    {
        $copied = [];

        foreach ($this->draftFiles($request, $doctor) as $file) {
            $source = (string) $file['path'];

            if (! Storage::disk('local')->exists($source)) {
                continue;
            }

            $target = 'appointment-reports/'.$patientId.'/'.$date.'/'.basename($source);
            MedicalFile::put($target, (string) Storage::disk('local')->get($source));
            $copied[] = [
                'group_name' => (string) ($file['group'] ?? 'Other reports'),
                'path' => $target,
                'original_name' => (string) ($file['original_name'] ?? 'report'),
            ];
        }

        return $copied;
    }

    private function clearDraftReports(Request $request, Doctor $doctor): void
    {
        foreach ($this->draftFiles($request, $doctor) as $file) {
            Storage::disk('local')->delete((string) $file['path']);
        }

        $request->session()->forget('booking_files');
        $request->session()->forget('booking_input');
    }

    private function ensureBookable(Doctor $doctor): void
    {
        $doctor->loadMissing('user');

        abort_if($doctor->user === null, 404);

        abort_unless(
            $doctor->approval_status === 'approved' && $doctor->user->is_active,
            403,
            'This doctor is not available for booking.'
        );
    }

    private function releaseAbandonedCheckout(Request $request, Doctor $doctor): void
    {
        if ($request->query('checkout') !== 'cancel' || $request->user() === null) {
            return;
        }

        $sessionId = (string) $request->query('session_id');

        if ($sessionId === '') {
            return;
        }

        $appointment = Appointment::query()
            ->where('stripe_session_id', $sessionId)
            ->where('patient_id', $request->user()->id)
            ->where('doctor_id', $doctor->id)
            ->where('payment_status', 'unpaid')
            ->first();

        if ($appointment === null) {
            return;
        }

        $appointment->forceFill([
            'payment_status' => 'failed',
            'status' => 'cancelled',
        ])->save();

        session()->flash('status', 'Payment was cancelled. That time is open again.');
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Doctor $doctor): array
    {
        return [
            'id' => $doctor->id,
            'slug' => $doctor->user?->slug,
            'name' => $doctor->user?->name,
            'specialization' => $doctor->specialization,
            'photo' => $doctor->user?->photo,
            'averageRating' => $doctor->average_rating,
            'totalRating' => $doctor->total_rating,
            'ticketPrice' => $doctor->ticketPrice,
            'about' => $doctor->about,
            'bio' => $doctor->bio,
            'appointmentDuration' => (int) ($doctor->appointment_duration ?? 30),
            'experiences' => $doctor->experiences->map(function (DoctorExperience $experience) {
                return [
                    'startingDate' => $experience->start_date?->toDateString(),
                    'endingDate' => $experience->end_date?->toDateString(),
                    'position' => $experience->designation,
                    'hospital' => $experience->hospital_name,
                    'location' => $experience->location,
                ];
            })->all(),
            'timeSlots' => $doctor->timeSlots->map(function (TimeSlot $slot) {
                return [
                    'day' => strtolower((string) $slot->day),
                    'startingTime' => substr(AppointmentBooking::normalizeTime((string) $slot->starting_time), 0, 5),
                    'endingTime' => substr(AppointmentBooking::normalizeTime((string) $slot->ending_time), 0, 5),
                    'isAvailable' => (bool) $slot->is_available,
                ];
            })->all(),
        ];
    }
}
