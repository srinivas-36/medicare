@extends('layouts.dashboard')

@php
    $name = $patient->name ?? 'Patient';
    $visits = $visits ?? [];
    $profile = $profile ?? null;

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Completed' => 'badge-info',
        'Cancelled' => 'badge-danger',
    ];
@endphp

@section('content')
    <section class="dash-banner">
        <a href="{{ route('doctor.patients') }}" class="text-sm font-semibold text-white/75 hover:text-white">Back to patients</a>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Patient record</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">{{ $name }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            Contact details, health notes, and the visits this patient has had with you.
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ count($visits) }} {{ \Illuminate\Support\Str::plural('visit', count($visits)) }}</span>
            <span class="dash-chip">{{ $patient->email }}</span>
        </div>
    </section>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    <section class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <h2 class="text-lg font-bold text-[#0B1F3A]">Health record</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @include('dashboard.partials.detail', ['label' => 'Email', 'value' => $patient->email])
            @include('dashboard.partials.detail', ['label' => 'Phone', 'value' => $patient->phone])
            @include('dashboard.partials.detail', ['label' => 'Age', 'value' => $profile?->age])
            @include('dashboard.partials.detail', ['label' => 'Blood type', 'value' => $profile?->blood_type])
        </div>
        <div class="mt-6">
            @include('dashboard.partials.health-notes', ['healthNotes' => $profile?->health_notes ?? []])
        </div>
    </section>

    <section class="mt-6 overflow-hidden rounded-2xl border border-[#E2EAF4] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-[#F4F7FB] text-[#53647D]">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold">Time</th>
                        <th class="px-6 py-3 font-semibold">Condition</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2EAF4]">
                    @forelse ($visits as $visit)
                        <tr class="hover:bg-[#F4F7FB]">
                            <td class="px-6 py-4 font-semibold text-[#0B1F3A]">
                                <a href="{{ route('doctor.appointments.show', $visit['id']) }}" class="hover:text-[#1769FF]">{{ $visit['date'] }}</a>
                            </td>
                            <td class="px-6 py-4 text-[#0B1F3A]">{{ $visit['time'] }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-[#EAF3FF] px-2.5 py-1 text-xs font-semibold text-[#1769FF]">{{ $visit['condition'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$visit['status']] ?? 'bg-[#F4F7FB] text-[#53647D]' }}">{{ $visit['status'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-[#53647D]">No visits with you yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
