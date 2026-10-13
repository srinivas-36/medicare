@extends('layouts.dashboard')

@php
    $user = auth()->user();
    $name = $user->name ?? 'there';
    $stats = $stats ?? [];
    $appointments = $appointments ?? [];
    $choices = $choices ?? [];

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Completed' => 'badge-info',
        'Cancelled' => 'badge-danger',
    ];

    $upcoming = collect($stats)->firstWhere('label', 'Upcoming')['value'] ?? 0;
@endphp

@section('title', 'Patient dashboard')

@section('content')
    <section class="dash-banner">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Patient dashboard</p>
        <div class="mt-3 flex flex-wrap items-end justify-between gap-5">
            <div class="max-w-2xl">
                <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Hello, {{ $name }}</h1>
                <p class="mt-3 text-sm leading-6 text-white/80">
                    Your visits, open bookings, and the doctors you can reserve are all in this workspace. You do not need to return to the public site to book.
                </p>
            </div>
            <a href="#book-a-doctor" class="dash-banner-btn">Book a doctor</a>
        </div>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
            <span class="dash-chip">{{ $upcoming }} upcoming</span>
            <span class="dash-chip">Patient account</span>
        </div>
    </section>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            @include('dashboard.partials.stat', ['stat' => $stat])
        @endforeach
    </div>

    <div class="mt-6">
        @include('dashboard.patient.partials.book-form')
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,0.9fr)]">
        <section class="dash-panel">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-6 py-4">
                <div>
                    <h2 class="text-lg font-bold text-navy">My appointments</h2>
                    <p class="mt-1 text-sm text-text">The latest visits on your record, newest first.</p>
                </div>
                <a href="{{ route('patient.bookings') }}" class="text-sm font-semibold text-primary no-underline hover:text-primary-hover">View all</a>
            </div>

            <div class="divide-y divide-line">
                @forelse ($appointments as $appointment)
                    <article class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-navy">{{ $appointment['doctor'] }}</h3>
                            <p class="mt-0.5 text-sm text-text">{{ $appointment['specialization'] }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <p class="text-sm font-medium text-navy">{{ $appointment['date'] }} · {{ $appointment['time'] }}</p>
                            <span class="{{ $statusClass[$appointment['status']] ?? 'badge-info' }}">
                                {{ $appointment['status'] }}
                            </span>
                        </div>
                    </article>
                @empty
                    <p class="px-6 py-8 text-sm leading-6 text-text">You do not have any appointments yet.</p>
                @endforelse
            </div>
        </section>

        <section class="dash-panel p-6">
            <h2 class="text-lg font-bold text-navy">After you book</h2>
            <p class="mt-1 text-sm leading-6 text-text">The visit shows in My appointments once it is saved. Open a booking to see payment, reports, and cancellation.</p>
            <a href="{{ route('patient.bookings') }}" class="mt-5 inline-flex text-sm font-semibold text-primary no-underline hover:text-primary-hover">View all bookings</a>
        </section>
    </div>
@endsection
