<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\AppointmentPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->role === 'patient', 403);

        $next = $user->appointmentsAsPatient()
            ->with('doctor.user')
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->upcoming()
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->first();

        $upcoming = $user->appointmentsAsPatient()
            ->with('doctor.user')
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->upcoming()
            ->when($next, fn ($query) => $query->whereKeyNot($next->id))
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->limit(4)
            ->get()
            ->map(fn (Appointment $appointment) => AppointmentPresenter::patient($appointment))
            ->all();

        $previous = $user->appointmentsAsPatient()
            ->with(['doctor.user', 'consultation'])
            ->where('status', 'completed')
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(4)
            ->get()
            ->map(fn (Appointment $appointment) => AppointmentPresenter::patient($appointment))
            ->all();

        $updates = $user->notifications()
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($notification) => [
                'title' => $notification->data['title'] ?? 'Update',
                'body' => $notification->data['body'] ?? '',
                'url' => route('account.notifications.read', $notification->id),
                'when' => $notification->created_at?->diffForHumans(),
            ])
            ->all();

        $referrals = $user->referralsAsPatient()
            ->with('toDoctor.user')
            ->latest()
            ->limit(2)
            ->get()
            ->map(fn ($referral) => [
                'title' => 'Referral to '.($referral->toDoctor?->user?->name ?? 'a doctor'),
                'body' => ucfirst((string) $referral->status),
                'url' => route('patient.history'),
                'when' => $referral->created_at?->diffForHumans(),
            ])
            ->all();

        return view('dashboard.patient', [
            'next' => $next ? AppointmentPresenter::patient($next) : null,
            'upcoming' => $upcoming,
            'previous' => $previous,
            'updates' => array_slice([...$updates, ...$referrals], 0, 4),
        ]);
    }
}
