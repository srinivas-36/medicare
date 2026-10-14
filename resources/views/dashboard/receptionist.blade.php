@extends('layouts.dashboard')

@php
    $appointments = $appointments ?? [];
    $counts = $counts ?? ['today' => 0, 'awaiting' => 0, 'checked_in' => 0, 'completed' => 0];
    $doctorName = $doctorName ?? null;
    $search = $search ?? '';
    $queue = $queue ?? '';

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Checked in' => 'badge-info',
        'Completed' => 'badge-info',
        'Cancelled' => 'badge-danger',
        'No-show' => 'badge-danger',
    ];

    $queueFilters = [
        '' => 'All',
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked in',
        'completed' => 'Completed',
    ];
@endphp

@section('title', 'Front desk')

@section('content')
    @if (! $doctorName)
        <header class="mb-4">
            <h1 class="text-xl font-semibold tracking-tight text-heading">Front desk</h1>
        </header>
        @include('dashboard.receptionist.partials.unassigned')
    @else
        <header class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-sm text-muted">{{ now()->format('l, j F Y') }} · {{ $doctorName }}@if ($specialization) · {{ $specialization }}@endif</p>
                <h1 class="mt-0.5 text-xl font-semibold tracking-tight text-heading sm:text-[22px]">Today’s appointments</h1>
            </div>
            <a href="{{ route('receptionist.appointments.create') }}" class="btn-primary">Book appointment</a>
        </header>

        <form method="GET" action="{{ route('receptionist.dashboard') }}" class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center">
            <label for="queue-search" class="sr-only">Search today’s patients</label>
            <input id="queue-search" type="search" name="q" value="{{ $search }}" placeholder="Search patient" class="field sm:max-w-xs">
            @if ($queue !== '')
                <input type="hidden" name="queue" value="{{ $queue }}">
            @endif
            <button class="btn-primary" type="submit">Search</button>
            <a href="{{ route('receptionist.patients') }}" class="btn-secondary">Find a patient</a>
            <a href="{{ route('receptionist.appointments', ['range' => 'upcoming']) }}" class="btn-secondary">Calendar</a>
        </form>

        <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <x-ui.metric label="Today" :value="$counts['today']" tone="blue" :href="route('receptionist.appointments', ['range' => 'today'])" />
            <x-ui.metric label="Awaiting" :value="$counts['awaiting']" tone="amber" />
            <x-ui.metric label="Checked in" :value="$counts['checked_in']" tone="blue" />
            <x-ui.metric label="Completed" :value="$counts['completed']" tone="green" />
        </div>

        <div class="mb-3 flex gap-2 overflow-x-auto" role="tablist" aria-label="Queue status">
            @foreach ($queueFilters as $value => $label)
                <a
                    href="{{ route('receptionist.dashboard', array_filter(['queue' => $value, 'q' => $search])) }}"
                    role="tab"
                    aria-selected="{{ $queue === $value ? 'true' : 'false' }}"
                    @class([
                        'inline-flex h-9 shrink-0 items-center rounded-lg px-3 text-sm font-semibold',
                        'bg-[#0067FF] text-white' => $queue === $value,
                        'bg-white text-body ring-1 ring-line hover:text-heading' => $queue !== $value,
                    ])
                >{{ $label }}</a>
            @endforeach
        </div>

        <section class="panel">
            @if (count($appointments) === 0)
                <div class="px-4 py-8">
                    <p class="text-sm font-semibold text-heading">{{ $search !== '' || $queue !== '' ? 'No appointments match this queue.' : 'No appointments today.' }}</p>
                    <p class="mt-1 text-sm text-body">Use Book appointment to add a visit, or open the calendar for later days.</p>
                </div>
            @else
                <div class="hidden md:block">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Arrival</th>
                                <th>Status</th>
                                <th class="text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointments as $appointment)
                                @php
                                    $arrived = $appointment['status_value'] === 'checked_in';
                                    $done = in_array($appointment['status_value'], ['completed', 'cancelled', 'no_show'], true);
                                @endphp
                                <tr class="{{ $arrived ? 'bg-[#F0FDF4]' : '' }}">
                                    <td class="whitespace-nowrap font-semibold text-heading">{{ $appointment['time'] }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <x-ui.avatar :name="$appointment['patient']" size="sm" />
                                            <span class="font-medium text-heading">{{ $appointment['patient'] }}</span>
                                        </div>
                                    </td>
                                    <td class="text-body">{{ $appointment['type'] }}</td>
                                    <td class="text-body">{{ $arrived ? 'Arrived' : ($done ? '—' : 'Not arrived') }}</td>
                                    <td><span class="{{ $statusClass[$appointment['status']] ?? 'badge-info' }}">{{ $appointment['status'] }}</span></td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center justify-end gap-3">
                                            @if ($appointment['status_value'] === 'confirmed')
                                                <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment['id']) }}">
                                                    @csrf
                                                    <input type="hidden" name="status" value="checked_in">
                                                    <button type="submit" class="text-sm font-semibold text-primary">Check in</button>
                                                </form>
                                            @endif
                                            <a href="{{ route('receptionist.appointments.show', $appointment['id']) }}" class="text-sm font-semibold text-heading no-underline hover:text-primary">Open</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <ul class="divide-y divide-line md:hidden">
                    @foreach ($appointments as $appointment)
                        @php
                            $arrived = $appointment['status_value'] === 'checked_in';
                            $done = in_array($appointment['status_value'], ['completed', 'cancelled', 'no_show'], true);
                        @endphp
                        <li class="px-4 py-3 {{ $arrived ? 'bg-[#F0FDF4]' : '' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-heading">{{ $appointment['time'] }} · {{ $appointment['patient'] }}</p>
                                    <p class="mt-0.5 truncate text-[13px] text-body">{{ $appointment['type'] }} · {{ $arrived ? 'Arrived' : ($done ? $appointment['status'] : 'Not arrived') }}</p>
                                </div>
                                <span class="{{ $statusClass[$appointment['status']] ?? 'badge-info' }}">{{ $appointment['status'] }}</span>
                            </div>
                            <div class="mt-2 flex gap-4 text-sm font-semibold">
                                @if ($appointment['status_value'] === 'confirmed')
                                    <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment['id']) }}">
                                        @csrf
                                        <input type="hidden" name="status" value="checked_in">
                                        <button type="submit" class="text-primary">Check in</button>
                                    </form>
                                @endif
                                <a href="{{ route('receptionist.appointments.show', $appointment['id']) }}" class="text-heading no-underline">Open</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    @endif
@endsection
