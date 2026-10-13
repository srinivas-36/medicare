@extends('layouts.public')

@php
    $paid = $appointment->payment_status === 'paid';
    $doctorName = $appointment->doctor?->user?->name ?? 'your doctor';
    $when = $appointment->appointment_date?->format('l, F j, Y');
    $start = \Illuminate\Support\Carbon::parse((string) $appointment->appointment_time)->format('g:i A');
    $end = filled($appointment->ending_time)
        ? \Illuminate\Support\Carbon::parse((string) $appointment->ending_time)->format('g:i A')
        : null;
    $window = $end ? $start.' – '.$end : $start;
    $fee = number_format((float) $appointment->ticket_price, 2);
@endphp

@section('title', 'Appointment payment')

@section('content')
    <section class="relative overflow-hidden bg-page py-14 lg:py-20">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-[#e8f2ff] to-transparent"></div>

        <div class="relative mx-auto w-full max-w-[1170px] px-5">
            <div class="mx-auto max-w-xl overflow-hidden rounded-[28px] border border-line bg-white shadow-card">
                <div class="bg-gradient-to-br from-navy via-[#12315c] to-primary px-8 py-8 text-white">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/15">
                        <x-ui.icon name="{{ $paid ? 'check' : 'clock' }}" class="h-6 w-6" />
                    </span>
                    <h1 class="mt-5 text-3xl font-extrabold tracking-tight">{{ $paid ? 'Appointment confirmed' : 'Payment received' }}</h1>
                    <p class="mt-2 text-sm text-white/80">{{ $doctorName }}</p>
                </div>

                <div class="space-y-5 p-8 text-sm leading-6 text-ink">
                    @if ($paid)
                        <dl class="divide-y divide-line rounded-2xl border border-line">
                            <div class="flex items-center justify-between gap-4 px-4 py-3">
                                <dt class="font-semibold text-muted">Date</dt>
                                <dd class="text-right font-semibold text-navy">{{ $when }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-4 py-3">
                                <dt class="font-semibold text-muted">Time</dt>
                                <dd class="text-right font-semibold text-navy">{{ $window }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 px-4 py-3">
                                <dt class="font-semibold text-muted">Fee</dt>
                                <dd class="text-right font-semibold text-navy">{{ $fee }} USD</dd>
                            </div>
                        </dl>
                    @else
                        <p class="rounded-2xl border border-[#c9dcff] bg-[#e8f2ff] px-4 py-4 leading-6">Payment received. Confirmation is updating.</p>
                    @endif

                    <a href="{{ route('patient.bookings') }}" class="btn-primary w-full">My bookings</a>
                </div>
            </div>
        </div>
    </section>
@endsection
