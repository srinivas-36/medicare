<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\AppointmentReport;
use App\Models\Doctor;
use App\Support\DoctorReviews;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PracticeController extends Controller
{
    public function consultations(Request $request): View
    {
        $doctor = $this->doctor($request);

        $rows = $doctor->appointments()
            ->with(['patient', 'consultation'])
            ->where(function ($query) {
                $query->where('visit_type', 'consultation')->orWhereHas('consultation');
            })
            ->orderByDesc('appointment_date')
            ->orderByDesc('appointment_time')
            ->paginate(12);

        return view('dashboard.doctor.practice.consultations', [
            'rows' => $rows,
        ]);
    }

    public function services(Request $request): View
    {
        $doctor = $this->doctor($request);
        $minutes = max(5, (int) ($doctor->appointment_duration ?: 30));
        $fee = round((float) $doctor->ticket_price, 2);

        return view('dashboard.doctor.practice.services', [
            'specialization' => $doctor->specialization ?: 'Not set',
            'fee' => '₹'.(abs($fee - round($fee)) < 0.001 ? number_format($fee, 0) : number_format($fee, 2)),
            'duration' => $minutes.' min',
            'buffer' => max(0, (int) $doctor->appointment_buffer).' min',
            'visits' => ['Check-up', 'Consultation', 'Follow-up', 'Emergency'],
        ]);
    }

    public function reports(Request $request): View
    {
        $doctor = $this->doctor($request);

        $reports = AppointmentReport::query()
            ->whereHas('appointment', fn ($query) => $query->where('doctor_id', $doctor->id))
            ->with(['appointment.patient'])
            ->latest()
            ->paginate(12);

        return view('dashboard.doctor.practice.reports', [
            'reports' => $reports,
        ]);
    }

    public function reviews(Request $request): View
    {
        $doctor = $this->doctor($request);

        return view('dashboard.doctor.practice.reviews', [
            'reviewState' => DoctorReviews::page($doctor, $request->user()),
        ]);
    }

    private function doctor(Request $request): Doctor
    {
        $doctor = $request->user()->doctor;

        abort_unless($doctor, 404);

        return $doctor;
    }
}
