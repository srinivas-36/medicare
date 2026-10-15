<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $doctor = $request->user()->receptionist?->doctor?->load('user');
        $search = trim($request->string('q')->toString());
        $patients = $doctor ? $this->patientsFor($doctor, $search) : collect();

        return view('dashboard.receptionist.patients', [
            'doctorName' => $doctor?->user?->name,
            'patients' => $patients,
            'search' => $search,
            'assigned' => (bool) $doctor,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $term = trim($request->string('q')->toString());
        $doctor = $request->user()->receptionist?->doctor;

        if (! $doctor || mb_strlen($term) < 2) {
            return response()->json(['patients' => []]);
        }

        $patients = User::query()
            ->where('role', 'patient')
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', '%'.$term.'%')
                    ->orWhere('email', 'like', '%'.$term.'%')
                    ->orWhere('phone', 'like', '%'.$term.'%');
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'phone']);

        return response()->json([
            'patients' => $patients->map(fn (User $patient) => [
                'id' => $patient->id,
                'name' => $patient->name,
                'email' => $patient->email,
                'phone' => $patient->phone,
            ])->values(),
        ]);
    }

    /**
     * Patients linked to this doctor, or who already have a visit with them.
     *
     * @return Collection<int, User>
     */
    private function patientsFor(Doctor $doctor, string $search): Collection
    {
        $ids = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->distinct()
            ->pluck('patient_id')
            ->merge($doctor->registeredPatients()->pluck('users.id'))
            ->unique()
            ->filter()
            ->values();

        return User::query()
            ->where('role', 'patient')
            ->whereIn('id', $ids)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(function ($query) use ($like) {
                    $query->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('phone', 'like', $like);
                });
            })
            ->orderBy('name')
            ->get();
    }
}
