@extends('layouts.dashboard')

@section('title', 'Visit record')

@section('content')
    <x-ui.page-header
        title="{{ $appointment->doctor?->user?->name ?? 'Doctor' }}"
        description="{{ $appointment->appointment_date?->format('l, F j, Y') }} · {{ $appointment->doctor?->specialization }}"
    >
        <a href="{{ route('patient.history') }}" class="btn-secondary">Back to history</a>
    </x-ui.page-header>

    <section class="panel mt-4 divide-y divide-line">
        <div class="grid gap-4 p-4 sm:grid-cols-2">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Status</p>
                <p class="mt-1 text-sm font-semibold text-heading">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-muted">Reason</p>
                <p class="mt-1 text-sm text-heading">{{ $appointment->reason ?: 'Not recorded' }}</p>
            </div>
        </div>
        <div class="space-y-3 p-4">
        @if ($appointment->status === 'completed' && $appointment->consultation)
            <p><span class="font-semibold text-heading">Diagnosis.</span> {{ $appointment->consultation->diagnosis ?: 'Not published' }}</p>
            <p class="whitespace-pre-line"><span class="font-semibold text-heading">Plan.</span> {{ $appointment->consultation->plan ?: 'Not published' }}</p>
            @if ($appointment->consultation->follow_up_on || $appointment->consultation->follow_up_instructions)
                <p class="whitespace-pre-line"><span class="font-semibold text-heading">Follow-up.</span>@if ($appointment->consultation->follow_up_on) Next review {{ $appointment->consultation->follow_up_on->format('d M Y') }}.@endif {{ $appointment->consultation->follow_up_instructions }}</p>
            @endif
        @else
            <p class="text-sm text-text">A consultation summary appears here after the doctor completes the visit.</p>
        @endif
        <div>
            <p class="font-semibold text-heading">Reports</p>
            <ul class="mt-2 space-y-2">
                @forelse ($appointment->reports as $report)
                    <li><a class="font-semibold text-primary" href="{{ route('patient.bookings.reports.show', [$appointment, $report]) }}">{{ $report->original_name }}</a></li>
                @empty
                    <li class="text-sm text-text">No reports on this visit.</li>
                @endforelse
            </ul>
        </div>
        </div>
    </section>
@endsection
