<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::with('user')
            ->where('approval_status', 'approved')
            ->latest()
            ->paginate(9);

        return view('public.doctors.index', compact('doctors'));
    }

    public function show(string $slug)
    {
        $doctor = Doctor::with([
            'user',
            'experiences',
            'qualifications',
        ])
            ->whereHas('user', function ($query) use ($slug) {
                $query->where('slug', $slug);
            })
            ->firstOrFail();

        return view('public.doctors.show', compact('doctor'));
    }
}
