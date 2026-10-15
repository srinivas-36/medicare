@extends('layouts.dashboard')

@php
    $user = auth()->user();
    $name = $user->name ?? 'Doctor';
    $displayName = preg_match('/^dr\.?\s/i', $name) ? preg_replace('/^dr\.?\s*/i', 'Dr. ', $name) : 'Dr. '.$name;
    $approval = data_get($doctor ?? $user, 'isApproved');
    $isPending = $approval === 'pending';
    $next = $next ?? null;
    $recent = $recent ?? [];
    $counts = $counts ?? ['today' => 0, 'upcoming' => 0, 'completed' => 0, 'pending' => 0, 'upcomingHint' => 'No upcoming visits'];
    $overview = $overview ?? ['consultations' => 0, 'followups' => 0, 'followupsHint' => 'None scheduled', 'newPatients' => 0, 'revenue' => '₹0'];
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $card = 'rounded-2xl border border-[#E7EEF6] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04),0_10px_28px_rgba(15,23,42,0.04)]';
    $pill = function (string $status): string {
        return match ($status) {
            'Confirmed' => 'bg-[#E7F8EE] text-[#16A34A]',
            'Pending' => 'bg-[#FFF6E8] text-[#D97706]',
            'At the clinic' => 'bg-[#ECFDF3] text-[#15803D]',
            'Completed' => 'bg-[#E8F2FF] text-[#1D4ED8]',
            'Cancelled', 'No-show' => 'bg-[#FEF2F2] text-[#DC2626]',
            default => 'bg-[#F4F7FB] text-[#64748B]',
        };
    };
    $stats = [
        ['label' => "Today's visits", 'value' => $counts['today'], 'hint' => 'Scheduled for today', 'icon' => 'calendar', 'chip' => 'bg-[#E8F1FF] text-[#0067FF]', 'href' => route('doctor.appointments', ['date' => now()->toDateString()])],
        ['label' => 'Upcoming', 'value' => $counts['upcoming'], 'hint' => $counts['upcomingHint'], 'icon' => 'clock', 'chip' => 'bg-[#FFF4E8] text-[#F59E0B]', 'href' => route('doctor.appointments')],
        ['label' => 'Completed today', 'value' => $counts['completed'], 'hint' => 'Visits completed', 'icon' => 'check', 'chip' => 'bg-[#E8F8EF] text-[#22C55E]', 'href' => route('doctor.appointments', ['tab' => 'completed', 'date' => now()->toDateString()])],
        ['label' => 'Pending requests', 'value' => $counts['pending'], 'hint' => 'Awaiting your decision', 'icon' => 'user', 'chip' => 'bg-[#FDECEC] text-[#EF4444]', 'href' => route('doctor.appointments', ['status' => 'pending'])],
    ];
    $actions = [
        ['label' => 'Availability', 'hint' => 'Manage your slots', 'icon' => 'calendar', 'href' => route('doctor.availability'), 'box' => 'bg-[#ECFDF3]', 'ink' => 'text-[#16A34A]'],
        ['label' => 'Patients', 'hint' => 'View all patients', 'icon' => 'users', 'href' => route('doctor.patients'), 'box' => 'bg-[#EFF6FF]', 'ink' => 'text-[#2563EB]'],
        ['label' => 'Referrals', 'hint' => 'Manage referrals', 'icon' => 'users', 'href' => route('doctor.referrals'), 'box' => 'bg-[#F5F3FF]', 'ink' => 'text-[#7C3AED]'],
        ['label' => 'Edit Profile', 'hint' => 'Update your details', 'icon' => 'user', 'href' => route('doctor.profile.edit'), 'box' => 'bg-[#FFF7ED]', 'ink' => 'text-[#F59E0B]'],
    ];
@endphp

@section('title', 'Dashboard')

@section('content')
    @if ($isPending)
        <p class="mb-4 rounded-xl border border-[#FDE68A] bg-[#FFFBEB] px-4 py-3 text-sm text-[#B45309]">
            Your profile is waiting for approval.
            <a href="{{ route('doctor.profile.edit') }}" class="font-semibold text-[#181A1E] underline">Complete it</a>
        </p>
    @endif

    <section class="relative overflow-hidden rounded-2xl border border-[#D7E6FF] bg-[linear-gradient(100deg,#EAF3FF_0%,#F5F9FF_48%,#E7F2FF_100%)] px-5 py-6 sm:px-7 sm:py-7">
        <div class="relative z-10 max-w-[520px]">
            <h1 class="text-[26px] font-bold tracking-tight text-[#0067FF] sm:text-[30px]">{{ $greeting }}, {{ $displayName }}</h1>
            <p class="mt-1.5 text-sm text-[#64748B]">Here's your practice at a glance.</p>
        </div>
        <div class="pointer-events-none absolute inset-y-0 right-3 hidden items-center lg:flex">
            <svg viewBox="0 0 250 150" class="h-[132px] w-[230px]" aria-hidden="true">
                <ellipse cx="78" cy="132" rx="46" ry="8" fill="#D7E6FF" />
                <path d="M46 118c8-28 18-46 34-46 10 0 16 8 22 20 6-14 14-24 28-22 16 2 22 22 24 40H46Z" fill="#FFFFFF" />
                <path d="M78 74c-2 10 2 18 10 22" stroke="#BFDBFE" stroke-width="2" fill="none" stroke-linecap="round" />
                <circle cx="92" cy="48" r="16" fill="#F3D5BF" />
                <path d="M78 46c2-14 28-16 32-2-6-8-22-10-32 2Z" fill="#1E293B" />
                <path d="M108 58c6 4 8 12 4 18" stroke="#0F766E" stroke-width="2" fill="none" stroke-linecap="round" />
                <rect x="118" y="78" width="36" height="26" rx="4" fill="#0067FF" />
                <rect x="122" y="82" width="28" height="14" rx="2" fill="#E0ECFF" />
                <path d="M168 118c0-22 10-40 24-40s22 16 22 36" fill="#BBF7D0" />
                <rect x="186" y="86" width="6" height="36" rx="3" fill="#15803D" />
                <circle cx="40" cy="96" r="14" fill="#BBF7D0" />
                <rect x="37" y="96" width="6" height="24" rx="3" fill="#15803D" />
                <rect x="198" y="28" width="36" height="28" rx="6" fill="#FFFFFF" opacity=".8" />
                <path d="M206 40h20M206 46h12" stroke="#BFDBFE" stroke-width="2" stroke-linecap="round" />
            </svg>
            <p class="mb-6 max-w-[7.5rem] text-[13px] italic leading-5 text-[#64748B]">“Better care brighter tomorrows”</p>
        </div>
    </section>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            <a href="{{ $stat['href'] }}" class="{{ $card }} flex items-center gap-3 px-4 py-4 no-underline transition hover:border-[#C5D9FF]">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $stat['chip'] }}">
                    <x-ui.icon :name="$stat['icon']" class="h-5 w-5" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-[13px] font-medium text-[#64748B]">{{ $stat['label'] }}</span>
                    <span class="mt-1 block text-[28px] font-bold leading-none tracking-tight text-[#0F172A]">{{ $stat['value'] }}</span>
                    <span class="mt-1.5 block truncate text-xs text-[#94A3B8]">{{ $stat['hint'] }}</span>
                </span>
                <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 text-[#CBD5E1]" />
            </a>
        @endforeach
    </div>

    <div class="mt-4 grid items-stretch gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,0.85fr)]">
        <section class="{{ $card }} flex h-full flex-col">
            <div class="flex shrink-0 items-start gap-2.5 px-5 py-4">
                <x-ui.icon name="chart" class="mt-0.5 h-5 w-5 text-[#0067FF]" />
                <div>
                    <h2 class="text-[15px] font-semibold text-[#0F172A]">{{ "Today's Schedule" }}</h2>
                    <p class="text-xs text-[#94A3B8]">Appointments across the week</p>
                </div>
            </div>

            @php
                $activity = $activity ?? ['range' => '', 'total' => 0, 'days' => [], 'mix' => []];
                $peak = max(1, (int) collect($activity['days'])->max('count'));
                $mixPeak = max(1, (int) collect($activity['mix'])->max('count'));
            @endphp
            <div class="flex min-h-[16rem] flex-1 flex-col border-t border-[#F1F5F9] px-5 py-5 xl:min-h-0">
                <div class="grid h-full min-h-0 flex-1 gap-6 lg:grid-cols-[minmax(0,1.2fr)_minmax(180px,0.8fr)]">
                    <div class="flex min-h-0 flex-col">
                        <div class="flex items-baseline justify-between gap-3">
                            <p class="text-sm font-semibold text-[#0F172A]">Visits this week</p>
                            <p class="text-xs text-[#94A3B8]">{{ $activity['range'] }}</p>
                        </div>
                        <div class="mt-4 flex min-h-[9rem] flex-1 items-end gap-2">
                            @foreach ($activity['days'] as $bar)
                                @php
                                    $visitCount = (int) ($bar['visits'] ?? $bar['count']);
                                    $followCount = (int) ($bar['followups'] ?? 0);
                                    $barHeight = $bar['count'] === 0 ? 8 : max(18, (int) round(($bar['count'] / $peak) * 100));
                                    $tip = $visitCount.' '.($visitCount === 1 ? 'visit' : 'visits');
                                    if ($followCount > 0) {
                                        $tip .= ' · '.$followCount.' '.($followCount === 1 ? 'follow-up' : 'follow-ups');
                                    }
                                @endphp
                                <div class="group flex h-full min-w-0 flex-1 flex-col items-center gap-2">
                                    <div class="relative w-full flex-1">
                                        <div
                                            class="absolute inset-x-0 bottom-0 mx-auto flex w-full max-w-[2rem] flex-col justify-end overflow-hidden rounded-t-md"
                                            style="height: {{ $barHeight }}%"
                                        >
                                            @if ($followCount > 0)
                                                <div class="w-full bg-[#7C3AED]" style="flex: {{ $followCount }} 1 0"></div>
                                            @endif
                                            @if ($visitCount > 0 || $followCount === 0)
                                                <div class="w-full {{ $bar['current'] ? 'bg-[#0067FF]' : 'bg-[#D6E4FF]' }}" style="flex: {{ max($visitCount, 1) }} 1 0"></div>
                                            @endif
                                            <span class="pointer-events-none absolute bottom-full left-1/2 mb-1.5 hidden -translate-x-1/2 whitespace-nowrap rounded-md bg-[#0F172A] px-2 py-1 text-[11px] font-semibold text-white group-hover:block">{{ $tip }}</span>
                                        </div>
                                    </div>
                                    <span class="shrink-0 text-[11px] font-medium {{ $bar['current'] ? 'text-[#0067FF]' : 'text-[#94A3B8]' }}">{{ $bar['label'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-3 shrink-0 text-xs text-[#64748B]">{{ $activity['total'] }} {{ $activity['total'] === 1 ? 'visit' : 'visits' }} booked from {{ $activity['range'] }}. @if (($activity['scheduledFollowUps'] ?? 0) > 0){{ $activity['scheduledFollowUps'] }} scheduled {{ ($activity['scheduledFollowUps'] ?? 0) === 1 ? 'follow-up is' : 'follow-ups are' }} included.@endif</p>
                    </div>
                    <div class="flex flex-col justify-center">
                        <p class="text-sm font-semibold text-[#0F172A]">Visit mix</p>
                        <ul class="mt-4 space-y-3">
                            @foreach ($activity['mix'] as $item)
                                <li data-mix="{{ $item['label'] }}" data-count="{{ $item['count'] }}">
                                    <div class="flex items-center justify-between gap-3 text-xs">
                                        <span class="font-medium text-[#475569]">{{ $item['label'] }}</span>
                                        <span class="font-semibold text-[#0F172A]">{{ $item['count'] }}</span>
                                    </div>
                                    <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-[#F1F5F9]">
                                        <div class="h-full rounded-full" style="width: {{ $item['count'] === 0 ? 0 : max(8, (int) round(($item['count'] / $mixPeak) * 100)) }}%; background: {{ $item['color'] }}"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <div class="flex flex-col gap-4">
            <section class="{{ $card }} p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <h2 class="text-[15px] font-semibold text-[#0F172A]">Next Appointment</h2>
                    <a href="{{ route('doctor.appointments') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0067FF] no-underline hover:text-[#0052CC]">
                        View all
                        <x-ui.icon name="arrow" class="h-3.5 w-3.5" />
                    </a>
                </div>
                @if ($next)
                    <div class="mt-4 rounded-2xl border border-[#DCE8FF] bg-[#F4F8FF] p-4">
                        <div class="flex items-start gap-3">
                            <x-ui.avatar :name="$next['patient']" tone="violet" size="lg" />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-[#0F172A]">{{ $next['patient'] }}</p>
                                <p class="truncate text-[13px] text-[#64748B]">{{ $next['visit'] }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $pill($next['status']) }}">{{ $next['status'] }}</span>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-sm text-[#181A1E]">
                            <p class="inline-flex items-center gap-1.5">
                                <x-ui.icon name="calendar" class="h-4 w-4 text-[#64748B]" />
                                {{ $next['day'] }}
                            </p>
                            <p class="inline-flex items-center gap-1.5">
                                <x-ui.icon name="clock" class="h-4 w-4 text-[#64748B]" />
                                {{ $next['time'] }}
                            </p>
                            <p class="inline-flex items-center gap-1.5">
                                <x-ui.icon name="clock" class="h-4 w-4 text-[#64748B]" />
                                {{ $next['minutes'] }} min
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('doctor.appointments.show', $next['id']) }}" class="mt-4 flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#0067FF] text-sm font-semibold text-white no-underline transition hover:bg-[#0052CC]">
                        Open Appointment Details
                        <x-ui.icon name="arrow" class="h-4 w-4" />
                    </a>
                @else
                    <p class="mt-4 rounded-2xl border border-dashed border-[#E7EEF6] bg-[#F8FAFC] px-4 py-8 text-center text-sm text-[#64748B]">No upcoming visit is on the calendar.</p>
                @endif
            </section>

            <section class="{{ $card }} p-4 sm:p-5">
                <div class="flex items-start gap-2.5">
                    <x-ui.icon name="bolt" class="mt-0.5 h-5 w-5 text-[#0067FF]" />
                    <div>
                        <h2 class="text-[15px] font-semibold text-[#0F172A]">Quick Actions</h2>
                        <p class="text-xs text-[#94A3B8]">Common actions for your practice</p>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    @foreach ($actions as $action)
                        <a href="{{ $action['href'] }}" class="flex items-center gap-3 rounded-xl px-3 py-3 no-underline transition hover:brightness-[0.98] {{ $action['box'] }}">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/80 {{ $action['ink'] }}">
                                <x-ui.icon :name="$action['icon']" class="h-4 w-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold text-[#0F172A]">{{ $action['label'] }}</span>
                                <span class="block truncate text-[11px] text-[#64748B]">{{ $action['hint'] }}</span>
                            </span>
                            <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 opacity-60" />
                        </a>
                    @endforeach
                </div>
            </section>
        </div>
    </div>

    <div class="mt-4 grid items-start gap-4 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,0.85fr)]">
        <section class="{{ $card }}">
            <div class="flex items-center justify-between gap-3 px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <x-ui.icon name="clock" class="h-5 w-5 text-[#0067FF]" />
                    <h2 class="text-[15px] font-semibold text-[#0F172A]">Recent Appointments</h2>
                </div>
                <a href="{{ route('doctor.appointments') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0067FF] no-underline hover:text-[#0052CC]">
                    View all
                    <x-ui.icon name="arrow" class="h-3.5 w-3.5" />
                </a>
            </div>
            @if ($recent === [])
                <p class="border-t border-[#F1F5F9] px-5 py-8 text-sm text-[#64748B]">No appointments yet.</p>
            @else
                <div class="overflow-x-auto border-t border-[#F1F5F9]">
                    <table class="w-full min-w-[640px] text-left text-sm">
                        <thead>
                            <tr class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[#94A3B8]">
                                <th class="px-5 py-3 font-semibold">Patient</th>
                                <th class="px-3 py-3 font-semibold">Date &amp; time</th>
                                <th class="px-3 py-3 font-semibold">Type</th>
                                <th class="px-3 py-3 font-semibold">Status</th>
                                <th class="px-4 py-3 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#F1F5F9]">
                            @foreach ($recent as $row)
                                <tr>
                                    <td class="px-5 py-3">
                                        <a href="{{ route('doctor.appointments.show', $row['id']) }}" class="flex items-center gap-2.5 no-underline">
                                            <x-ui.avatar :name="$row['patient']" tone="violet" size="sm" />
                                            <span class="truncate font-semibold text-[#0F172A]">{{ $row['patient'] }}</span>
                                        </a>
                                    </td>
                                    <td class="px-3 py-3 text-[#475569]">{{ $row['when'] }}</td>
                                    <td class="px-3 py-3 text-[#475569]">
                                        <span class="block">{{ $row['visit'] }}</span>
                                        @if (filled($row['follow_up'] ?? null))
                                            <span class="mt-1 block text-xs font-medium text-[#7C3AED]">Follow-up {{ $row['follow_up'] }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $pill($row['status']) }}">{{ $row['status'] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="relative inline-block" x-data="{ open: false }" @keydown.escape.window="open = false">
                                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#64748B] hover:bg-[#F8FAFC]" @click="open = !open" aria-label="Appointment actions">
                                                <x-ui.icon name="dots" class="h-4 w-4" />
                                            </button>
                                            <div x-show="open" x-cloak @click.outside="open = false" class="absolute right-0 z-20 mt-1 w-40 overflow-hidden rounded-xl border border-[#E7EEF6] bg-white py-1 text-left shadow-lift">
                                                <a href="{{ route('doctor.appointments.show', $row['id']) }}" class="block px-3 py-2 text-sm font-medium text-[#181A1E] no-underline hover:bg-[#F8FAFC]">View details</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="{{ $card }} p-4 sm:p-5">
            <div class="flex items-center gap-2.5">
                <x-ui.icon name="chart" class="h-5 w-5 text-[#0067FF]" />
                <h2 class="text-[15px] font-semibold text-[#0F172A]">Today's Overview</h2>
            </div>
            <div class="mt-5 grid grid-cols-2 gap-x-3 gap-y-5 sm:grid-cols-4">
                <div>
                    <p class="text-xs font-medium text-[#64748B]">Consultations</p>
                    <p class="mt-2 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#F3E8FF] text-[#7C3AED]">
                            <x-ui.icon name="stethoscope" class="h-4 w-4" />
                        </span>
                        <span class="text-2xl font-bold text-[#0F172A]">{{ $overview['consultations'] }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-[#64748B]">Follow-ups</p>
                    <p class="mt-2 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#E8F1FF] text-[#0067FF]">
                            <x-ui.icon name="refresh" class="h-4 w-4" />
                        </span>
                        <span class="text-2xl font-bold text-[#0F172A]">{{ $overview['followups'] }}</span>
                    </p>
                    <p class="mt-1 text-[11px] text-[#94A3B8]">{{ $overview['followupsHint'] ?? 'None scheduled' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium text-[#64748B]">New Patients</p>
                    <p class="mt-2 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#E8F8EF] text-[#16A34A]">
                            <x-ui.icon name="user" class="h-4 w-4" />
                        </span>
                        <span class="text-2xl font-bold text-[#0F172A]">{{ $overview['newPatients'] }}</span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-[#64748B]">Revenue</p>
                    <p class="mt-2 flex items-center gap-2">
                        <span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#FFF4E8] text-[#F59E0B]">
                            <x-ui.icon name="card" class="h-4 w-4" />
                        </span>
                        <span class="text-2xl font-bold text-[#0F172A]">{{ $overview['revenue'] }}</span>
                    </p>
                    <p class="mt-1 text-[11px] text-[#94A3B8]">Today</p>
                </div>
            </div>
        </section>
    </div>
@endsection
