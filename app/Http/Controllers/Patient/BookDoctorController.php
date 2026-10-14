<?php

namespace App\Http\Controllers\Patient;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Services\AppointmentBooking;
use App\Support\PublicCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookDoctorController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        abort_unless($request->user()->role === 'patient', 403);

        if ($request->filled('doctor') && trim($request->string('q')->toString()) === '' && ! $request->filled('specialty')) {
            $selected = $this->bookableDoctors()->whereKey($request->integer('doctor'))->first();

            if ($selected !== null) {
                return redirect()->route('patient.book.create', $selected);
            }
        }

        $search = trim($request->string('q')->toString());
        $specialty = trim($request->string('specialty')->toString());

        $doctors = $this->bookableDoctors()
            ->with(['user', 'qualifications', 'experiences'])
            ->when($search !== '', function (Builder $query) use ($search) {
                $term = '%'.$search.'%';

                $query->where(function (Builder $query) use ($term) {
                    $query->where('specialization', 'like', $term)
                        ->orWhere('about', 'like', $term)
                        ->orWhereHas('user', function (Builder $query) use ($term) {
                            $query->where('name', 'like', $term);
                        })
                        ->orWhereHas('qualifications', function (Builder $query) use ($term) {
                            $query->where('degree', 'like', $term);
                        });
                });
            })
            ->when($specialty !== '', function (Builder $query) use ($specialty) {
                $query->where('specialization', $specialty);
            })
            ->orderBy('specialization')
            ->orderBy('id')
            ->paginate(12)
            ->withQueryString();

        return view('dashboard.patient.doctors', [
            'doctors' => $doctors,
            'search' => $search,
            'specialty' => $specialty,
            'specialties' => PublicCatalog::specialties(),
        ]);
    }

    public function create(Request $request, Doctor $doctor, AppointmentBooking $booking): View
    {
        abort_unless($request->user()->role === 'patient', 403);

        $doctor->load(['user', 'qualifications', 'experiences', 'timeSlots']);

        abort_if($doctor->user === null, 404);
        abort_unless(
            $doctor->approval_status === 'approved' && $doctor->user->is_active,
            403,
            'This doctor is not available for booking.',
        );

        return view('dashboard.patient.book', [
            'doctor' => $doctor,
            'openSlots' => $booking->calendar($doctor),
            'minDate' => now()->toDateString(),
            'maxDate' => AppointmentBooking::bookableUntil()->toDateString(),
        ]);
    }

    private function bookableDoctors(): Builder
    {
        return Doctor::query()
            ->where('approval_status', 'approved')
            ->whereHas('user', function (Builder $query) {
                $query->where('is_active', true);
            });
    }
}
