<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\User;
use App\Support\AppointmentPresenter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $appointments = Appointment::query()
            ->with(['patient', 'doctor.user'])
            ->whereDate('appointment_date', today())
            ->orderBy('appointment_time')
            ->get()
            ->map(fn (Appointment $appointment) => AppointmentPresenter::receptionist($appointment))
            ->all();

        $stats = [
            [
                'label' => 'New patients',
                'value' => User::query()->where('role', 'patient')->whereDate('created_at', today())->count(),
                'note' => 'Registered today',
            ],
            [
                'label' => 'Pending',
                'value' => Appointment::query()->where('status', 'pending')->count(),
                'note' => 'Awaiting confirmation',
            ],
        ];

        return view('dashboard.receptionist', [
            'stats' => $stats,
            'appointments' => $appointments,
        ]);
    }
}
