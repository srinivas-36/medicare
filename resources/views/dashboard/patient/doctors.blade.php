@extends('layouts.dashboard')

@section('title', 'Book a doctor')

@section('content')
    <section class="dash-banner">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Patient workspace</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Book a doctor</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            Pick a specialist and an open time here. You stay in your dashboard, and the fee is collected when you confirm.
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
            <span class="dash-chip">{{ count($choices ?? []) }} {{ \Illuminate\Support\Str::plural('doctor', count($choices ?? [])) }} available</span>
        </div>
    </section>

    <div class="mt-6">
        @include('dashboard.patient.partials.book-form')
    </div>
@endsection
