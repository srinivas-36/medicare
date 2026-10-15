@extends('layouts.public')

@section('title', 'Book your appointment')
@section('description', 'Choose a date and an open time, then confirm the visit.')

@php
    $doctor = $doctor ?? [];
    $slug = data_get($doctor, 'slug');
    $profileUrl = $slug ? route('doctors.show', $slug) : route('doctors.index');
    $doctorName = data_get($doctor, 'name');
@endphp

@section('content')
    @if (empty($doctor) || empty($doctorName))
        <section class="page py-16 lg:py-24">
            <div class="mx-auto max-w-xl rounded-2xl border border-line bg-white px-6 py-12 text-center shadow-card">
                <h1 class="text-2xl font-bold text-navy">Doctor not found</h1>
                <p class="mt-2 text-sm leading-6 text-body">This profile is unavailable, so a booking cannot be started.</p>
                <a href="{{ route('doctors.index') }}" class="btn-primary mt-6">Browse doctors</a>
            </div>
        </section>
    @else
        <section class="page py-8 lg:py-12">
            <h1 class="sr-only">Book your appointment</h1>
            <nav class="flex flex-wrap items-center gap-2 text-sm text-muted" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="inline-flex text-muted no-underline hover:text-primary" aria-label="Home">
                    <x-ui.icon name="home" class="h-4 w-4" />
                </a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('doctors.index') }}" class="text-muted no-underline hover:text-primary">Doctors</a>
                <span aria-hidden="true">/</span>
                <a href="{{ $profileUrl }}" class="text-muted no-underline hover:text-primary">{{ $doctorName }}</a>
                <span aria-hidden="true">/</span>
                <span class="font-medium text-navy">Book appointment</span>
            </nav>

            <div class="mt-4">
                @include('partials.appointment-booking-form')
            </div>
        </section>
    @endif
@endsection
