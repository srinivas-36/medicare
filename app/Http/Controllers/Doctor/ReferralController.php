<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReferralRequest;
use App\Models\Appointment;
use App\Models\AppointmentReport;
use App\Models\Doctor;
use App\Models\Referral;
use App\Services\ReferralSender;
use App\Support\AppointmentPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $this->doctor($request);

        $referrals = $doctor->referralsReceived()
            ->with(['patient', 'fromDoctor.user'])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Referral $referral) => [
                'id' => $referral->id,
                'patient' => $referral->patient?->name ?? 'Patient',
                'from' => $referral->fromDoctor?->user?->name ?? 'Doctor',
                'specialization' => $referral->fromDoctor?->specialization ?? '',
                'sent' => AppointmentPresenter::date($referral->created_at).' · '.AppointmentPresenter::time($referral->created_at),
                'status' => self::label($referral->status),
                'status_value' => $referral->status,
            ])
            ->all();

        return view('dashboard.doctor.referrals.index', [
            'referrals' => $referrals,
        ]);
    }

    public function show(Request $request, Referral $referral): View
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $referral->to_doctor_id === (int) $doctor->id, 403);

        $referral->load(['reports', 'fromDoctor.user', 'patient']);

        return view('dashboard.doctor.referrals.show', [
            'referral' => $referral,
            'snapshot' => $referral->summary_snapshot ?? [],
            'status' => self::label($referral->status),
            'sent' => AppointmentPresenter::date($referral->created_at).' · '.AppointmentPresenter::time($referral->created_at),
        ]);
    }

    public function store(StoreReferralRequest $request, Appointment $appointment, ReferralSender $sender): RedirectResponse
    {
        $doctor = $this->doctor($request);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 403);
        abort_unless(in_array($appointment->status, ['confirmed', 'completed'], true), 403);

        $recipient = Doctor::query()->findOrFail($request->validated('to_doctor_id'));

        $sender->send($appointment, $doctor, $recipient, $request->validated('reason'));

        return redirect()
            ->route('doctor.appointments.show', $appointment)
            ->with('status', 'Referral sent.');
    }

    public function accept(Request $request, Referral $referral): RedirectResponse
    {
        return $this->respond($request, $referral, 'accepted');
    }

    public function decline(Request $request, Referral $referral): RedirectResponse
    {
        return $this->respond($request, $referral, 'declined');
    }

    public function report(Request $request, Referral $referral, AppointmentReport $report): StreamedResponse
    {
        $doctor = $this->doctor($request);

        abort_unless((int) $referral->to_doctor_id === (int) $doctor->id, 403);
        abort_unless($referral->reports()->whereKey($report->id)->exists(), 403);
        abort_unless(Storage::disk('public')->exists($report->path), 404);

        return Storage::disk('public')->response($report->path, $report->original_name);
    }

    private function respond(Request $request, Referral $referral, string $status): RedirectResponse
    {
        $doctor = $this->doctor($request);

        abort_unless((int) $referral->to_doctor_id === (int) $doctor->id, 403);
        abort_unless($referral->status === 'sent', 422);

        $referral->forceFill([
            'status' => $status,
            'responded_at' => now(),
        ])->save();

        return back()->with('status', $status === 'accepted' ? 'Referral accepted.' : 'Referral declined.');
    }

    private function doctor(Request $request): Doctor
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        return $doctor;
    }

    private static function label(string $status): string
    {
        return [
            'sent' => 'Sent',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
        ][$status] ?? ucfirst($status);
    }
}
