<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->role === 'patient', 403);

        $query = $user->appointmentsAsPatient()
            ->with(['doctor.user', 'consultation', 'reports'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time');

        $doctorId = $request->integer('doctor');
        $visit = $request->string('visit')->toString();
        $scope = $request->string('scope')->toString();

        if ($doctorId > 0) {
            $query->where('doctor_id', $doctorId);
        }

        if (in_array($visit, ['check-up', 'consultation', 'follow-up', 'emergency'], true)) {
            $query->where('visit_type', $visit);
        }

        if ($scope === 'consultations') {
            $query->where('status', 'completed')->whereHas('consultation');
        } elseif ($scope === 'cancelled') {
            $query->where('status', 'cancelled');
        } elseif ($scope === 'upcoming') {
            $query->upcoming()->whereNotIn('status', ['cancelled', 'completed', 'no_show']);
        }

        if ($request->filled('from')) {
            $query->whereDate('appointment_date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('appointment_date', '<=', $request->date('to'));
        }

        $doctors = $user->appointmentsAsPatient()
            ->with('doctor.user')
            ->get()
            ->pluck('doctor')
            ->filter()
            ->unique('id')
            ->values();

        return view('dashboard.patient.history', [
            'visits' => $query->paginate(12)->withQueryString(),
            'doctors' => $doctors,
            'filters' => [
                'doctor' => $doctorId,
                'visit' => $visit,
                'scope' => $scope,
                'from' => $request->string('from')->toString(),
                'to' => $request->string('to')->toString(),
            ],
        ]);
    }

    public function show(Request $request, Appointment $appointment): View
    {
        abort_unless($request->user()->id === $appointment->patient_id, 403);

        $appointment->load(['doctor.user', 'consultation', 'reports']);

        return view('dashboard.patient.history-show', [
            'appointment' => $appointment,
        ]);
    }
}
