@extends('layouts.dashboard')

@php
    $fullName = auth()->user()->name ?? 'there';
    $firstName = trim((string) strtok($fullName, ' ')) ?: $fullName;
    $next = $next ?? null;
    $upcoming = $upcoming ?? [];
    $previous = $previous ?? [];
    $updates = $updates ?? [];
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $statusClass = [
        'Confirmed' => 'bg-[#ECFDF3] text-[#15803D]',
        'Pending' => 'bg-[#FFFBEB] text-[#B45309]',
        'At the clinic' => 'bg-[#ECFDF3] text-[#15803D]',
        'Completed' => 'bg-[#E8F2FF] text-[#1D4ED8]',
        'Cancelled' => 'bg-red-50 text-[#B91C1C]',
        'No-show' => 'bg-red-50 text-[#B91C1C]',
    ];
@endphp

@section('title', 'Overview')

@section('content')
    <header class="mb-4 flex flex-wrap items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-sm text-[#64748B]">{{ $greeting }}, {{ now()->format('l, j F') }}</p>
            <h1 class="mt-1 truncate text-[28px] font-bold leading-tight tracking-tight text-[#181A1E]">Welcome back, {{ $firstName }}</h1>
            <p class="mt-1 text-sm text-[#64748B]">Manage your care and upcoming visits in one place.</p>
        </div>
        <a href="{{ route('patient.book') }}" class="btn-primary h-11 px-4">
            <x-ui.icon name="calendar" class="h-4 w-4" />
            Book an appointment
        </a>
    </header>

    <div class="grid grid-cols-1 items-start gap-4 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,0.9fr)] lg:items-start">
        <div class="contents lg:flex lg:flex-col lg:gap-4">
        <section class="overflow-hidden rounded-2xl border border-[#E6EDF5] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]" aria-labelledby="next-heading">
            <div class="flex items-center justify-between gap-3 border-b border-[#EEF2F7] px-5 py-4">
                <h2 id="next-heading" class="flex items-center gap-2 text-[15px] font-semibold text-[#181A1E]">
                    <x-ui.icon name="calendar" class="h-4 w-4 text-[#64748B]" />
                    Next appointment
                </h2>
                @if ($next)
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$next['status']] ?? 'bg-[#E8F2FF] text-[#1D4ED8]' }}">{{ $next['status'] }}</span>
                @endif
            </div>

            @if ($next)
                <div class="px-5 py-4">
                    <div class="flex items-center gap-4">
                        <x-ui.avatar :name="$next['doctor']" :photo="$next['photo'] ?? null" size="xl" />
                        <div class="min-w-0">
                            <p class="truncate text-base font-bold text-[#181A1E]">{{ $next['doctor'] }}</p>
                            <p class="mt-0.5 truncate text-sm text-[#64748B]">
                                {{ $next['specialization'] ?: 'Specialist' }}@if ($next['visit'])<span class="px-1.5 text-[#CBD5E1]">·</span>{{ $next['visit'] }}@endif
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-[#334155]">
                        <span class="inline-flex items-center gap-2">
                            <x-ui.icon name="calendar" class="h-4 w-4 text-[#94A3B8]" />
                            {{ $next['date'] }}
                        </span>
                        <span class="inline-flex items-center gap-2">
                            <x-ui.icon name="clock" class="h-4 w-4 text-[#94A3B8]" />
                            {{ $next['time'] }}
                        </span>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-[#334155]">
                        @if (filled($next['clinic'] ?? null))
                            <span class="inline-flex items-center gap-2">
                                <x-ui.icon name="pin" class="h-4 w-4 text-[#94A3B8]" />
                                {{ $next['clinic'] }}
                            </span>
                        @endif
                        <span class="inline-flex items-center gap-2">
                            <x-ui.icon name="building" class="h-4 w-4 text-[#94A3B8]" />
                            {{ $next['mode'] ?? 'In-person visit' }}
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center gap-2.5">
                        <a href="{{ route('patient.bookings.show', $next['id']) }}" class="btn-primary h-10 px-4">
                            View appointment
                            <x-ui.icon name="chevron-right" class="h-4 w-4" />
                        </a>
                        @if ($next['can_cancel'])
                            <a href="{{ route('patient.bookings.show', $next['id']) }}#reschedule" class="btn-secondary h-10 px-4">
                                <x-ui.icon name="calendar" class="h-4 w-4 text-[#64748B]" />
                                Reschedule
                            </a>
                            <a href="{{ route('patient.bookings.show', $next['id']) }}#cancel" class="inline-flex h-10 items-center gap-2 px-2 text-sm font-semibold text-[#DC2626] no-underline">
                                <x-ui.icon name="trash" class="h-4 w-4" />
                                Cancel
                            </a>
                        @endif
                    </div>
                </div>
            @else
                <div class="px-5 py-4">
                    <p class="text-sm font-semibold text-[#181A1E]">You do not have an upcoming visit.</p>
                    <p class="mt-1 text-sm text-[#64748B]">Find a doctor who is approved and has open hours, then choose a time.</p>
                    <a href="{{ route('patient.book') }}" class="btn-primary mt-4">Find a Doctor</a>
                </div>
            @endif
        </section>

        <section class="order-3 overflow-hidden rounded-2xl border border-[#E6EDF5] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] lg:order-none" aria-labelledby="recent-heading">
            <div class="flex items-center justify-between gap-3 border-b border-[#EEF2F7] px-5 py-4">
                <h2 id="recent-heading" class="flex items-center gap-2 text-[15px] font-semibold text-[#181A1E]">
                    <x-ui.icon name="clock" class="h-4 w-4 text-[#64748B]" />
                    Recent visits
                </h2>
                <a href="{{ route('patient.history') }}" class="text-sm font-semibold text-[#0067FF] no-underline">View medical history</a>
            </div>
            @if ($previous === [])
                <p class="px-5 py-4 text-sm text-[#64748B]">Completed visits will appear here. Cancelled bookings stay with your appointments.</p>
            @else
                <ul class="divide-y divide-[#EEF2F7]">
                    @foreach ($previous as $visit)
                        <li>
                            <a href="{{ route('patient.history.show', $visit['id']) }}" class="flex items-center gap-3 px-5 py-3.5 no-underline">
                                <x-ui.avatar :name="$visit['doctor']" :photo="$visit['photo'] ?? null" size="md" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-[#181A1E]">{{ $visit['doctor'] }}</span>
                                    <span class="mt-0.5 block truncate text-[13px] text-[#64748B]">
                                        {{ $visit['specialization'] ?: 'Specialist' }}@if ($visit['visit'])<span class="px-1 text-[#CBD5E1]">·</span>{{ $visit['visit'] }}@endif
                                    </span>
                                    <span class="mt-2 flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center gap-1.5 text-[13px] text-[#64748B]">
                                            <x-ui.icon name="calendar" class="h-3.5 w-3.5" />
                                            {{ $visit['date'] }}
                                        </span>
                                        @if ($visit['visit'])
                                            <span class="inline-flex items-center rounded-full bg-[#F1F5F9] px-2.5 py-0.5 text-xs font-medium text-[#475569]">{{ $visit['visit'] }}</span>
                                        @endif
                                    </span>
                                </span>
                                <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
        </div>

        <div class="contents lg:flex lg:flex-col lg:gap-4">
        <section class="order-2 overflow-hidden rounded-2xl border border-[#E6EDF5] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] lg:order-none" aria-labelledby="upcoming-heading">
            <div class="flex items-center justify-between gap-3 border-b border-[#EEF2F7] px-5 py-4">
                <h2 id="upcoming-heading" class="flex items-center gap-2 text-[15px] font-semibold text-[#181A1E]">
                    <x-ui.icon name="calendar" class="h-4 w-4 text-[#64748B]" />
                    Upcoming appointments
                </h2>
                <a href="{{ route('patient.bookings') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0067FF] no-underline">
                    View all
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </a>
            </div>
            @if ($upcoming === [])
                <p class="px-5 py-4 text-sm text-[#64748B]">{{ $next ? 'No other visits are scheduled after the next one.' : 'Upcoming visits will appear here after you book.' }}</p>
            @else
                <ul class="divide-y divide-[#EEF2F7]">
                    @foreach ($upcoming as $visit)
                        <li>
                            <a href="{{ route('patient.bookings.show', $visit['id']) }}" class="flex items-start gap-3 px-5 py-3.5 no-underline">
                                <x-ui.avatar :name="$visit['doctor']" :photo="$visit['photo'] ?? null" size="md" class="mt-0.5" />
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-start justify-between gap-3">
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-semibold text-[#181A1E]">{{ $visit['doctor'] }}</span>
                                            <span class="mt-0.5 block truncate text-[13px] text-[#64748B]">
                                                {{ $visit['specialization'] ?: 'Specialist' }}@if ($visit['visit'])<span class="px-1 text-[#CBD5E1]">·</span>{{ $visit['visit'] }}@endif
                                            </span>
                                        </span>
                                        <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$visit['status']] ?? 'bg-[#E8F2FF] text-[#1D4ED8]' }}">{{ $visit['status'] }}</span>
                                    </span>
                                    <span class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[13px] text-[#64748B]">
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-ui.icon name="calendar" class="h-3.5 w-3.5" />
                                            {{ $visit['date'] }}
                                        </span>
                                        <span class="inline-flex items-center gap-1.5">
                                            <x-ui.icon name="clock" class="h-3.5 w-3.5" />
                                            {{ $visit['time'] }}
                                        </span>
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="order-4 overflow-hidden rounded-2xl border border-[#E6EDF5] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)] lg:order-none" aria-labelledby="updates-heading">
            <div class="flex items-center justify-between gap-3 border-b border-[#EEF2F7] px-5 py-4">
                <h2 id="updates-heading" class="flex items-center gap-2 text-[15px] font-semibold text-[#181A1E]">
                    <x-ui.icon name="bell" class="h-4 w-4 text-[#64748B]" />
                    Updates & notifications
                </h2>
                <a href="{{ route('account.notifications') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0067FF] no-underline">
                    View inbox
                    <x-ui.icon name="chevron-right" class="h-4 w-4" />
                </a>
            </div>
            @if ($updates === [])
                <p class="px-5 py-4 text-sm text-[#64748B]">Booking and referral updates will appear here.</p>
            @else
                <ul class="divide-y divide-[#EEF2F7]">
                    @foreach ($updates as $update)
                        <li>
                            <a href="{{ $update['url'] }}" class="flex items-start gap-3 px-5 py-3.5 no-underline">
                                @if ($update['unread'] ?? false)
                                    <span class="mt-3 h-2 w-2 shrink-0 rounded-full bg-[#0067FF]" aria-label="Unread"></span>
                                @else
                                    <span class="mt-3 h-2 w-2 shrink-0" aria-hidden="true"></span>
                                @endif
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[#F4F7FB] text-[#64748B]">
                                    <x-ui.icon name="calendar" class="h-4 w-4" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-start justify-between gap-3">
                                        <span class="truncate text-sm font-semibold text-[#181A1E]">{{ $update['title'] }}</span>
                                        @if (filled($update['when'] ?? null))
                                            <span class="shrink-0 text-xs text-[#94A3B8]">{{ $update['when'] }}</span>
                                        @endif
                                    </span>
                                    @if (($update['body'] ?? '') !== '')
                                        <span class="mt-1 block text-[13px] leading-5 text-[#64748B]">{{ $update['body'] }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
        </div>
    </div>
@endsection
