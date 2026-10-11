@extends('layouts.dashboard')

@php
    $user = auth()->user();
    $name = $user->name ?? 'Doctor';
    $isPending = data_get($doctor ?? $user, 'isApproved') === 'pending';

    $stats = $stats ?? [
        ['label' => 'Referrals sent', 'value' => '8', 'note' => '+1 from yesterday'],
        ['label' => 'Referrals received', 'value' => '10', 'note' => '+1 from yesterday'],
        ['label' => 'Total payable', 'value' => '$2,450', 'note' => '+$120 from yesterday'],
        ['label' => 'Total receivable', 'value' => '$3,850', 'note' => '+$210 from yesterday'],
    ];

    $appointments = $appointments ?? [
        ['patient' => 'John Smith', 'condition' => 'Hypertension', 'time' => '09:00 AM', 'status' => 'Confirmed'],
        ['patient' => 'Maria Garcia', 'condition' => 'Diabetes', 'time' => '10:30 AM', 'status' => 'Confirmed'],
        ['patient' => 'Robert Johnson', 'condition' => 'Asthma', 'time' => '11:15 AM', 'status' => 'Pending'],
        ['patient' => 'Sarah Wilson', 'condition' => 'Arthritis', 'time' => '02:00 PM', 'status' => 'Confirmed'],
    ];

    $statusClass = [
        'Confirmed' => 'bg-[#e8f8ef] text-[#0f9f6e]',
        'Pending' => 'bg-[#fff7e8] text-[#b7791f]',
        'Cancelled' => 'bg-red-50 text-red-600',
    ];
@endphp

@section('content')
    <div class="rounded-2xl bg-[#0067FF] p-6 text-white shadow-[0_12px_40px_rgba(0,103,255,0.22)] sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Doctor dashboard</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight">Good day, {{ $name }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/85">
            A snapshot of today’s appointments, referrals, and payments. The other screens will be added one by one.
        </p>
    </div>

    @if ($isPending)
        <div class="mt-6 rounded-2xl border border-[#f3d7a1] bg-[#fff7e8] px-5 py-4 text-sm leading-6 text-[#8a5a12]">
            To get approval, complete your profile. We’ll review it manually and approve within 3 days.
        </div>
    @endif

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            @include('dashboard.partials.stat', ['stat' => $stat])
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(260px,0.8fr)]">
        <section class="overflow-hidden rounded-2xl border border-[#0066ff1f] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <div class="border-b border-[#0066ff1f] px-6 py-4">
                <h2 class="text-lg font-bold text-[#181A1E]">Appointments today ({{ count($appointments) }})</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-[#f7faff] text-[#4E545F]">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Patient</th>
                            <th class="px-6 py-3 font-semibold">Condition</th>
                            <th class="px-6 py-3 font-semibold">Time</th>
                            <th class="px-6 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#0066ff1f]">
                        @forelse ($appointments as $appointment)
                            <tr class="hover:bg-[#f7faff]">
                                <td class="px-6 py-4 font-semibold text-[#181A1E]">{{ $appointment['patient'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full bg-[#e8f2ff] px-2.5 py-1 text-xs font-semibold text-[#0067FF]">{{ $appointment['condition'] }}</span>
                                </td>
                                <td class="px-6 py-4 text-[#181A1E]">{{ $appointment['time'] }}</td>
                                <td class="px-6 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$appointment['status']] ?? 'bg-[#f7faff] text-[#4E545F]' }}">
                                        {{ $appointment['status'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-[#4E545F]">No appointments scheduled for today.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <h2 class="text-lg font-bold text-[#181A1E]">Quick actions</h2>
            <div class="mt-5 space-y-3">
                @foreach ([
                    ['title' => 'Schedule appointment', 'text' => 'Create a new booking'],
                    ['title' => 'Send referral', 'text' => 'Refer a patient to another doctor'],
                    ['title' => 'View reports', 'text' => 'Analytics and insights'],
                ] as $action)
                    <div class="rounded-xl bg-[#f7faff] px-4 py-4">
                        <p class="font-semibold text-[#181A1E]">{{ $action['title'] }}</p>
                        <p class="mt-0.5 text-sm text-[#4E545F]">{{ $action['text'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
@endsection
