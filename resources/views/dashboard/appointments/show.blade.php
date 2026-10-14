@extends('layouts.dashboard')

@php
    $role = $role ?? 'patient';
    $booking = \App\Support\AppointmentPage::booking($appointment, $role);
    $person = $booking['person'];
    $record = $booking['patient_record'];
    $messageUrl = $role === 'doctor'
        ? route('doctor.appointments.messages.store', $appointment)
        : route('patient.bookings.messages.store', $appointment);
    $canUpload = $role === 'patient' && $appointment->status !== 'cancelled';
    $inputClass = 'h-12 w-full rounded-xl border border-line bg-page px-4 text-ink outline-none transition placeholder:text-muted focus:border-primary focus:ring-4 focus:ring-primary/15';
@endphp

@section('content')
    <div>
        @if (session('status'))
            <p class="alert-success">{{ session('status') }}</p>
        @endif

        @if ($errors->hasAny(['to_doctor_id', 'reason', 'clinical_question', 'urgency', 'report_ids', 'report_ids.*']))
            <p class="alert-error mt-4">{{ $errors->first() }}</p>
        @endif

        @if ($role === 'doctor')
            @include('dashboard.appointments.partials.doctor-visit')
        @else
            @include('dashboard.appointments.partials.patient-visit')
        @endif

    </div>
@endsection
