<?php

namespace App\Http\Controllers\Receptionist;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PatientController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'receptionist', 403);

        $doctor = $request->user()->receptionist?->doctor;
        $search = $request->string('q')->toString();
        $patients = collect();

        if ($doctor && $search !== '') {
            $patients = User::query()
                ->where('role', 'patient')
                ->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                })
                ->orderBy('name')
                ->limit(30)
                ->get();
        }

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
            ->limit(8)
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
}
