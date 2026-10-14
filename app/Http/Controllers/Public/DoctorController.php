<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Support\DoctorReviews;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index(Request $request)
    {
        $doctors = Doctor::with(['user', 'qualifications', 'timeSlots'])
            ->where('approval_status', 'approved')
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->when($request->filled('query'), function ($query) use ($request) {
                $term = '%'.$request->string('query')->trim().'%';

                $query->where(function ($query) use ($term) {
                    $query->where('specialization', 'like', $term)
                        ->orWhereHas('user', function ($query) use ($term) {
                            $query->where('name', 'like', $term);
                        });
                });
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

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

        return view('public.doctors.show', [
            'doctor' => $doctor,
            'reviewState' => DoctorReviews::page(
                $doctor,
                request()->user(),
                max(1, (int) request()->query('reviews_page', 1)),
            ),
        ]);
    }
}
