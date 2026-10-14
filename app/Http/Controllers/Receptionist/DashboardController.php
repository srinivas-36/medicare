<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\AppointmentPresenter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $doctor = $request->user()->receptionist?->doctor;

        if (! $doctor) {
            return view('dashboard.receptionist', [
                'doctorName' => null,
                'specialization' => null,
                'appointments' => [],
                'counts' => [
                    'today' => 0,
                    'awaiting' => 0,
                    'checked_in' => 0,
                    'completed' => 0,
                ],
            ]);
        }

        $doctor->load('user');

        $appointments = Appointment::query()
            ->with(['patient', 'doctor.user'])
            ->where('doctor_id', $doctor->id)
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get()
            ->map(fn (Appointment $appointment) => AppointmentPresenter::receptionist($appointment));

        $active = $appointments->reject(fn (array $appointment) => $appointment['status_value'] === 'cancelled');
        $search = trim($request->string('q')->toString());
        $queue = $request->string('queue')->toString();
        $visible = $appointments;

        if ($search !== '') {
            $needle = mb_strtolower($search);
            $visible = $visible->filter(
                fn (array $appointment) => str_contains(mb_strtolower((string) $appointment['patient']), $needle)
            );
        }

        if (array_key_exists($queue, AppointmentPresenter::STATUS_LABELS)) {
            $visible = $visible->where('status_value', $queue);
        } else {
            $queue = '';
        }

        return view('dashboard.receptionist', [
            'doctorName' => $doctor->user?->name ?? 'Your doctor',
            'specialization' => $doctor->specialization,
            'appointments' => $visible->values()->all(),
            'search' => $search,
            'queue' => $queue,
            'counts' => [
                'today' => $active->count(),
                'awaiting' => $this->countStatus($active, ['pending', 'confirmed']),
                'checked_in' => $this->countStatus($active, ['checked_in']),
                'completed' => $this->countStatus($active, ['completed']),
            ],
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $appointments
     * @param  list<string>  $statuses
     */
    private function countStatus(Collection $appointments, array $statuses): int
    {
        return $appointments->whereIn('status_value', $statuses)->count();
    }
}
