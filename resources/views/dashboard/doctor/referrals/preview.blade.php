@extends('layouts.dashboard')

@php
    $referral = $referral ?? [];
    $statusClass = [
        'Sent' => 'badge-info',
        'Accepted' => 'badge-success',
        'Declined' => 'badge-danger',
        'Appointment booked' => 'badge-success',
    ];
    $outgoing = ($referral['direction'] ?? '') === 'Outgoing';
    $doctor = $referral['name'] ?? 'Doctor';
    $specialty = $referral['specialization'] ?? '';
    $doctorLabel = trim($doctor.($specialty !== '' ? ' ('.$specialty.')' : ''));
@endphp

@section('title', 'Referral')

@section('content')
    <x-ui.page-header
        title="{{ $referral['patient'] ?? 'Patient' }}"
        description="{{ $outgoing ? 'To '.$doctorLabel : 'From '.$doctorLabel }} · {{ $referral['sent'] ?? '' }}"
    >
        <span class="badge-info">{{ $referral['urgency'] ?? 'Routine' }}</span>
        <span class="{{ $statusClass[$referral['status'] ?? ''] ?? 'badge-info' }}">{{ $referral['status'] ?? 'Sent' }}</span>
        <a href="{{ route('doctor.referrals') }}" class="btn-secondary">Back</a>
    </x-ui.page-header>

    <section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
        <h2 class="text-lg font-bold text-[#0B1F3A]">Referral</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @include('dashboard.partials.detail', ['label' => 'Patient', 'value' => $referral['patient'] ?? null])
            @include('dashboard.partials.detail', ['label' => $outgoing ? 'Referred to' : 'Referred by', 'value' => $doctorLabel])
            @include('dashboard.partials.detail', ['label' => 'Status', 'value' => $referral['status'] ?? null])
            @include('dashboard.partials.detail', ['label' => 'Sent', 'value' => $referral['sent'] ?? null])
        </div>
        <p class="mt-6 text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Reason.</span>
            {{ $referral['reason'] ?? 'Not recorded' }}
        </p>
        @if (filled($referral['question'] ?? null))
            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#53647D]">
                <span class="font-semibold text-[#0B1F3A]">Clinical question.</span>
                {{ $referral['question'] }}
            </p>
        @endif
        @if (filled($referral['clinic'] ?? null))
            <p class="mt-3 text-sm leading-6 text-[#53647D]">
                <span class="font-semibold text-[#0B1F3A]">Clinic.</span>
                {{ $referral['clinic'] }}
            </p>
        @endif
    </section>

    @include('dashboard.doctor.referrals.partials.clinical-packet', [
        'summary' => $summary ?? [],
        'reports' => $reports ?? [],
    ])

    <p class="mt-6 text-sm leading-6 text-[#53647D]">
        @if ($outgoing && ($referral['status'] ?? '') === 'Sent')
            Waiting for {{ $doctorLabel }} to accept or decline.
        @elseif (($referral['status'] ?? '') === 'Accepted')
            {{ $doctorLabel }} accepted. They can book the visit from their schedule.
        @elseif (($referral['status'] ?? '') === 'Appointment booked')
            {{ $doctorLabel }} booked the referred visit.
        @elseif (($referral['status'] ?? '') === 'Declined')
            {{ $doctorLabel }} declined this referral.
        @else
            This referral is waiting for a decision.
        @endif
    </p>
@endsection
