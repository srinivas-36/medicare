@extends('layouts.dashboard')

@php
    $user = auth()->user();
    $name = $user->name ?? 'there';
    $stats = $stats ?? [];
    $appointments = $appointments ?? [];

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Completed' => 'badge-info',
        'Cancelled' => 'badge-danger',
    ];
@endphp

@section('title', 'Reception dashboard')

@section('content')
    <section class="dash-banner">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Reception dashboard</p>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-5">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Welcome back, {{ $name }}</h1>
                <p class="mt-3 text-sm leading-6 text-white/80">
                    Today’s front desk: who is expected, which doctor they are seeing, and the visits you can confirm or book from the phone.
                </p>
            </div>
            <a href="{{ route('receptionist.appointments.create') }}" class="dash-banner-btn">Book a visit</a>
        </div>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
            <span class="dash-chip">{{ count($appointments) }} on today’s board</span>
            <span class="dash-chip">Front desk</span>
        </div>
    </section>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            @include('dashboard.partials.stat', ['stat' => $stat])
        @endforeach
    </div>

    <section class="dash-panel mt-6">
        <div class="flex items-center justify-between gap-3 border-b border-line px-6 py-4">
            <div>
                <h2 class="text-lg font-bold text-navy">Today’s appointments ({{ count($appointments) }})</h2>
                <p class="mt-1 text-sm text-text">The board defaults to this calendar day.</p>
            </div>
            <a href="{{ route('receptionist.appointments') }}" class="text-sm font-semibold text-primary no-underline hover:text-primary-hover">Open board</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-page text-text">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Patient</th>
                        <th class="px-6 py-3 font-semibold">Doctor</th>
                        <th class="px-6 py-3 font-semibold">Time</th>
                        <th class="px-6 py-3 font-semibold">Type</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($appointments as $appointment)
                        <tr class="hover:bg-page">
                            <td class="px-6 py-4 font-semibold text-navy">{{ $appointment['patient'] }}</td>
                            <td class="px-6 py-4 text-navy">{{ $appointment['doctor'] }}</td>
                            <td class="px-6 py-4 text-navy">{{ $appointment['time'] }}</td>
                            <td class="px-6 py-4">
                                <span class="badge-info">{{ $appointment['type'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="{{ $statusClass[$appointment['status']] ?? 'badge-info' }}">
                                    {{ $appointment['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-text">No appointments on the board today.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-2">
        <a href="{{ route('receptionist.appointments.create') }}" class="dash-panel block p-6 no-underline hover:border-primary/30">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-primary">Front desk</p>
            <p class="mt-2 text-lg font-bold text-navy">Schedule appointment</p>
            <p class="mt-1 text-sm leading-6 text-text">Find a patient, choose an approved doctor, and confirm the visit.</p>
        </a>
        <a href="{{ route('receptionist.appointments') }}" class="dash-panel block p-6 no-underline hover:border-primary/30">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-primary">Board</p>
            <p class="mt-2 text-lg font-bold text-navy">Update a status</p>
            <p class="mt-1 text-sm leading-6 text-text">Confirm, complete, or cancel a visit already on the schedule.</p>
        </a>
    </section>
@endsection
