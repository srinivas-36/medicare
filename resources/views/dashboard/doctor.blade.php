@extends('layouts.dashboard')

@php
    $user = auth()->user();
    $name = $user->name ?? 'Doctor';
    $specialization = data_get($doctor ?? $user, 'specialization');
    $approval = data_get($doctor ?? $user, 'isApproved');
    $isPending = $approval === 'pending';

    $stats = $stats ?? [];
    $appointments = $appointments ?? [];

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Completed' => 'badge-info',
        'Cancelled' => 'badge-danger',
    ];

    $approvalLabel = match ($approval) {
        'approved' => 'Approved to see patients',
        'cancelled' => 'Approval cancelled',
        'pending' => 'Profile awaiting approval',
        default => 'Profile status unknown',
    };
@endphp

@section('title', 'Doctor dashboard')

@section('content')
    <section class="dash-banner">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Doctor dashboard</p>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-5">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Good day, {{ $name }}</h1>
                <p class="mt-3 text-sm leading-6 text-white/80">
                    Today’s clinic list, incoming referrals, and the patients on your record. Book a new patient straight onto your schedule when they are not listed yet.
                </p>
            </div>
            <a href="{{ route('doctor.patients.create') }}" class="dash-banner-btn">Add a patient</a>
        </div>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
            @if ($specialization)
                <span class="dash-chip">{{ $specialization }}</span>
            @endif
            <span class="dash-chip">{{ $approvalLabel }}</span>
            <span class="dash-chip">{{ count($appointments) }} on today’s list</span>
        </div>
    </section>

    @if ($isPending)
        <div class="mt-6 rounded-2xl border border-[#f3d7a1] bg-[#fff7e8] px-5 py-4 text-sm leading-6 text-[#8a5a12]">
            To get approval, complete your profile. We’ll review it manually and approve within 3 days.
        </div>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            @include('dashboard.partials.stat', ['stat' => $stat])
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)]">
        <section class="dash-panel">
            <div class="flex items-center justify-between gap-3 border-b border-line px-6 py-4">
                <div>
                    <h2 class="text-lg font-bold text-navy">Appointments today ({{ count($appointments) }})</h2>
                    <p class="mt-1 text-sm text-text">Visits scheduled for this calendar day.</p>
                </div>
                <a href="{{ route('doctor.appointments') }}" class="text-sm font-semibold text-primary no-underline hover:text-primary-hover">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-page text-text">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Patient</th>
                            <th class="px-6 py-3 font-semibold">Condition</th>
                            <th class="px-6 py-3 font-semibold">Time</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @forelse ($appointments as $appointment)
                            <tr class="hover:bg-page">
                                <td class="px-6 py-4 font-semibold text-navy">{{ $appointment['patient'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="badge-info">{{ $appointment['condition'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-navy">{{ $appointment['time'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="{{ $statusClass[$appointment['status']] ?? 'badge-info' }}">
                                        {{ $appointment['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-text">No appointments scheduled for today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="dash-panel p-6">
            <h2 class="text-lg font-bold text-navy">Clinic shortcuts</h2>
            <p class="mt-1 text-sm leading-6 text-text">The actions you use between appointments.</p>
            <div class="mt-5 space-y-3">
                <a href="{{ route('doctor.patients.create') }}" class="block rounded-xl bg-page px-4 py-4 no-underline hover:bg-light">
                    <p class="font-semibold text-navy">Add a patient</p>
                    <p class="mt-0.5 text-sm text-text">Enter their details and book an open time</p>
                </a>
                <a href="{{ route('doctor.referrals') }}" class="block rounded-xl bg-page px-4 py-4 no-underline hover:bg-light">
                    <p class="font-semibold text-navy">Review referrals</p>
                    <p class="mt-0.5 text-sm text-text">Open the snapshot another doctor sent</p>
                </a>
                <a href="{{ route('doctor.profile.edit') }}" class="block rounded-xl bg-page px-4 py-4 no-underline hover:bg-light">
                    <p class="font-semibold text-navy">Update your profile</p>
                    <p class="mt-0.5 text-sm text-text">Specialty, fee, and the hours patients can book</p>
                </a>
            </div>
        </section>
    </div>
@endsection
