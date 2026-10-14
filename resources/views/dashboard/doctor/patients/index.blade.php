@extends('layouts.dashboard')

@php
    $patients = $patients ?? [];
    $search = $search ?? '';
    $hasPatients = $hasPatients ?? ($patients !== []);
@endphp

@section('title', 'Patients')

@section('content')
    <x-ui.page-header title="Patients" description="Manage your patients. View history and start consultations.">
        <a href="{{ route('doctor.patients.create') }}" class="btn-primary">
            <x-ui.icon name="plus" class="h-4 w-4" />
            Add patient
        </a>
    </x-ui.page-header>

    <form method="GET" action="{{ route('doctor.patients') }}" class="mb-4 flex flex-col gap-2 sm:flex-row">
        <label for="patient-search" class="sr-only">Search patients</label>
        <input id="patient-search" type="search" name="q" value="{{ $search }}" placeholder="Name, email, or phone..." class="field sm:max-w-md">
        <button class="btn-primary" type="submit">Search</button>
        @if ($search !== '')
            <a href="{{ route('doctor.patients') }}" class="btn-secondary">Clear</a>
        @endif
    </form>

    <section class="dash-panel">
        <div class="hidden md:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line text-[13px] text-muted">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Patient</th>
                        <th class="px-4 py-2.5 font-semibold">Last visit</th>
                        <th class="px-4 py-2.5 font-semibold">Next appointment</th>
                        <th class="px-4 py-2.5 font-semibold">Follow-up</th>
                        <th class="px-4 py-2.5 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($patients as $patient)
                        <tr class="hover:bg-page">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <x-ui.avatar :name="$patient['name']" size="sm" />
                                    <div class="min-w-0">
                                        <a href="{{ route('doctor.patients.show', $patient['id']) }}" class="font-semibold text-heading no-underline hover:text-primary">{{ $patient['name'] }}</a>
                                        <p class="truncate text-xs text-muted">{{ $patient['email'] }}</p>
                                        <span class="badge-info mt-1">{{ $patient['source'] ?? 'From a visit' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-heading">{{ $patient['last_visit'] }}</td>
                            <td class="px-4 py-3 text-heading">{{ $patient['next'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-heading">{{ $patient['follow_up'] ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('doctor.patients.show', $patient['id']) }}" class="text-sm font-semibold text-primary no-underline">Open chart</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-sm text-body">
                                @if ($search !== '' && $hasPatients)
                                    No patients match that search. Revise the name, email, or phone.
                                @else
                                    No patients yet. Add a patient to start a record before their first visit.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <ul class="divide-y divide-line md:hidden">
            @forelse ($patients as $patient)
                <li class="px-4 py-3">
                    <a href="{{ route('doctor.patients.show', $patient['id']) }}" class="font-semibold text-heading no-underline">{{ $patient['name'] }}</a>
                    <p class="mt-1 text-[13px] text-body">Last completed: {{ $patient['last_visit'] }}</p>
                    <p class="text-[13px] text-body">Next: {{ $patient['next'] ?? 'None scheduled' }}</p>
                    <span class="badge-info mt-2">{{ $patient['source'] ?? 'From a visit' }}</span>
                </li>
            @empty
                <li class="px-4 py-5 text-sm text-body">No patients to show.</li>
            @endforelse
        </ul>
    </section>
@endsection
