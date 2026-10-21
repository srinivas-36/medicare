@extends('layouts.dashboard')

@php
    $bookedDate = $bookedDate ?? '';
    $bookedLabel = $bookedLabel ?? '';
    $canMove = $canMove ?? false;
    $closed = $closed ?? false;
    $department = $department ?? 'Not recorded';
    $visitLabel = $visitLabel ?? 'Visit';
    $patient = $appointment->patient;
    $statusLabels = [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'checked_in' => 'Checked in',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No-show',
    ];
    $statusTones = [
        'pending' => 'bg-[#FFF7ED] text-[#C2410C]',
        'confirmed' => 'bg-[#E8F8EF] text-[#15803D]',
        'checked_in' => 'bg-[#E8F8EF] text-[#15803D]',
        'completed' => 'bg-[#E8F2FF] text-[#0067FF]',
        'cancelled' => 'bg-[#FEF2F2] text-[#DC2626]',
        'no_show' => 'bg-[#FEF2F2] text-[#DC2626]',
    ];
    $initials = collect(preg_split('/\s+/', trim((string) ($patient?->name ?? ''))))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $dateLabel = $appointment->appointment_date?->format('D, M j, Y') ?? 'Not scheduled';
    $checkedIn = $appointment->status === 'checked_in';
    $checkInLabel = match ($appointment->status) {
        'checked_in' => 'Checked in',
        'pending' => 'Not confirmed yet',
        'no_show' => 'No-show',
        default => 'Not checked in yet',
    };
    $checkInWaiting = ! $checkedIn;
    $facts = [
        ['icon' => 'calendar', 'label' => 'Appointment Date', 'value' => $dateLabel],
        ['icon' => 'clock', 'label' => 'Appointment Time', 'value' => $bookedLabel ?: 'Not scheduled'],
        ['icon' => 'building', 'label' => 'Department', 'value' => $department],
        ['icon' => 'file', 'label' => 'Visit Type', 'value' => $visitLabel],
    ];
@endphp

@section('title', 'Appointment Details')

@section('content')
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-muted">
                <a href="{{ route('receptionist.appointments') }}" class="text-muted no-underline hover:text-primary">Appointments</a>
                <span class="px-1">/</span>
                Appointment Details
            </p>
            <h1 class="mt-1 text-[28px] font-semibold tracking-tight text-heading">Appointment Details</h1>
            <p class="mt-1 text-sm text-body">{{ $closed ? 'This visit is closed.' : 'Manage check-in and appointment changes.' }}</p>
        </div>
        <a href="{{ route('receptionist.appointments') }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-line bg-white px-3 text-sm font-semibold text-heading no-underline hover:bg-page">
            <x-ui.icon name="chevron-left" class="h-4 w-4" />
            Back to Appointments
        </a>
    </div>

    @if (session('status'))
        <p class="alert-success mb-4">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert-error mb-4">{{ $errors->first() }}</p>
    @endif

    <section class="rounded-2xl border border-line bg-white shadow-card">
        <div class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-sm font-semibold text-[#0067FF]">{{ $initials !== '' ? $initials : 'P' }}</span>
                <div class="min-w-0">
                    <p class="truncate text-base font-semibold text-heading">{{ $patient?->name ?? 'Patient' }}</p>
                    <p class="mt-1 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-body">
                        <span class="inline-flex items-center gap-1.5">
                            <x-ui.icon name="phone" class="h-3.5 w-3.5 text-muted" />
                            {{ $patient?->phone ?: 'No phone' }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <x-ui.icon name="mail" class="h-3.5 w-3.5 text-muted" />
                            {{ $patient?->email ?: 'No email' }}
                        </span>
                        <span class="inline-flex items-center gap-1.5">
                            <x-ui.icon name="building" class="h-3.5 w-3.5 text-muted" />
                            {{ $department }}
                        </span>
                    </p>
                </div>
            </div>
            <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {{ $statusTones[$appointment->status] ?? 'bg-page text-body' }}">
                @if (in_array($appointment->status, ['confirmed', 'checked_in', 'completed'], true))
                    <x-ui.icon name="check" class="h-4 w-4" />
                @endif
                {{ $statusLabels[$appointment->status] ?? ucfirst(str_replace('_', ' ', $appointment->status)) }}
            </span>
        </div>

        <div class="grid gap-4 border-t border-line px-5 py-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($facts as $fact)
                <div class="flex items-center gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#F4F7FB] text-[#64748B]">
                        <x-ui.icon :name="$fact['icon']" class="h-4 w-4" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs text-muted">{{ $fact['label'] }}</p>
                        <p class="truncate text-sm font-semibold text-heading">{{ $fact['value'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @if (! $closed)
        <div class="mt-4 grid items-start gap-4 {{ $canMove ? 'xl:grid-cols-2' : '' }}">
            <section class="rounded-2xl border border-line bg-white p-5 shadow-card">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-[#0067FF]">
                        <x-ui.icon name="user" class="h-5 w-5" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-heading">Check-in</h2>
                        <p class="mt-0.5 text-sm text-body">Confirm the patient has arrived for their appointment.</p>
                    </div>
                </div>

                <ol class="mt-5 space-y-4">
                    @foreach ([
                        ['Patient arrival', 'Verify the patient\'s identity and confirm arrival.', true],
                        ['Mark as checked in', 'Update the appointment status.', $checkedIn],
                        ['Proceed to consultation', 'Patient will be seen by the doctor.', false],
                    ] as $index => [$title, $copy, $active])
                        <li class="flex gap-3">
                            <span @class([
                                'inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                'bg-[#0067FF] text-white' => $active,
                                'bg-[#F1F5F9] text-[#94A3B8]' => ! $active,
                            ])>{{ $index + 1 }}</span>
                            <div>
                                <p @class(['text-sm font-semibold', 'text-heading' => $active, 'text-[#94A3B8]' => ! $active])>{{ $title }}</p>
                                <p @class(['text-sm', 'text-body' => $active, 'text-[#94A3B8]' => ! $active])>{{ $copy }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-5 rounded-xl bg-[#F4F8FF] px-4 py-3">
                    <p class="text-xs font-medium text-muted">Check-in Status</p>
                    <p @class([
                        'mt-2 inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-sm font-semibold',
                        'bg-[#E8F8EF] text-[#15803D]' => $checkedIn,
                        'bg-[#FFF4E5] text-[#D97706]' => $checkInWaiting,
                    ])>
                        @if ($checkedIn)
                            <x-ui.icon name="check" class="h-4 w-4" />
                        @else
                            <x-ui.icon name="clock" class="h-4 w-4" />
                        @endif
                        {{ $checkInLabel }}
                    </p>
                </div>

                @if ($appointment->status === 'pending')
                    <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="status" value="confirmed">
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#0067FF] text-sm font-semibold text-white hover:bg-[#0056D6]">
                            <x-ui.icon name="check" class="h-4 w-4" />
                            Confirm appointment
                        </button>
                    </form>
                @endif

                @if ($appointment->status === 'confirmed')
                    <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment) }}" class="mt-4">
                        @csrf
                        <input type="hidden" name="status" value="checked_in">
                        <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#0067FF] text-sm font-semibold text-white hover:bg-[#0056D6]">
                            <x-ui.icon name="check" class="h-4 w-4" />
                            Check in patient
                        </button>
                    </form>
                    <p class="mt-3 flex items-start gap-2 text-xs text-muted">
                        <x-ui.icon name="info" class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        Checking in updates the appointment status for the doctor.
                    </p>
                @endif
            </section>

            @if ($canMove)
                <section class="rounded-2xl border border-line bg-white p-5 shadow-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-[#0067FF]">
                                <x-ui.icon name="calendar" class="h-5 w-5" />
                            </span>
                            <div>
                                <h2 class="text-base font-semibold text-heading">Reschedule appointment</h2>
                                <p class="mt-0.5 text-sm text-body">Choose a new date and available time if needed.</p>
                            </div>
                        </div>
                        <p class="shrink-0 text-sm font-semibold text-[#0067FF]">{{ $dateLabel }}</p>
                    </div>
                    @include('dashboard.partials.reschedule-calendar', [
                        'action' => route('receptionist.appointments.reschedule', $appointment),
                        'openSlots' => $openSlots,
                        'bookedDate' => $bookedDate,
                        'bookedLabel' => $bookedLabel,
                    ])
                </section>
            @endif
        </div>
    @endif

    <section class="mt-4 rounded-2xl border border-line bg-white p-5 shadow-card" x-data="{ notes: @js(old('notes', $appointment->notes ?? '')), saved: false }">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#E8F2FF] text-[#0067FF]">
                <x-ui.icon name="note" class="h-4 w-4" />
            </span>
            <h2 class="text-sm font-semibold text-heading">Receptionist notes (optional)</h2>
        </div>
        <form method="POST" action="{{ route('receptionist.appointments.notes', $appointment) }}" class="mt-3" @submit.prevent="
            saved = false;
            fetch($el.action, { method: 'POST', body: new FormData($el), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then((response) => { if (response.ok) saved = true; });
        ">
            @csrf
            <textarea
                name="notes"
                x-model="notes"
                maxlength="500"
                rows="3"
                placeholder="Add any notes about this appointment (e.g., special instructions, patient requests, etc.)"
                class="field h-auto w-full resize-none py-3"
                @blur="$el.form.requestSubmit()"
            ></textarea>
            <p class="mt-1 text-right text-xs text-muted">
                <span x-show="saved" x-cloak class="mr-2 text-[#15803D]">Saved</span>
                <span x-text="notes.length"></span>/500
            </p>
        </form>
    </section>
@endsection
