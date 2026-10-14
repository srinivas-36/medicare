<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Doctor;
use App\Models\Referral;
use App\Models\User;
use App\Services\AppointmentBooking;
use App\Services\ClinicNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = AppointmentBooking::clinicNow()->toDateString();
        $todayVisits = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->where('status', '!=', 'cancelled')
            ->get(['status']);

        $clinicToday = AppointmentBooking::clinicNow();
        $trendStart = $clinicToday->copy()->subDays(6)->toDateString();
        $trendRows = Appointment::query()
            ->whereDate('appointment_date', '>=', $trendStart)
            ->whereDate('appointment_date', '<=', $today)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('date(appointment_date) as day, count(*) as total')
            ->groupByRaw('date(appointment_date)')
            ->pluck('total', 'day');

        $trend = collect(range(0, 6))->map(function (int $offset) use ($clinicToday, $trendRows) {
            $day = $clinicToday->copy()->subDays(6 - $offset);

            return [
                'label' => $day->format('D'),
                'value' => (int) ($trendRows[$day->toDateString()] ?? 0),
            ];
        })->all();

        return view('dashboard.admin.index', [
            'headline' => [
                ['label' => 'Doctors', 'value' => Doctor::query()->where('approval_status', 'approved')->count(), 'tone' => 'blue', 'href' => route('admin.doctors')],
                ['label' => 'Users', 'value' => User::query()->count(), 'tone' => 'slate', 'href' => route('admin.users')],
                ['label' => 'Pending approvals', 'value' => Doctor::query()->where('approval_status', 'pending')->count(), 'tone' => 'amber', 'href' => route('admin.doctors')],
                ['label' => 'Appointments', 'value' => Appointment::query()->where('status', '!=', 'cancelled')->count(), 'tone' => 'blue'],
            ],
            'openReferrals' => Referral::query()->where('status', 'sent')->count(),
            'trend' => $trend,
            'todayBreakdown' => [
                'Confirmed' => $todayVisits->where('status', 'confirmed')->count(),
                'Checked in' => $todayVisits->where('status', 'checked_in')->count(),
                'Completed' => $todayVisits->where('status', 'completed')->count(),
                'Pending' => $todayVisits->where('status', 'pending')->count(),
            ],
            'pendingDoctors' => Doctor::query()
                ->with('user')
                ->where('approval_status', 'pending')
                ->latest()
                ->limit(5)
                ->get(),
            'recentAccounts' => User::query()
                ->latest()
                ->limit(5)
                ->get(['id', 'name', 'role', 'created_at']),
        ]);
    }

    public function doctors(): View
    {
        $doctors = Doctor::query()
            ->with('user')
            ->orderByRaw("case approval_status when 'pending' then 0 when 'approved' then 1 else 2 end")
            ->orderByDesc('id')
            ->paginate(15);

        return view('dashboard.admin.doctors', [
            'doctors' => $doctors,
        ]);
    }

    public function approve(Request $request, Doctor $doctor, ClinicNotifier $notifier): RedirectResponse
    {
        $doctor->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
        ]);

        if ($doctor->user) {
            $notifier->doctorReviewed($doctor->user, true);
        }

        return back()->with('status', 'Doctor approved.');
    }

    public function reject(Request $request, Doctor $doctor, ClinicNotifier $notifier): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        $doctor->update([
            'approval_status' => 'cancelled',
            'rejection_reason' => $data['rejection_reason'],
        ]);

        if ($doctor->user) {
            $notifier->doctorReviewed($doctor->user, false);
        }

        return back()->with('status', 'Doctor application rejected.');
    }

    public function users(Request $request): View
    {
        $search = $request->string('q')->toString();
        $role = $request->string('role')->toString();
        $status = $request->string('status')->toString();

        if (! in_array($role, ['patient', 'doctor', 'receptionist', 'admin'], true)) {
            $role = '';
        }

        if (! in_array($status, ['active', 'inactive'], true)) {
            $status = '';
        }

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($role !== '', fn ($query) => $query->where('role', $role))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('dashboard.admin.users', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'is_active' => ['required', Rule::in(['0', '1'])],
        ]);

        if ($request->user()->id === $user->id && $data['is_active'] === '0') {
            return back()->withErrors(['is_active' => 'You cannot deactivate your own account.']);
        }

        if ($user->role === 'admin' && $data['is_active'] === '0') {
            $remaining = User::query()->where('role', 'admin')->where('is_active', true)->whereKeyNot($user->id)->count();

            if ($remaining === 0) {
                return back()->withErrors(['is_active' => 'The last active administrator cannot be deactivated.']);
            }
        }

        $user->update(['is_active' => $data['is_active'] === '1']);

        return back()->with('status', 'Account updated.');
    }

    public function settings(): View
    {
        return view('dashboard.admin.settings', [
            'settings' => ClinicSetting::current(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'booking_horizon_months' => ['required', 'integer', 'min:1', 'max:24'],
            'minimum_notice_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'cancellation_notice_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'payment_policy' => ['required', Rule::in(['none', 'clinic'])],
            'timezone' => ['required', 'timezone'],
        ]);

        ClinicSetting::current()->update($data);

        return back()->with('status', 'Clinic settings saved.');
    }
}
