@extends('layouts.dashboard')

@php
    $role = $role ?? 'patient';
    $filters = $filters ?? [
        'date' => '',
        'startingTime' => '',
        'endingTime' => '',
        'status' => '',
        'q' => '',
    ];
    $slots = $slots ?? [];
    $filtersActive = $filtersActive ?? false;
    $hasAny = $hasAny ?? false;
    $listRoute = $listRoute ?? ($role === 'doctor' ? 'doctor.appointments' : 'patient.bookings');
    $showRoute = $showRoute ?? ($role === 'doctor' ? 'doctor.appointments.show' : 'patient.bookings.show');
    $inputClass = 'h-12 w-full rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 text-[#0B1F3A] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';
    $selectedSlot = ($filters['startingTime'] ?? '') !== '' && ($filters['endingTime'] ?? '') !== ''
        ? $filters['startingTime'].'|'.$filters['endingTime']
        : '';
    $query = array_filter([
        'date' => $filters['date'] ?? '',
        'startingTime' => $filters['startingTime'] ?? '',
        'endingTime' => $filters['endingTime'] ?? '',
        'status' => $filters['status'] ?? '',
        'q' => $filters['q'] ?? '',
        'page' => $appointments->currentPage() > 1 ? $appointments->currentPage() : null,
    ], fn ($value) => $value !== null && $value !== '');
    $count = $appointments->total();
@endphp

@section('content')
    <style>
        .appointment-table tbody tr { position: relative; }
        .appointment-table tbody tr .row-link::after {
            content: "";
            position: absolute;
            inset: 0;
        }
    </style>

    <section class="dash-banner">
        @if ($role === 'doctor')
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Appointments</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Your appointments</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">Review the visits patients have booked with you. Filter by date, time, or status, then open a row for the full record.</p>
        @else
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">My bookings</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight">Your bookings</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">Review the visits you have booked with a doctor. Open a row for payment, reports, and cancellation.</p>
        @endif
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
            <span class="dash-chip">{{ $count }} {{ \Illuminate\Support\Str::plural('appointment', $count) }}</span>
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
        <h2 class="text-xl font-bold text-[#181A1E]">Filters</h2>

        <form method="GET" action="{{ route($listRoute) }}" class="mt-6 grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div>
                <label for="appointment-date" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Date</label>
                <input id="appointment-date" type="date" name="date" value="{{ $filters['date'] }}" class="{{ $inputClass }}">
            </div>

            <div>
                <label for="appointment-slot" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Time slot</label>
                <select id="appointment-slot" name="slot" class="{{ $inputClass }}">
                    <option value="">All</option>
                    @foreach ($slots as $slot)
                        <option value="{{ $slot['value'] }}" @selected($selectedSlot === $slot['value'])>{{ $slot['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="appointment-status" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Status</label>
                <select id="appointment-status" name="status" class="{{ $inputClass }}">
                    <option value="">All</option>
                    @foreach (\App\Support\AppointmentPage::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="appointment-search" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Search</label>
                <input
                    id="appointment-search"
                    type="search"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="{{ $role === 'doctor' ? 'Patient name' : 'Doctor name' }}"
                    class="{{ $inputClass }}"
                >
            </div>

            <div class="flex flex-wrap items-center gap-4 sm:col-span-2 xl:col-span-4">
                <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#1769FF] px-6 font-semibold text-white">Filter</button>
                <a href="{{ route($listRoute) }}" class="text-sm font-semibold text-[#1769FF]">Clear filters</a>
            </div>
        </form>
    </section>

    <p class="mt-6 text-sm font-semibold text-[#4E545F]">{{ $count }} {{ \Illuminate\Support\Str::plural('appointment', $count) }}</p>

    <section class="mt-4 overflow-hidden rounded-2xl border border-[#0066ff1f] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <div class="overflow-x-auto">
            <table class="appointment-table w-full min-w-[640px] text-left text-sm">
                <thead class="bg-[#f7faff] text-[#4E545F]">
                    <tr>
                        @if ($role === 'doctor')
                            <th class="px-6 py-3 font-semibold">Patient</th>
                        @else
                            <th class="px-6 py-3 font-semibold">Doctor</th>
                            <th class="px-6 py-3 font-semibold">Specialization</th>
                        @endif
                        <th class="px-6 py-3 font-semibold">Date</th>
                        <th class="px-6 py-3 font-semibold">Time slot</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Payment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#0066ff1f]">
                    @forelse ($appointments as $appointment)
                        @php
                            $href = route($showRoute, array_merge(['appointment' => $appointment], $query));
                            $status = \App\Support\AppointmentPage::status($appointment->status);
                            $payment = \App\Support\AppointmentPage::payment($appointment->payment_status);
                            $primary = $role === 'doctor'
                                ? ($appointment->patient?->name ?: 'Patient')
                                : ($appointment->doctor?->user?->name ?: 'Doctor');
                        @endphp
                        <tr class="hover:bg-[#f7faff]">
                            <td class="relative px-6 py-4 font-semibold text-[#181A1E]">
                                <a href="{{ $href }}" class="row-link text-[#181A1E]">{{ $primary }}</a>
                            </td>
                            @if ($role === 'patient')
                                <td class="px-6 py-4 text-[#181A1E]">{{ $appointment->doctor?->specialization ?: 'Not provided' }}</td>
                            @endif
                            <td class="px-6 py-4 text-[#181A1E]">{{ \App\Support\AppointmentPage::date($appointment->appointment_date) }}</td>
                            <td class="px-6 py-4 text-[#181A1E]">{{ \App\Support\AppointmentPage::timeSlot($appointment) }}</td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $payment['class'] }}">{{ $payment['label'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $role === 'patient' ? 6 : 5 }}" class="px-6 py-8 text-[#4E545F]">
                                {{ $filtersActive && $hasAny ? 'No appointments match these filters.' : 'You do not have any appointments yet.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if ($appointments->lastPage() > 1)
        <nav class="mt-6 flex flex-wrap items-center gap-2" aria-label="Pagination">
            @if ($appointments->onFirstPage())
                <span class="inline-flex h-10 items-center rounded-xl px-4 text-sm font-semibold text-[#4E545F]">Previous</span>
            @else
                <a href="{{ $appointments->previousPageUrl() }}" class="inline-flex h-10 items-center rounded-xl border border-[#0066ff1f] bg-white px-4 text-sm font-semibold text-[#181A1E]">Previous</a>
            @endif

            @for ($page = 1; $page <= $appointments->lastPage(); $page++)
                <a
                    href="{{ $appointments->url($page) }}"
                    @class([
                        'inline-flex h-10 min-w-10 items-center justify-center rounded-xl px-3 text-sm font-semibold',
                        'bg-[#0067FF] text-white' => $appointments->currentPage() === $page,
                        'border border-[#0066ff1f] bg-white text-[#181A1E]' => $appointments->currentPage() !== $page,
                    ])
                >{{ $page }}</a>
            @endfor

            @if ($appointments->hasMorePages())
                <a href="{{ $appointments->nextPageUrl() }}" class="inline-flex h-10 items-center rounded-xl border border-[#0066ff1f] bg-white px-4 text-sm font-semibold text-[#181A1E]">Next</a>
            @else
                <span class="inline-flex h-10 items-center rounded-xl px-4 text-sm font-semibold text-[#4E545F]">Next</span>
            @endif
        </nav>
    @endif
@endsection
