<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\AppointmentPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        $appointments = $doctor->appointments()
            ->with('patient')
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get()
            ->map(fn (Appointment $appointment) => AppointmentPresenter::doctor($appointment))
            ->all();

        $stats = [
            ['label' => "Today's appointments", 'value' => count($appointments), 'note' => 'On today’s schedule'],
            ['label' => 'Pending', 'value' => $doctor->appointments()->where('status', 'pending')->count(), 'note' => 'Awaiting confirmation'],
            ['label' => 'Confirmed', 'value' => $doctor->appointments()->where('status', 'confirmed')->count(), 'note' => 'Accepted visits'],
            [
                'label' => 'Completed this month',
                'value' => $doctor->appointments()
                    ->where('status', 'completed')
                    ->whereMonth('appointment_date', now()->month)
                    ->whereYear('appointment_date', now()->year)
                    ->count(),
                'note' => now()->format('F Y'),
            ],
        ];

        return view('dashboard.doctor', [
            'doctor' => [
                'isApproved' => $doctor->approval_status,
                'specialization' => $doctor->specialization,
            ],
            'stats' => $stats,
            'appointments' => $appointments,
        ]);
    }
}
