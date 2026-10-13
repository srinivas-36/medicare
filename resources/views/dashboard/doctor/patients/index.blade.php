@extends('layouts.dashboard')

@php
    $patients = $patients ?? [];
@endphp

@section('title', 'Patients')

@section('content')
    <section class="dash-banner">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Patients</p>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-5">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">People in your care</h1>
                <p class="mt-3 text-sm leading-6 text-white/80">
                    Everyone with a visit on your schedule. Add a patient to book someone who is not listed yet.
                </p>
            </div>
            <a href="{{ route('doctor.patients.create') }}" class="dash-banner-btn">Add a patient</a>
        </div>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ count($patients) }} {{ \Illuminate\Support\Str::plural('patient', count($patients)) }}</span>
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
        </div>
    </section>

    <section class="dash-panel mt-6">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-page text-text">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Patient</th>
                        <th class="px-6 py-3 font-semibold">Last visit</th>
                        <th class="px-6 py-3 font-semibold">Visits</th>
                        <th class="px-6 py-3 font-semibold">Record</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($patients as $patient)
                        <tr class="hover:bg-page">
                            <td class="px-6 py-4">
                                <a href="{{ route('doctor.patients.show', $patient['id']) }}" class="font-semibold text-navy no-underline hover:text-primary">{{ $patient['name'] }}</a>
                                @if (! empty($patient['email']))
                                    <p class="mt-0.5 text-xs text-text">{{ $patient['email'] }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-navy">{{ $patient['last_visit'] }}</td>
                            <td class="px-6 py-4 text-navy">{{ $patient['visits'] }}</td>
                            <td class="px-6 py-4">
                                <span class="badge-info">{{ $patient['source'] ?? 'From a visit' }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-text">No patients yet. Add one to start a record before their first visit.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
