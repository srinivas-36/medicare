@extends('layouts.dashboard')

@section('title', 'Appointment')

@section('content')
    <x-ui.page-header
        eyebrow="Appointment"
        title="{{ $appointment->patient?->name }}"
        description="{{ $appointment->appointment_date?->format('l, F j, Y') }} · {{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('g:i A') }}"
    >
        <a href="{{ route('receptionist.appointments') }}" class="btn-secondary">Back</a>
    </x-ui.page-header>

    @if (session('status'))
        <p class="alert-success">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert-error">{{ $errors->first() }}</p>
    @endif

    <section class="panel p-4">
        <p class="text-sm text-text">Status: {{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</p>
        <p class="mt-2 text-sm text-text">Reason: {{ $appointment->reason ?: 'Not recorded' }}</p>
        <div class="mt-4 flex flex-wrap gap-2">
            @if ($appointment->status === 'confirmed')
                <form method="POST" action="{{ route('receptionist.appointments.update-status', $appointment) }}">
                    @csrf
                    <input type="hidden" name="status" value="checked_in">
                    <button class="btn-primary" type="submit">Check in</button>
                </form>
            @endif
        </div>
    </section>

    @if (! in_array($appointment->status, ['completed', 'cancelled', 'no_show'], true))
        <form method="POST" action="{{ route('receptionist.appointments.reschedule', $appointment) }}" class="mt-6 max-w-xl space-y-4 rounded-xl border border-line bg-white p-4 sm:p-5">
            @csrf
            @include('partials.slot-picker', [
                'openSlots' => $openSlots,
                'minDate' => $minDate,
                'maxDate' => $maxDate,
            ])
            <label class="text-sm font-medium text-heading">Reason
                <input name="reason" class="field mt-1.5">
            </label>
            <button class="btn-primary" type="submit">Reschedule</button>
        </form>
    @endif
@endsection
