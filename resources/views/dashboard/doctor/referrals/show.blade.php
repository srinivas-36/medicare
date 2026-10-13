@extends('layouts.dashboard')

@php
    $snapshot = $snapshot ?? [];
    $patient = $snapshot['patient'] ?? [];
    $from = $snapshot['referring_doctor'] ?? [];
    $visit = $snapshot['visit'] ?? [];
    $reports = $referral->reports ?? collect();

    $statusClass = [
        'Sent' => 'badge-info',
        'Accepted' => 'badge-success',
        'Declined' => 'badge-danger',
    ];
@endphp

@section('content')
    <div class="dash-banner">
        <a href="{{ route('doctor.referrals') }}" class="text-sm font-semibold text-white/80 hover:text-white">Back to referrals</a>
        <div class="mt-3 flex flex-wrap items-center gap-3">
            <h1 class="text-3xl font-extrabold leading-tight">{{ $patient['name'] ?? 'Patient' }}</h1>
            <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-wide">{{ $status }}</span>
        </div>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            From {{ $from['name'] ?? 'Doctor' }} ({{ $from['specialization'] ?? '' }}) · {{ $sent }}
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ $status }}</span>
            <span class="dash-chip">{{ $sent }}</span>
            @if (! empty($from['specialization']))
                <span class="dash-chip">{{ $from['specialization'] }}</span>
            @endif
        </div>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif

    <section class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <h2 class="text-lg font-bold text-[#0B1F3A]">Patient</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-3">
            @include('dashboard.partials.detail', ['label' => 'Name', 'value' => $patient['name'] ?? null])
            @include('dashboard.partials.detail', ['label' => 'Age', 'value' => $patient['age'] ?? null])
            @include('dashboard.partials.detail', ['label' => 'Blood type', 'value' => $patient['blood_type'] ?? null])
        </div>
        <div class="mt-6">
            @include('dashboard.partials.health-notes', ['healthNotes' => $patient['health_notes'] ?? []])
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <h2 class="text-lg font-bold text-[#0B1F3A]">Visit snapshot</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @include('dashboard.partials.detail', ['label' => 'Date', 'value' => $visit['date'] ?? null])
            @include('dashboard.partials.detail', ['label' => 'Reason', 'value' => $visit['reason'] ?? null])
        </div>
        <p class="mt-6 whitespace-pre-line text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Notes.</span>
            {{ $visit['notes'] ?? 'Not recorded' }}
        </p>
        <p class="mt-3 text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Diagnosis.</span>
            {{ $visit['diagnosis'] ?? 'Not recorded' }}
        </p>
        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Plan.</span>
            {{ $visit['plan'] ?? 'Not recorded' }}
        </p>
        <p class="mt-6 text-sm leading-6 text-[#53647D]">
            <span class="font-semibold text-[#0B1F3A]">Referral reason.</span>
            {{ $referral->reason }}
        </p>
    </section>

    @if (filled($snapshot['ai_summary'] ?? null) || filled($snapshot['referral_brief'] ?? null))
        <section class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <h2 class="text-lg font-bold text-[#0B1F3A]">Analysis snapshot</h2>
            <p class="mt-2 text-sm leading-6 text-[#53647D]">This is a draft for the doctor, generated from the note and file names.</p>
            @if (filled($snapshot['ai_summary'] ?? null))
                <p class="mt-4 text-sm font-semibold leading-6 text-[#0B1F3A]">{{ $snapshot['ai_summary'] }}</p>
            @endif
            @if (filled($snapshot['referral_brief'] ?? null))
                <p class="mt-3 whitespace-pre-line text-sm leading-6 text-[#53647D]">{{ $snapshot['referral_brief'] }}</p>
            @endif
        </section>
    @endif

    <section class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <h2 class="text-lg font-bold text-[#0B1F3A]">Attached reports</h2>
        @if ($reports->isEmpty())
            <p class="mt-3 text-sm leading-6 text-[#53647D]">No reports were attached to this visit.</p>
        @else
            <div class="mt-4 space-y-3">
                @foreach ($reports as $report)
                    <a href="{{ route('doctor.referrals.reports.show', [$referral, $report]) }}" class="flex items-center justify-between gap-3 rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 py-3 hover:border-[#1769FF]">
                        <span>
                            <span class="block text-sm font-semibold text-[#0B1F3A]">{{ $report->original_name }}</span>
                            <span class="mt-0.5 block text-xs font-semibold text-[#53647D]">{{ $report->group_name }}</span>
                        </span>
                        <x-ui.icon name="download" class="h-4 w-4 shrink-0 text-[#1769FF]" />
                    </a>
                @endforeach
            </div>
        @endif
    </section>

    @if ($referral->status === 'sent')
        <div class="mt-6 flex flex-wrap gap-3">
            <form method="POST" action="{{ route('doctor.referrals.accept', $referral) }}">
                @csrf
                <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#1769FF] px-6 font-semibold text-white">Accept</button>
            </form>
            <form method="POST" action="{{ route('doctor.referrals.decline', $referral) }}">
                @csrf
                <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-red-50 px-6 font-semibold text-red-600">Decline</button>
            </form>
        </div>
    @endif
@endsection
