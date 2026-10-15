@extends('layouts.dashboard')

@php
    $bookedDate = $bookedDate ?? '';
    $bookedLabel = $bookedLabel ?? '';
    $canMove = $canMove ?? false;
    $patient = $appointment->patient;
    $statusLabels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'checked_in' => 'At the clinic',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No-show',
    ];
    $statusBadges = [
        'pending' => 'badge-warning',
        'confirmed' => 'badge-success',
        'checked_in' => 'badge-success',
        'completed' => 'badge-info',
        'cancelled' => 'badge-danger',
        'no_show' => 'badge-danger',
    ];
    $initials = collect(preg_split('/\s+/', trim((string) ($patient?->name ?? ''))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

@section('title', 'Appointment')

@section('content')
    <x-ui.page-header
        eyebrow="Appointment"
        title="{{ $patient?->name ?? 'Patient' }}"
    >
        <a href="{{ route('receptionist.appointments') }}" class="btn-secondary">Back</a>
    </x-ui.page-header>

    @if (session('status'))
        <p class="alert-success">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert-error">{{ $errors->first() }}</p>
    @endif

    <section class="panel">
        <div class="flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
            <div class="flex min-w-0 items-center gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-sm font-semibold text-primary">{{ $initials !== '' ? $initials : 'P' }}</span>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate text-sm font-semibold text-heading">{{ $appointment->appointment_date?->format('D, M j, Y') }} · {{ $bookedLabel }}</p>
                        <span class="{{ $statusBadges[$appointment->status] ?? 'badge-info' }}">{{ $statusLabels[$appointment->status] ?? ucfirst(str_replace('_', ' ', $appointment->status)) }}</span>
                    </div>
                    <p class="mt-0.5 truncate text-sm text-body">
                        {{ $patient?->phone ?: 'No phone' }}
                        <span class="px-1 text-muted">·</span>
                        {{ $patient?->email ?: 'No email' }}
                        <span class="px-1 text-muted">·</span>
                        {{ $appointment->reason ?: 'No reason recorded' }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                @if ($appointment->status === 'pending')
                    <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment) }}">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button class="btn-primary" type="submit">Confirm</button>
                    </form>
                @endif
                @if ($appointment->status === 'confirmed')
                    <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment) }}">
                        @csrf
                        <input type="hidden" name="status" value="checked_in">
                        <button class="btn-primary" type="submit">Check in</button>
                    </form>
                @endif
            </div>
        </div>

        @if ($canMove)
            <div class="border-t border-line px-4 py-4 sm:px-5">
                <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                    <h2 class="text-sm font-semibold text-heading">Reschedule</h2>
                    <p class="text-sm text-body">The booked day is green. Choose a later day, then a time.</p>
                </div>
                @include('dashboard.partials.reschedule-calendar', [
                    'action' => route('receptionist.appointments.reschedule', $appointment),
                    'openSlots' => $openSlots,
                    'bookedDate' => $bookedDate,
                    'bookedLabel' => $bookedLabel,
                ])
            </div>
        @endif
    </section>
@endsection
