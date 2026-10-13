@extends('layouts.dashboard')

@php
    $appointments = $appointments ?? [];
    $range = $range ?? 'today';
    $status = $status ?? '';
    $inputClass = 'h-12 min-w-[160px] rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 text-[#0B1F3A] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Completed' => 'badge-info',
        'Cancelled' => 'badge-danger',
    ];
@endphp

@section('content')
    <section class="dash-banner">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div class="max-w-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Front desk</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Appointments</h1>
                <p class="mt-3 text-sm leading-6 text-white/80">Today’s board is the default. Confirm, complete, or cancel any visit.</p>
            </div>
            <a href="{{ route('receptionist.appointments.create') }}" class="dash-banner-btn">Book a visit</a>
        </div>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ count($appointments) }} listed</span>
            <span class="dash-chip">{{ ucfirst($range) }}</span>
        </div>
    </section>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif

    <form method="GET" action="{{ route('receptionist.appointments') }}" class="mt-6 flex flex-wrap items-end gap-3">
        <div>
            <label for="range" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">When</label>
            <select id="range" name="range" class="{{ $inputClass }}">
                <option value="today" @selected($range === 'today')>Today</option>
                <option value="upcoming" @selected($range === 'upcoming')>Upcoming</option>
                <option value="all" @selected($range === 'all')>All</option>
            </select>
        </div>
        <div>
            <label for="status" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Status</label>
            <select id="status" name="status" class="{{ $inputClass }}">
                <option value="">All statuses</option>
                @foreach (['pending' => 'Pending', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#1769FF] px-6 font-semibold text-white">Filter</button>
    </form>

    <section class="mt-6 overflow-hidden rounded-2xl border border-[#E2EAF4] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-[#F4F7FB] text-[#53647D]">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Patient</th>
                        <th class="px-6 py-3 font-semibold">Doctor</th>
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold">Time</th>
                        <th class="px-6 py-3 font-semibold">Type</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2EAF4]">
                    @forelse ($appointments as $appointment)
                        <tr class="hover:bg-[#F4F7FB]">
                            <td class="px-6 py-4 font-semibold text-[#0B1F3A]">{{ $appointment['patient'] }}</td>
                            <td class="px-6 py-4 text-[#0B1F3A]">{{ $appointment['doctor'] }}</td>
                            <td class="px-6 py-4 text-[#0B1F3A]">{{ $appointment['date'] }}</td>
                            <td class="px-6 py-4 text-[#0B1F3A]">{{ $appointment['time'] }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-[#EAF3FF] px-2.5 py-1 text-xs font-semibold text-[#1769FF]">{{ $appointment['type'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$appointment['status']] ?? 'bg-[#F4F7FB] text-[#53647D]' }}">
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
                                                <button type="submit" class="rounded-lg bg-[#1769FF] px-3 py-1.5 text-xs font-semibold text-white">Confirm</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment['id']) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="rounded-lg bg-[#EAF3FF] px-3 py-1.5 text-xs font-semibold text-[#1769FF]">Complete</button>
                                        </form>
                                        <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment['id']) }}" class="flex items-center gap-2">
                                            @csrf
                                            <input type="hidden" name="status" value="cancelled">
                                            <input type="text" name="cancel_reason" placeholder="Reason" class="h-9 w-28 rounded-lg border border-[#E2EAF4] bg-[#F4F7FB] px-2 text-xs">
                                            <button type="submit" class="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600">Cancel</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-[#53647D]">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-[#53647D]">
                                {{ $range === 'today' && $status === '' ? 'No appointments on the board today.' : 'No appointments match this filter.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
