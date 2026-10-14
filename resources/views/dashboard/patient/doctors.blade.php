@extends('layouts.dashboard')

@section('title', 'Book an appointment')

@section('content')
    <x-ui.page-header
        title="Book an appointment"
        description="Choose an approved doctor. The next step asks for a time and any reports you want them to review."
    />

    <x-dashboard.booking-steps :step="1" />

    <form method="GET" action="{{ route('patient.book') }}" class="mb-5 rounded-2xl border border-[#E2E8F0] bg-white p-4 shadow-card">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-end">
            <div class="min-w-0 flex-1">
                <label for="doctor-search" class="label">Search</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted">
                        <x-ui.icon name="search" class="h-4 w-4" />
                    </span>
                    <input id="doctor-search" type="search" name="q" value="{{ $search }}" placeholder="Name, specialty, or qualification" class="field pl-9">
                </div>
            </div>
            @if ($specialties->isNotEmpty())
                <div class="lg:w-64">
                    <label for="doctor-specialty" class="label">Specialty</label>
                    <select id="doctor-specialty" name="specialty" class="field">
                        <option value="">All specialties</option>
                        @foreach ($specialties as $option)
                            <option value="{{ $option }}" @selected($specialty === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="flex gap-2">
                <button class="btn-primary" type="submit">Search</button>
                @if ($search !== '' || $specialty !== '')
                    <a href="{{ route('patient.book') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </div>
    </form>

    @if ($doctors->isEmpty())
        <x-ui.empty-state
            :title="$search !== '' || $specialty !== '' ? 'No doctors match that search' : 'No doctors are open for booking'"
            :text="$search !== '' || $specialty !== '' ? 'Try another name or specialty.' : 'Approved doctors with an active account will appear here.'"
        >
            @if ($search !== '' || $specialty !== '')
                <a href="{{ route('patient.book') }}" class="btn-primary">View all doctors</a>
            @endif
        </x-ui.empty-state>
    @else
        <p class="mb-4 text-sm text-body">Showing {{ $doctors->firstItem() }}–{{ $doctors->lastItem() }} of {{ $doctors->total() }} {{ $doctors->total() === 1 ? 'doctor' : 'doctors' }}</p>

        <ul class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($doctors as $doctor)
                @php
                    $name = $doctor->user?->name ?? 'Doctor';
                    $degrees = $doctor->qualifications->pluck('degree')->filter()->take(2)->implode(' · ');
                    $current = $doctor->experiences->firstWhere('is_current', true) ?? $doctor->experiences->first();
                    $practice = $current?->designation
                        ? trim($current->designation.($current->hospital_name ? ' · '.$current->hospital_name : ''))
                        : '';
                    $reviews = (int) $doctor->total_rating;
                    $duration = max(5, (int) ($doctor->appointment_duration ?? 30));
                @endphp
                <li class="flex h-full flex-col rounded-2xl border border-[#E2E8F0] bg-white p-5 shadow-card">
                    <div class="flex items-start gap-3">
                        <x-ui.avatar :name="$name" :photo="$doctor->user?->photo" size="lg" />
                        <div class="min-w-0 flex-1">
                            <h2 class="truncate text-base font-semibold text-heading">{{ $name }}</h2>
                            <p class="mt-0.5 truncate text-sm font-medium text-[#0067FF]">{{ $doctor->specialization ?: 'Specialist' }}</p>
                            @if ($reviews > 0)
                                <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-heading">
                                    <x-ui.icon name="star" class="h-3.5 w-3.5 text-amber-400" />
                                    {{ number_format((float) $doctor->average_rating, 1) }}
                                    <span class="font-medium text-muted">({{ $reviews }})</span>
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 space-y-1.5 text-sm text-body">
                        @if ($degrees !== '')
                            <p class="line-clamp-2">{{ $degrees }}</p>
                        @endif
                        @if ($practice !== '')
                            <p class="line-clamp-2 text-[13px] text-muted">{{ $practice }}</p>
                        @elseif ($degrees === '')
                            <p class="text-[13px] text-muted">Approved for clinic visits</p>
                        @endif
                    </div>

                    <div class="mt-auto flex items-end justify-between gap-3 border-t border-[#E2E8F0] pt-4">
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">{{ $duration }} min visit</p>
                            <p class="mt-0.5 text-lg font-semibold tracking-tight text-heading">{{ $doctor->ticketPrice }} USD</p>
                        </div>
                        <a href="{{ route('patient.book.create', $doctor) }}" class="btn-primary">
                            Select
                            <x-ui.icon name="arrow" class="h-4 w-4" />
                        </a>
                    </div>
                </li>
            @endforeach
        </ul>

        {{ $doctors->links('dashboard.patient.partials.pagination') }}
    @endif
@endsection
