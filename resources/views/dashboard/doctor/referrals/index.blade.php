@extends('layouts.dashboard')

@php
    $referrals = $referrals ?? [];

    $statusClass = [
        'Sent' => 'badge-info',
        'Accepted' => 'badge-success',
        'Declined' => 'badge-danger',
    ];
@endphp

@section('content')
    <section class="dash-banner">
        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Referrals</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight">Incoming referrals</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            Open a referral to read the clinical snapshot, reports, and the decision waiting on you.
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ count($referrals) }} {{ \Illuminate\Support\Str::plural('referral', count($referrals)) }}</span>
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
        </div>
    </section>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif

    <section class="mt-6 overflow-hidden rounded-2xl border border-[#E2EAF4] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-[#F4F7FB] text-[#53647D]">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Patient</th>
                        <th class="px-6 py-3 font-semibold">From</th>
                        <th class="px-6 py-3 font-semibold">Sent</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                        <th class="px-6 py-3 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E2EAF4]">
                    @forelse ($referrals as $referral)
                        <tr class="hover:bg-[#F4F7FB]">
                            <td class="px-6 py-4 font-semibold text-[#0B1F3A]">
                                <a href="{{ route('doctor.referrals.show', $referral['id']) }}" class="hover:text-[#1769FF]">{{ $referral['patient'] }}</a>
                            </td>
                            <td class="px-6 py-4 text-[#0B1F3A]">
                                {{ $referral['from'] }}
                                @if ($referral['specialization'] !== '')
                                    <span class="text-[#53647D]">({{ $referral['specialization'] }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-[#0B1F3A]">{{ $referral['sent'] }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$referral['status']] ?? 'bg-[#F4F7FB] text-[#53647D]' }}">{{ $referral['status'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('doctor.referrals.show', $referral['id']) }}" class="text-sm font-semibold text-[#1769FF]">Open</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-[#53647D]">No referrals have been sent to you.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
