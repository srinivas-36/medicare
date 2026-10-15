<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Support\AppointmentPage;
use App\Support\AppointmentPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $request->user()->doctor()->firstOrCreate([], [
            'approval_status' => 'pending',
        ]);

        $duration = max(5, (int) ($doctor->appointment_duration ?: 30));

        $todayVisits = $doctor->appointments()
            ->with(['patient', 'consultation'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get();

        $nextModel = $doctor->appointments()
            ->with(['patient', 'consultation'])
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->upcoming()
            ->orderBy('appointment_date')
            ->orderBy('appointment_time')
            ->first();

        $recent = $doctor->appointments()
            ->with(['patient', 'consultation'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(5)
            ->get();

        $openToday = $todayVisits->where('status', '!=', 'cancelled');
        $scheduledFollowUps = $this->scheduledFollowUps($doctor);
        $bookedWithoutDate = $openToday
            ->where('visit_type', 'follow-up')
            ->reject(fn (Appointment $appointment) => $scheduledFollowUps->contains('appointment_id', $appointment->id))
            ->count();
        $nextFollowUp = $scheduledFollowUps->first();

        return view('dashboard.doctor', [
            'doctor' => [
                'isApproved' => $doctor->approval_status,
                'specialization' => $doctor->specialization,
            ],
            'activity' => $this->weekActivity($doctor, today()),
            'next' => $nextModel ? $this->card($nextModel, $duration) : null,
            'recent' => $recent->map(fn (Appointment $appointment) => $this->card($appointment, $duration))->all(),
            'counts' => [
                'today' => $openToday->count(),
                'upcoming' => $doctor->appointments()
                    ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
                    ->whereDate('appointment_date', '>', today())
                    ->count(),
                'completed' => $todayVisits->where('status', 'completed')->count(),
                'pending' => $doctor->appointments()->where('status', 'pending')->count()
                    + $doctor->referralsReceived()->where('status', 'sent')->count(),
                'upcomingHint' => $this->upcomingHint($nextModel),
            ],
            'overview' => [
                'consultations' => $openToday->where('visit_type', 'consultation')->count(),
                'followups' => $scheduledFollowUps->count() + $bookedWithoutDate,
                'followupsHint' => $nextFollowUp
                    ? 'Next '.AppointmentPresenter::date($nextFollowUp->follow_up_on)
                    : ($bookedWithoutDate > 0 ? 'Scheduled for today' : 'None scheduled'),
                'newPatients' => $this->newPatients($doctor, $openToday),
                'revenue' => $this->money($openToday->where('payment_status', 'paid')->sum(fn (Appointment $appointment) => (float) $appointment->ticket_price)),
            ],
        ]);
    }

    /**
     * @return array{range: string, total: int, scheduledFollowUps: int, days: list<array{label: string, count: int, visits: int, followups: int, current: bool}>, mix: list<array{label: string, count: int, color: string}>}
     */
    private function weekActivity(Doctor $doctor, Carbon $day): array
    {
        $start = $day->copy()->startOfWeek();
        $end = $start->copy()->addDays(6);

        $visits = $doctor->appointments()
            ->where('status', '!=', 'cancelled')
            ->whereDate('appointment_date', '>=', $start->toDateString())
            ->whereDate('appointment_date', '<=', $end->toDateString())
            ->get(['id', 'appointment_date', 'visit_type']);

        $scheduled = Consultation::query()
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('follow_up_on')
            ->whereDate('follow_up_on', '>=', today())
            ->get(['appointment_id', 'follow_up_on']);

        $days = [];

        for ($offset = 0; $offset < 7; $offset++) {
            $date = $start->copy()->addDays($offset);
            $visitCount = $visits->filter(fn (Appointment $appointment) => $appointment->appointment_date?->isSameDay($date))->count();
            $followCount = $scheduled->filter(fn (Consultation $consultation) => $consultation->follow_up_on?->isSameDay($date))->count();
            $days[] = [
                'label' => $date->format('D'),
                'visits' => $visitCount,
                'followups' => $followCount,
                'count' => $visitCount + $followCount,
                'current' => $date->isSameDay($day),
            ];
        }

        $mix = [
            'consultation' => ['Consultations', '#0067FF'],
            'follow-up' => ['Follow-ups', '#7C3AED'],
            'check-up' => ['Check-ups', '#16A34A'],
            'emergency' => ['Emergency', '#F59E0B'],
        ];

        $bookedFollowUps = $visits
            ->where('visit_type', 'follow-up')
            ->reject(fn (Appointment $appointment) => $scheduled->contains('appointment_id', $appointment->id))
            ->count();

        return [
            'range' => $start->format('j M').' – '.$end->format('j M'),
            'total' => $visits->count(),
            'scheduledFollowUps' => $scheduled->count(),
            'days' => $days,
            'mix' => collect($mix)->map(fn (array $meta, string $type) => [
                'label' => $meta[0],
                'color' => $meta[1],
                'count' => $type === 'follow-up'
                    ? $scheduled->count() + $bookedFollowUps
                    : $visits->where('visit_type', $type)->count(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function card(Appointment $appointment, int $fallbackMinutes): array
    {
        $presented = AppointmentPresenter::doctor($appointment);
        $presented['minutes'] = $this->minutes($appointment, $fallbackMinutes);
        $presented['follow_up'] = $appointment->consultation?->follow_up_on
            ? AppointmentPresenter::date($appointment->consultation->follow_up_on)
            : null;
        $presented['initials'] = AppointmentPage::initials($presented['patient'], 'P');
        $presented['when'] = Carbon::parse($appointment->appointment_date)->format('j M Y').', '.AppointmentPresenter::time($appointment->appointment_time);
        $presented['day'] = Carbon::parse($appointment->appointment_date)->format('j M Y');
        $presented['day_short'] = Carbon::parse($appointment->appointment_date)->format('j M');

        return $presented;
    }

    private function minutes(Appointment $appointment, int $fallback): int
    {
        if (! filled($appointment->ending_time)) {
            return $fallback;
        }

        $diff = (int) Carbon::parse($appointment->appointment_time)->diffInMinutes(Carbon::parse($appointment->ending_time), true);

        return $diff > 0 ? $diff : $fallback;
    }

    private function upcomingHint(?Appointment $next): string
    {
        if (! $next || ! $next->appointment_date) {
            return 'No upcoming visits';
        }

        if ($next->appointment_date->isToday()) {
            return 'Next visit today';
        }

        if ($next->appointment_date->isTomorrow()) {
            return 'Next visit tomorrow';
        }

        $days = (int) today()->startOfDay()->diffInDays($next->appointment_date->copy()->startOfDay(), true);

        return 'Next visit in '.$days.' '.($days === 1 ? 'day' : 'days');
    }

    /**
     * @return Collection<int, Consultation>
     */
    private function scheduledFollowUps(Doctor $doctor): Collection
    {
        return Consultation::query()
            ->where('doctor_id', $doctor->id)
            ->whereNotNull('follow_up_on')
            ->whereDate('follow_up_on', '>=', today())
            ->orderBy('follow_up_on')
            ->orderBy('id')
            ->get(['appointment_id', 'follow_up_on']);
    }

    /**
     * @param  Collection<int, Appointment>  $openToday
     */
    private function newPatients(Doctor $doctor, Collection $openToday): int
    {
        $ids = $openToday->pluck('patient_id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return 0;
        }

        $returning = $doctor->appointments()
            ->where('status', '!=', 'cancelled')
            ->whereDate('appointment_date', '<', today())
            ->whereIn('patient_id', $ids)
            ->pluck('patient_id')
            ->unique();

        return $ids->diff($returning)->count();
    }

    private function money(float|int|string $amount): string
    {
        $value = round((float) $amount, 2);
        $formatted = abs($value - round($value)) < 0.001
            ? number_format($value, 0)
            : number_format($value, 2);

        return '₹'.$formatted;
    }
}
