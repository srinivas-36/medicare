@extends('layouts.dashboard')

@php
    $appointments = $appointments ?? [];
    $range = $range ?? 'today';
    $status = $status ?? '';
    $doctorName = $doctorName ?? null;

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Checked in' => 'badge-info',
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
                    @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'checked_in' => 'Checked in', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'no_show' => 'No-show'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary">Filter</button>
        </form>

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
                                    @if ($appointment['can_update'])
                                        <div class="flex flex-wrap items-center gap-2">
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
                                        </div>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
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
