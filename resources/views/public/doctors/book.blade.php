@extends('layouts.public')

@section('title', 'Book your appointment')
@section('description', 'Choose a date and an open time, then pay the consultation fee to confirm the visit.')

@php
    $doctor = $doctor ?? [];
    $slug = data_get($doctor, 'slug');
    $profileUrl = $slug ? route('doctors.show', $slug) : route('doctors.index');
@endphp

@section('content')
<div class="bg-page">
    <section class="py-6 lg:py-8">
        <div class="mx-auto w-full max-w-3xl px-5">
            @if (empty($doctor) || empty(data_get($doctor, 'name')))
                <div class="rounded-2xl border border-line bg-white px-6 py-8 text-center shadow-card">
                    <h1 class="text-2xl font-bold text-navy">Doctor not found</h1>
                    <p class="mt-2 text-sm text-body">This profile is unavailable, so a booking cannot be started.</p>
                    <a href="{{ route('doctors.index') }}" class="btn-primary mt-5">Browse doctors</a>
                </div>
            @else
                <a href="{{ $profileUrl }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary no-underline hover:text-primary-hover">
                    <span aria-hidden="true">←</span> Back to profile
                </a>
                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-navy">Book your appointment</h1>
                <p class="mt-1 text-sm text-body">With {{ data_get($doctor, 'name') }}, {{ data_get($doctor, 'specialization') }}</p>

                <div class="mt-4">
                    @include('partials.appointment-booking-form')
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
