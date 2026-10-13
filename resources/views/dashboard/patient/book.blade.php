@extends('layouts.dashboard')

@php
    $name = data_get($doctor, 'name', 'Doctor');
    $specialty = data_get($doctor, 'specialization', '');
    $fee = data_get($doctor, 'ticketPrice', '0');
@endphp

@section('title', 'Book '.$name)

@section('content')
    <section class="dash-banner">
        <a href="{{ route('patient.book') }}" class="text-sm font-semibold text-white/75 hover:text-white">All doctors</a>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Patient booking</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Book {{ $name }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            Choose an open time{{ $specialty ? ' with '.$specialty : '' }}. The consultation fee is collected when you confirm, and the visit stays on your dashboard.
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
            @if ($specialty)
                <span class="dash-chip">{{ $specialty }}</span>
            @endif
            <span class="dash-chip">{{ $fee }} USD consultation</span>
        </div>
    </section>

    <div class="mt-6">
        @include('partials.appointment-booking-form')
    </div>
@endsection
