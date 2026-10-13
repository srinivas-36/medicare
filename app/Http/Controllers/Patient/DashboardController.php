<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Support\AppointmentPresenter;
use App\Support\PublicCatalog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->role === 'patient', 403);

        $appointments = $user->appointmentsAsPatient()
            ->with('doctor.user')
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->limit(6)
            ->get()
            ->map(fn (Appointment $appointment) => AppointmentPresenter::patient($appointment))
            ->all();

        $upcoming = $user->appointmentsAsPatient()
            ->whereIn('status', ['pending', 'confirmed'])
            ->upcoming()
            ->count();

        $stats = [
            ['label' => 'Upcoming', 'value' => $upcoming, 'note' => 'Appointments scheduled'],
            ['label' => 'Completed', 'value' => $user->appointmentsAsPatient()->where('status', 'completed')->count(), 'note' => 'Visits finished'],
            ['label' => 'Pending', 'value' => $user->appointmentsAsPatient()->where('status', 'pending')->count(), 'note' => 'Awaiting confirmation'],
            [
                'label' => 'Doctors',
                'value' => $user->appointmentsAsPatient()->where('status', 'completed')->pluck('doctor_id')->unique()->count(),
                'note' => 'You have visited',
            ],
        ];

        return view('dashboard.patient', [
            'stats' => $stats,
            'appointments' => $appointments,
            'choices' => PublicCatalog::bookingChoices(),
            'selectedDoctorId' => (int) old('doctor_id', $request->integer('doctor')),
        ]);
    }
}
