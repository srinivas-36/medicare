<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Support\PublicCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookDoctorController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'patient', 403);

        return view('dashboard.patient.doctors', [
            'choices' => PublicCatalog::bookingChoices(),
            'selectedDoctorId' => (int) old('doctor_id', $request->integer('doctor')),
        ]);
    }

    public function create(Request $request, Doctor $doctor): RedirectResponse
    {
        abort_unless($request->user()->role === 'patient', 403);

        return redirect()->route('patient.book', ['doctor' => $doctor->id]);
    }
}
