@extends('layouts.dashboard')

@php
    $user = auth()->user();
    $name = $user->name ?? 'there';

    $stats = $stats ?? [
        ['label' => 'Upcoming', 'value' => '2', 'note' => 'Appointments scheduled'],
        ['label' => 'Completed', 'value' => '6', 'note' => 'Visits finished'],
        ['label' => 'Pending', 'value' => '1', 'note' => 'Awaiting confirmation'],
        ['label' => 'Doctors', 'value' => '3', 'note' => 'You have visited'],
    ];

    $appointments = $appointments ?? [
        ['doctor' => 'Dr. Alfaz Ahmed', 'specialization' => 'Surgeon', 'date' => '12 Oct 2026', 'time' => '10:30 AM', 'status' => 'Confirmed'],
        ['doctor' => 'Dr. Saleh Mahmud', 'specialization' => 'Neurologist', 'date' => '18 Oct 2026', 'time' => '02:15 PM', 'status' => 'Pending'],
        ['doctor' => 'Dr. Farid Uddin', 'specialization' => 'Dermatologist', 'date' => '02 Oct 2026', 'time' => '11:00 AM', 'status' => 'Completed'],
    ];

    $statusClass = [
        'Confirmed' => 'bg-[#e8f8ef] text-[#0f9f6e]',
        'Pending' => 'bg-[#fff7e8] text-[#b7791f]',
        'Completed' => 'bg-[#e8f2ff] text-[#0067FF]',
        'Cancelled' => 'bg-red-50 text-red-600',
    ];

    $doctorsUrl = \Illuminate\Support\Facades\Route::has('doctors.index') ? route('doctors.index') : url('/doctors');
    $contactUrl = \Illuminate\Support\Facades\Route::has('contact') ? route('contact') : url('/contact');
@endphp

@section('content')
    <div class="rounded-2xl bg-[#0067FF] p-6 text-white shadow-[0_12px_40px_rgba(0,103,255,0.22)] sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Patient dashboard</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight">Hello, {{ $name }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/85">
            See your upcoming visits, recent bookings, and the next step for your care.
        </p>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            @include('dashboard.partials.stat', ['stat' => $stat])
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.6fr)_minmax(280px,0.8fr)]">
        <section class="rounded-2xl border border-[#0066ff1f] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <div class="border-b border-[#0066ff1f] px-6 py-4">
                <h2 class="text-lg font-bold text-[#181A1E]">My appointments</h2>
                <p class="mt-1 text-sm text-[#4E545F]">Your latest bookings. The full bookings screen comes next.</p>
            </div>

            <div class="divide-y divide-[#0066ff1f]">
                @forelse ($appointments as $appointment)
                    <article class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h3 class="font-semibold text-[#181A1E]">{{ $appointment['doctor'] }}</h3>
                            <p class="mt-0.5 text-sm text-[#4E545F]">{{ $appointment['specialization'] }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <p class="text-sm font-medium text-[#181A1E]">{{ $appointment['date'] }} · {{ $appointment['time'] }}</p>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$appointment['status']] ?? 'bg-[#f7faff] text-[#4E545F]' }}">
                                {{ $appointment['status'] }}
                            </span>
                        </div>
                    </article>
                @empty
                    <p class="px-6 py-8 text-sm leading-6 text-[#4E545F]">You do not have any appointments yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <h2 class="text-lg font-bold text-[#181A1E]">Quick actions</h2>
            <p class="mt-1 text-sm leading-6 text-[#4E545F]">Book a visit or reach the clinic from here.</p>

            <div class="mt-5 space-y-3">
                <a href="{{ $doctorsUrl }}" class="flex items-center justify-between rounded-xl bg-[#f7faff] px-4 py-4 hover:bg-[#e8f2ff]">
                    <span>
                        <span class="block font-semibold text-[#181A1E]">Find a doctor</span>
                        <span class="mt-0.5 block text-sm text-[#4E545F]">Browse specialists and open a booking</span>
                    </span>
                    <span class="text-[#0067FF]" aria-hidden="true">→</span>
                </a>
                <a href="{{ $contactUrl }}" class="flex items-center justify-between rounded-xl bg-[#f7faff] px-4 py-4 hover:bg-[#e8f2ff]">
                    <span>
                        <span class="block font-semibold text-[#181A1E]">Contact the clinic</span>
                        <span class="mt-0.5 block text-sm text-[#4E545F]">Questions about a visit or a booking</span>
                    </span>
                    <span class="text-[#0067FF]" aria-hidden="true">→</span>
                </a>
            </div>
        </section>
    </div>
@endsection
