@extends('layouts.dashboard')

@section('title', 'Medical history')

@section('content')
    @php
        $filtered = collect($filters)->filter(fn ($value) => $value !== '' && $value !== 0 && $value !== '0')->isNotEmpty();
    @endphp

    <x-ui.page-header title="Medical history" description="Each visit keeps its own consultation. Opening an older visit does not change a new appointment." />

    <form method="GET" action="{{ route('patient.history') }}" class="mb-4 grid gap-3 rounded-xl border border-line bg-white p-3 shadow-card sm:grid-cols-2 lg:grid-cols-6">
        <label class="text-[13px] font-medium text-body">From
            <input type="date" name="from" value="{{ $filters['from'] }}" class="field mt-1">
        </label>
        <label class="text-[13px] font-medium text-body">To
            <input type="date" name="to" value="{{ $filters['to'] }}" class="field mt-1">
        </label>
        <label class="text-[13px] font-medium text-body">Doctor
            <select name="doctor" class="field mt-1">
                <option value="">All doctors</option>
                @foreach ($doctors as $doctor)
                    <option value="{{ $doctor->id }}" @selected((int) $filters['doctor'] === (int) $doctor->id)>{{ $doctor->user?->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-[13px] font-medium text-body">Type
            <select name="visit" class="field mt-1">
                <option value="">All types</option>
                @foreach (['consultation' => 'Consultation', 'check-up' => 'Check-up', 'follow-up' => 'Follow-up', 'emergency' => 'Emergency'] as $value => $label)
                    <option value="{{ $value }}" @selected($filters['visit'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-[13px] font-medium text-body">Show
            <select name="scope" class="field mt-1">
                <option value="">All records</option>
                <option value="consultations" @selected($filters['scope'] === 'consultations')>Completed consultations</option>
                <option value="upcoming" @selected($filters['scope'] === 'upcoming')>Upcoming</option>
                <option value="cancelled" @selected($filters['scope'] === 'cancelled')>Cancelled</option>
            </select>
        </label>
        <div class="flex items-end">
            <button class="btn-primary w-full" type="submit">Apply</button>
        </div>
    </form>

    <ol class="space-y-3">
        @forelse ($visits as $visit)
            <li class="rounded-xl border border-line bg-white px-4 py-3 sm:px-5">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[13px] font-semibold text-primary">{{ $visit->appointment_date?->format('M j, Y') }}</p>
                        <h2 class="mt-0.5 text-base font-semibold text-heading">{{ $visit->doctor?->user?->name ?? 'Doctor' }}</h2>
                        <p class="text-[13px] text-body">{{ $visit->doctor?->specialization }} · {{ ucfirst($visit->visit_type) }} · {{ ucfirst(str_replace('_', ' ', $visit->status)) }}</p>
                    </div>
                    <a href="{{ route('patient.history.show', $visit) }}" class="text-sm font-semibold text-primary no-underline">Open visit</a>
                </div>
                <p class="mt-2 text-sm leading-6 text-heading">{{ $visit->reason ?: 'No reason recorded.' }}</p>
                @if ($visit->status === 'completed' && $visit->consultation)
                    <p class="mt-1 text-sm text-body">{{ $visit->consultation->diagnosis ?: 'Consultation recorded without a diagnosis.' }}</p>
                    @if ($visit->consultation->plan)
                        <p class="mt-1 text-sm text-body">{{ $visit->consultation->plan }}</p>
                    @endif
                    @if ($visit->consultation->follow_up_instructions)
                        <p class="mt-1 text-sm text-body">Follow-up: {{ $visit->consultation->follow_up_instructions }}</p>
                    @endif
                @elseif ($visit->status === 'cancelled')
                    <p class="mt-1 text-sm text-body">Cancelled. This is not a completed consultation.</p>
                @endif
                @if ($visit->reports->isNotEmpty())
                    <p class="mt-2 text-[13px] text-muted">{{ $visit->reports->count() }} {{ \Illuminate\Support\Str::plural('report', $visit->reports->count()) }} on this visit</p>
                @endif
            </li>
        @empty
            <li class="rounded-xl border border-line bg-white px-4 py-5">
                @if ($filtered)
                    <p class="text-sm font-semibold text-heading">No visits match these filters</p>
                    <a href="{{ route('patient.history') }}" class="mt-2 inline-flex text-sm font-semibold text-primary">Clear filters</a>
                @else
                    <p class="text-sm font-semibold text-heading">No previous visits</p>
                    <p class="mt-1 text-sm leading-6 text-body">When a doctor completes a visit, the consultation, diagnosis, and reports appear here.</p>
                    <a href="{{ route('patient.book') }}" class="btn-primary mt-3">Find a Doctor</a>
                @endif
            </li>
        @endforelse
    </ol>

    <div class="mt-4">{{ $visits->links() }}</div>
@endsection
