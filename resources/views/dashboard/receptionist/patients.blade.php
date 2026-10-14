@extends('layouts.dashboard')

@section('title', 'Patients')

@section('content')
    <x-ui.page-header title="Patients" description="Search for an existing patient or add a new one for {{ $doctorName ?: 'your clinic' }}. Clinical notes stay with the doctor.">
        @if ($assigned)
            <a href="{{ route('receptionist.appointments.create', ['mode' => 'new']) }}" class="btn-primary">Add patient</a>
        @endif
    </x-ui.page-header>
    @unless ($assigned)
        <p class="dash-panel mt-6 p-6 text-sm">No doctor is assigned to this account.</p>
    @else
        <form method="GET" class="mb-4 flex flex-col gap-2 sm:flex-row">
            <label for="desk-patient-search" class="sr-only">Search patients</label>
            <input id="desk-patient-search" type="search" name="q" value="{{ $search }}" placeholder="Name, email, or phone" class="field sm:max-w-md">
            <button class="btn-primary" type="submit">Search</button>
        </form>
        <section class="panel">
            <ul class="divide-y divide-line">
                @forelse ($patients as $patient)
                    <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-ui.avatar :name="$patient->name" size="sm" />
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-heading">{{ $patient->name }}</p>
                                <p class="truncate text-sm text-body">{{ $patient->email }} @if($patient->phone) · {{ $patient->phone }} @endif</p>
                            </div>
                        </div>
                        <a class="btn-primary h-9 px-3" href="{{ route('receptionist.appointments.create', ['q' => $patient->name, 'patient_id' => $patient->id]) }}">Book</a>
                    </li>
                @empty
                    <li class="px-4 py-5 text-sm text-body">{{ $search === '' ? 'Search to find a patient, or add a new one.' : 'No patients match that search. Add them as a new patient.' }}</li>
                @endforelse
            </ul>
        </section>
    @endunless
@endsection
