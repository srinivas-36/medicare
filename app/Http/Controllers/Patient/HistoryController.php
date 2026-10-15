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
        $status = $request->string('status')->toString();

        if ($status === '' && $request->filled('scope')) {
            $status = match ($request->string('scope')->toString()) {
                'consultations' => 'completed',
                'cancelled' => 'cancelled',
                'upcoming' => 'upcoming',
                default => '',
            };
        }

        if ($doctorId > 0) {
            $query->where('doctor_id', $doctorId);
        }

        if ($visit === 'follow-up') {
            $query->where(function ($query) {
                $query->where('visit_type', 'follow-up')
                    ->orWhereHas('consultation', function ($consultation) {
                        $consultation->where(function ($consultation) {
                            $consultation->whereNotNull('follow_up_on')
                                ->orWhereNotNull('follow_up_instructions');
                        });
                    })
                    ->orWhereExists(function ($scheduled) {
                        $scheduled->selectRaw('1')
                            ->from('consultations as scheduled_follow_ups')
                            ->whereColumn('scheduled_follow_ups.patient_id', 'appointments.patient_id')
                            ->whereColumn('scheduled_follow_ups.doctor_id', 'appointments.doctor_id')
                            ->whereColumn('scheduled_follow_ups.appointment_id', '!=', 'appointments.id')
                            ->whereNotNull('scheduled_follow_ups.follow_up_on')
                            ->whereRaw('date(scheduled_follow_ups.follow_up_on) = date(appointments.appointment_date)');
                    });
            });
        } elseif (in_array($visit, ['check-up', 'consultation', 'emergency'], true)) {
            $query->where('visit_type', $visit);
        }

        if (in_array($status, ['pending', 'confirmed', 'checked_in', 'completed', 'cancelled', 'no_show'], true)) {
            $query->where('status', $status);
        } elseif ($status === 'upcoming') {
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
                'status' => $status,
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
