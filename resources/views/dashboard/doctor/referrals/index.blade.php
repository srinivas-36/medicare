@extends('layouts.dashboard')

@php
$referrals = $referrals ?? [];
$outgoing = $outgoing ?? [];

$statusClass = [
'Sent' => 'badge-info',
'Accepted' => 'badge-success',
'Declined' => 'badge-danger',
'Appointment booked' => 'badge-success',
];
@endphp

@section('title', 'Referrals')

@section('content')
<x-ui.page-header title="Referrals" description="Manage incoming and outgoing referrals.">
    <!-- <a href="{{ route('doctor.appointments') }}" class="btn-primary">
        <x-ui.icon name="plus" class="h-4 w-4" />
        Referrals
    </a> -->
</x-ui.page-header>

@if (session('status'))
<p class="mb-4 rounded-lg border border-[#BBF7D0] bg-[#F0FDF4] px-4 py-3 text-sm font-medium text-success">{{ session('status') }}</p>
@endif

@if ($errors->any())
<p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</p>
@endif

<div x-data="{ tab: 'incoming' }">
    <div class="mb-4 flex gap-6 border-b border-[#E2E8F0]" role="tablist">
        <button type="button" role="tab" class="border-b-2 pb-2 text-sm font-semibold" :class="tab === 'incoming' ? 'border-[#0067FF] text-[#0067FF]' : 'border-transparent text-[#64748B]'" @click="tab = 'incoming'">Incoming</button>
        <button type="button" role="tab" class="border-b-2 pb-2 text-sm font-semibold" :class="tab === 'outgoing' ? 'border-[#0067FF] text-[#0067FF]' : 'border-transparent text-[#64748B]'" @click="tab = 'outgoing'">Outgoing</button>
    </div>

    <section class="dash-panel" x-show="tab === 'incoming'">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="text-[13px] text-muted">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Patient</th>
                        <th class="px-4 py-2.5 font-semibold">From</th>
                        <th class="px-4 py-2.5 font-semibold">Reason</th>
                        <th class="px-4 py-2.5 font-semibold">Sent</th>
                        <th class="px-4 py-2.5 font-semibold">Status</th>
                        <th class="px-4 py-2.5 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($referrals as $referral)
                    <tr class="hover:bg-page">
                        <td class="px-4 py-3 font-semibold text-heading">
                            <a href="{{ ! empty($referral['preview']) ? route('doctor.referrals.preview', $referral['id']) : route('doctor.referrals.show', $referral['id']) }}" class="hover:text-primary">{{ $referral['patient'] }}</a>
                        </td>
                        <td class="px-4 py-3 text-heading">
                            {{ $referral['from'] }}
                            @if (($referral['specialization'] ?? '') !== '')
                            <span class="text-body">({{ $referral['specialization'] }})</span>
                            @endif
                        </td>
                        <td class="max-w-xs px-4 py-3 text-body">{{ $referral['reason'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-heading">{{ $referral['sent'] }}</td>
                        <td class="px-4 py-3"><span class="{{ $statusClass[$referral['status']] ?? 'badge-info' }}">{{ $referral['status'] }}</span></td>
                        <td class="px-4 py-3">
                            <a href="{{ ! empty($referral['preview']) ? route('doctor.referrals.preview', $referral['id']) : route('doctor.referrals.show', $referral['id']) }}" class="text-sm font-semibold text-primary no-underline">Review</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-14 text-center">
                            <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#F8FAFC] text-[#94A3B8]">
                                <x-ui.icon name="file" class="h-5 w-5" />
                            </span>
                            <p class="mt-3 text-sm font-semibold text-[#181A1E]">No incoming referrals</p>
                            <p class="mt-1 text-sm text-[#64748B]">Incoming referrals that need a decision will appear here.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="dash-panel" x-show="tab === 'outgoing'" x-cloak>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-[13px] text-muted">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Patient</th>
                        <th class="px-4 py-2.5 font-semibold">To</th>
                        <th class="px-4 py-2.5 font-semibold">Reason</th>
                        <th class="px-4 py-2.5 font-semibold">Sent</th>
                        <th class="px-4 py-2.5 font-semibold">Status</th>
                        <th class="px-4 py-2.5 font-semibold">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($outgoing as $referral)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-heading">
                            @if (! empty($referral['preview']))
                            <a href="{{ route('doctor.referrals.preview', $referral['id']) }}" class="hover:text-primary">{{ $referral['patient'] }}</a>
                            @else
                            {{ $referral['patient'] }}
                            @endif
                        </td>
                        <td class="px-4 py-3 text-heading">{{ $referral['from'] }}</td>
                        <td class="px-4 py-3 text-body">{{ $referral['reason'] }}</td>
                        <td class="px-4 py-3 text-heading">{{ $referral['sent'] }}</td>
                        <td class="px-4 py-3"><span class="{{ $statusClass[$referral['status']] ?? 'badge-info' }}">{{ $referral['status'] }}</span></td>
                        <td class="px-4 py-3">
                            @if (! empty($referral['preview']))
                            <a href="{{ route('doctor.referrals.preview', $referral['id']) }}" class="text-sm font-semibold text-primary no-underline">View</a>
                            @else
                            <span class="text-[#94A3B8]">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-14 text-center">
                            <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-[#F8FAFC] text-[#94A3B8]">
                                <x-ui.icon name="file" class="h-5 w-5" />
                            </span>
                            <p class="mt-3 text-sm font-semibold text-[#181A1E]">No outgoing referrals</p>
                            <p class="mt-1 text-sm text-[#64748B]">You have not sent any referrals yet.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection