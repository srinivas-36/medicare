@extends('layouts.dashboard')

@php
    $appointments = $appointments ?? [];
    $calendar = $calendar ?? null;
    $range = $range ?? 'today';
    $status = $status ?? '';
    $doctorName = $doctorName ?? null;
    $search = $search ?? '';

    $calendarQuery = function (?string $month = null, ?string $date = null) use ($range, $status, $search) {
        return route('receptionist.appointments', array_filter([
            'range' => $range,
            'status' => $status,
            'q' => $search,
            'month' => $month,
            'date' => $date,
        ], fn ($value) => $value !== null && $value !== ''));
    };

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'At the clinic' => 'badge-success',
        'Completed' => 'badge-info',
        'Cancelled' => 'badge-danger',
        'No-show' => 'badge-danger',
    ];
@endphp

@section('title', 'Appointments')

@section('content')
    <x-ui.page-header
        :title="$range === 'upcoming' ? 'Calendar' : ($range === 'today' ? 'Today’s appointments' : 'Appointments')"
        :description="$doctorName ? $doctorName.($specialization ? ' · '.$specialization : '') : 'This desk is not linked to a doctor yet.'"
    >
        @if ($doctorName)
            <a href="{{ route('receptionist.appointments.create') }}" class="btn-primary">Book Appointment</a>
        @endif
    </x-ui.page-header>

    @if (! $doctorName)
        @include('dashboard.receptionist.partials.unassigned')
    @else
        @if (session('status'))
            <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif

        <form method="GET" action="{{ route('receptionist.appointments') }}" class="mb-4 flex flex-wrap items-end gap-3">
            <div class="w-full sm:max-w-xs sm:flex-1">
                <label for="appointment-search" class="label">Patient</label>
                <input id="appointment-search" type="search" name="q" value="{{ $search ?? '' }}" placeholder="Name, email, or phone" class="field">
            </div>
            <div class="w-full sm:w-48">
                <label for="range" class="label">When</label>
                <select id="range" name="range" class="field">
                    <option value="today" @selected($range === 'today')>Today</option>
                    <option value="upcoming" @selected($range === 'upcoming')>Upcoming</option>
                    <option value="all" @selected($range === 'all')>All</option>
                </select>
            </div>
            <div class="w-full sm:w-52">
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="field">
                    <option value="">All statuses</option>
                    @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'checked_in' => 'At the clinic', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">Filter</button>
        </form>

        @if ($calendar)
            <section class="dash-panel mt-6 p-4 sm:p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-heading">Calendar</h2>
                        <p class="mt-1 text-sm text-body">Green days already have a booking. Use View to check the patient in or move the visit to a later day.</p>
                    </div>
                    <p class="inline-flex items-center gap-2 text-xs font-medium text-body">
                        <span class="inline-block h-3 w-3 rounded bg-[#16A34A]" aria-hidden="true"></span>
                        Existing booking
                    </p>
                </div>

                <div class="mt-4 overflow-x-auto">
                    <div class="min-w-[720px]">
                        <div class="mb-3 flex items-center justify-between">
                            <a href="{{ $calendarQuery($calendar['prevMonth']) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-body hover:bg-page" aria-label="Previous month">
                                <x-ui.icon name="chevron" class="h-4 w-4 rotate-90" />
                            </a>
                            <p class="text-sm font-semibold text-navy">{{ $calendar['label'] }}</p>
                            <a href="{{ $calendarQuery($calendar['nextMonth']) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-body hover:bg-page" aria-label="Next month">
                                <x-ui.icon name="chevron" class="h-4 w-4 -rotate-90" />
                            </a>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold uppercase text-muted">
                            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>
                        <div class="mt-1 grid grid-cols-7 gap-1">
                            @foreach ($calendar['weeks'] as $week)
                                @foreach ($week as $day)
                                    @if (! $day)
                                        <div class="min-h-[92px] rounded-lg bg-page/60"></div>
                                    @else
                                        @php
                                            $booked = $day['count'] > 0;
                                            $dayUrl = $calendarQuery($calendar['month'], $day['selected'] ? null : $day['date']);
                                        @endphp
                                        <div @class([
                                            'flex min-h-[92px] flex-col rounded-lg border p-1.5',
                                            'border-[#16A34A] bg-[#F0FDF4]' => $booked,
                                            'border-line bg-white' => ! $booked,
                                            'ring-2 ring-[#16A34A]' => $day['selected'],
                                        ])>
                                            <a href="{{ $dayUrl }}" class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold no-underline {{ $booked ? 'bg-[#16A34A] text-white' : ($day['today'] ? 'text-primary ring-1 ring-primary' : 'text-navy hover:bg-page') }}">
                                                {{ $day['day'] }}
                                            </a>
                                            <div class="mt-1 space-y-1">
                                                @foreach (array_slice($day['items'], 0, 2) as $item)
                                                    <a href="{{ route('receptionist.appointments.show', $item['id']) }}" class="block truncate rounded bg-[#16A34A] px-1.5 py-1 text-left text-[11px] font-semibold text-white no-underline hover:bg-[#15803D]" title="{{ $item['patient'] }} · {{ $item['time'] }}">
                                                        View · {{ $item['patient'] }}
                                                    </a>
                                                @endforeach
                                                @if ($day['count'] > 2)
                                                    <a href="{{ $dayUrl }}" class="block truncate px-1 text-[11px] font-semibold text-[#15803D] no-underline">+{{ $day['count'] - 2 }} more</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                </div>

                @if ($calendar['selectedLabel'])
                    <p class="mt-3 text-sm text-body">
                        Showing {{ $calendar['selectedLabel'] }}.
                        <a href="{{ $calendarQuery($calendar['month']) }}" class="font-semibold text-primary no-underline">Show the full list</a>
                    </p>
                @endif
            </section>
        @endif

        <section class="dash-panel mt-6">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-left text-sm">
                    <thead class="bg-page text-text">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Patient</th>
                            <th class="px-6 py-3 font-semibold">Date</th>
                            <th class="px-6 py-3 font-semibold">Time</th>
                            <th class="px-6 py-3 font-semibold">Type</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                            <th class="px-6 py-3 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($appointments as $appointment)
                            <tr @class(['hover:bg-page', 'bg-[#F0FDF4]' => $appointment['status_value'] === 'checked_in'])>
                                <td class="px-6 py-4 font-semibold text-navy">{{ $appointment['patient'] }}</td>
                                <td class="px-6 py-4 text-navy">{{ $appointment['date'] }}</td>
                                <td class="px-6 py-4 text-navy">{{ $appointment['time'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="badge-info">{{ $appointment['type'] }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="{{ $statusClass[$appointment['status']] ?? 'badge-info' }}">
                                        {{ $appointment['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <a href="{{ route('receptionist.appointments.show', $appointment['id']) }}" class="inline-flex h-9 items-center rounded-lg bg-[#16A34A] px-3 text-xs font-semibold text-white no-underline hover:bg-[#15803D]">View</a>
                                        @if ($appointment['can_update'])
                                            @if ($appointment['status_value'] === 'pending')
                                                <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment['id']) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-primary px-3 text-xs font-semibold text-white">Confirm</button>
                                                </form>
                                            @endif
                                            @if ($appointment['status_value'] === 'confirmed')
                                                <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment['id']) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="checked_in">
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-light px-3 text-xs font-semibold text-primary">Check in</button>
                                                </form>
                                            @endif
                                            @if (in_array($appointment['status_value'], ['confirmed', 'checked_in'], true))
                                                <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment['id']) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="no_show">
                                                    <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-light px-3 text-xs font-semibold text-navy">No-show</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment['id']) }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="hidden" name="status" value="cancelled">
                                                <input type="text" name="cancel_reason" placeholder="Reason" aria-label="Cancellation reason" class="h-9 w-28 rounded-lg border border-line bg-white px-2 text-xs text-navy">
                                                <button type="submit" class="inline-flex h-9 items-center rounded-lg bg-red-50 px-3 text-xs font-semibold text-danger">Cancel</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-sm text-body">
                                    @if ($range === 'today' && $status === '')
                                        <p class="font-semibold text-heading">No appointments today</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <a href="{{ route('receptionist.appointments.create') }}" class="btn-primary">Book Appointment</a>
                                            <a href="{{ route('receptionist.appointments', ['range' => 'upcoming']) }}" class="btn-secondary">Open Calendar</a>
                                        </div>
                                    @else
                                        No appointments match this filter.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
