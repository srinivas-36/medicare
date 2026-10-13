<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePatientAppointmentRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorExperience;
use App\Models\TimeSlot;
use App\Services\AppointmentBooking;
use App\Services\StripeCheckout;
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
        private StripeCheckout $stripe,
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

        $input = $request->validate([
            'appointment_date' => ['nullable', 'date'],
            'health_issues' => ['nullable', 'string', 'max:2000'],
            'slot_day' => ['nullable', 'string', 'max:20'],
            'starting_time' => ['nullable', 'string', 'max:8'],
            'ending_time' => ['nullable', 'string', 'max:8'],
            'other_report_name' => ['nullable', 'string', 'max:120'],
        ]);

        $request->session()->put('booking_draft', [
            'doctor_id' => $doctor->id,
            'input' => $input,
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
            $request->session()->forget('booking_draft');
        }

        $doctor->load([
            'user',
            'experiences',
            'timeSlots',
            'appointments' => function ($query) {
                $query->where('status', '!=', 'cancelled')
                    ->whereDate('appointment_date', '>=', today()->toDateString())
                    ->whereDate('appointment_date', '<=', today()->addDays(AppointmentBooking::DAYS - 1)->toDateString());
            },
        ]);

        return [
            'doctor' => $this->present($doctor),
            'blocked' => $this->booking->blockedWindows($doctor),
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

        $reports = $this->storeReports($request, $request->user()->id, $data['appointment_date']);

        try {
            $appointment = $this->booking->reserveWindow(
                patient: $request->user(),
                doctor: $doctor,
                date: $data['appointment_date'],
                startingTime: $data['starting_time'],
                endingTime: $data['ending_time'],
                reason: $data['health_issues'] ?? null,
                reports: $reports,
            );
        } catch (ValidationException $exception) {
            $this->deleteReports($reports);

            return back()->withErrors($exception->errors())->withInput();
        } catch (\Throwable $exception) {
            $this->deleteReports($reports);

            throw $exception;
        }

        if ($appointment->payment_status === 'paid') {
            return redirect()->route('checkout.success', $appointment);
        }

        $appointment->load('patient');

        try {
            $session = $this->stripe->createSession(
                $appointment,
                $doctor->loadMissing('user'),
                route('checkout.success', $appointment).'?session_id={CHECKOUT_SESSION_ID}',
                route('doctors.book', $doctor).'?checkout=cancel&session_id={CHECKOUT_SESSION_ID}',
            );
        } catch (\Throwable) {
            $appointment->forceFill([
                'payment_status' => 'failed',
                'status' => 'cancelled',
            ])->save();

            return back()
                ->withErrors(['appointment_date' => 'Payment could not be started. Please try again.'])
                ->withInput();
        }

        $appointment->forceFill([
            'stripe_session_id' => $session->id,
        ])->save();

        return redirect()->away($session->url);
    }

    public function success(Request $request, Appointment $appointment): View
    {
        abort_unless($request->user()?->id === $appointment->patient_id, 403);

        $appointment->load('doctor.user');

        return view('public.checkout.success', [
            'appointment' => $appointment,
        ]);
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
     * @return list<array{group_name: string, path: string, original_name: string}>
     */
    private function storeReports(Request $request, int $patientId, string $date): array
    {
        $stored = [];

        $groups = [
            'blood_reports' => 'Blood Test Reports',
            'xray_reports' => 'X-Ray Scans',
        ];

        foreach ($groups as $field => $name) {
            foreach ($request->file($field, []) as $file) {
                if ($file instanceof UploadedFile) {
                    $stored[] = $this->keepFile($file, $patientId, $date, $name);
                }
            }
        }

        $otherName = trim((string) $request->input('other_report_name'));

        foreach ($request->file('other_reports', []) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            if ($otherName === '') {
                $this->deleteReports($stored);

                throw ValidationException::withMessages([
                    'other_report_name' => 'Name the other report group before confirming.',
                ]);
            }

            $stored[] = $this->keepFile($file, $patientId, $date, $otherName);
        }

        return $stored;
    }

    /**
     * @return array{group_name: string, path: string, original_name: string}
     */
    private function keepFile(UploadedFile $file, int $patientId, string $date, string $group): array
    {
        return [
            'group_name' => $group,
            'path' => $file->store('appointment-reports/'.$patientId.'/'.$date, 'public'),
            'original_name' => $file->getClientOriginalName(),
        ];
    }

    /**
     * @param  list<array{group_name: string, path: string, original_name: string}>  $reports
     */
    private function deleteReports(array $reports): void
    {
        $paths = array_values(array_filter(array_column($reports, 'path')));

        if ($paths !== []) {
            Storage::disk('public')->delete($paths);
        }
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
            'experiences' => $doctor->experiences->map(function (DoctorExperience $experience) {
                return [
                    'startingDate' => $experience->start_date?->toDateString(),
                    'endingDate' => $experience->end_date?->toDateString(),
                    'position' => $experience->designation,
                    'hospital' => $experience->hospital_name,
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
