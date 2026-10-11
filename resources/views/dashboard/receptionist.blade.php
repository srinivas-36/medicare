@extends('layouts.dashboard')

@php
    $user = auth()->user();
    $name = $user->name ?? 'there';

    $stats = $stats ?? [
        ['label' => 'New patients', 'value' => '8', 'note' => '+2 from yesterday'],
        ['label' => 'Pending approvals', 'value' => '5', 'note' => '1 less than yesterday'],
        ['label' => 'Messages', 'value' => '12', 'note' => '+4 from yesterday'],
        ['label' => 'Follow-ups', 'value' => '15', 'note' => '+2 from yesterday'],
    ];

    $appointments = $appointments ?? [
        ['patient' => 'John Smith', 'doctor' => 'Dr. Sarah Wilson', 'time' => '09:00 AM', 'type' => 'Check-up', 'status' => 'Confirmed'],
        ['patient' => 'Maria Garcia', 'doctor' => 'Dr. Robert Kim', 'time' => '10:30 AM', 'type' => 'Consultation', 'status' => 'Confirmed'],
        ['patient' => 'David Johnson', 'doctor' => 'Dr. Emily Chen', 'time' => '11:15 AM', 'type' => 'Follow-up', 'status' => 'Pending'],
        ['patient' => 'Sarah Williams', 'doctor' => 'Dr. Michael Brown', 'time' => '02:00 PM', 'type' => 'Emergency', 'status' => 'Confirmed'],
        ['patient' => 'James Miller', 'doctor' => 'Dr. Lisa Taylor', 'time' => '03:30 PM', 'type' => 'Check-up', 'status' => 'Cancelled'],
    ];

    $tasks = $tasks ?? [
        ['task' => 'Verify insurance for patient #1234', 'priority' => 'High', 'due' => 'Today'],
        ['task' => 'Schedule follow-up for Maria Garcia', 'priority' => 'Medium', 'due' => 'Tomorrow'],
        ['task' => 'Update patient records', 'priority' => 'Low', 'due' => 'This week'],
        ['task' => 'Order medical supplies', 'priority' => 'Medium', 'due' => 'Tomorrow'],
    ];

    $statusClass = [
        'Confirmed' => 'bg-[#e8f8ef] text-[#0f9f6e]',
        'Pending' => 'bg-[#fff7e8] text-[#b7791f]',
        'Cancelled' => 'bg-red-50 text-red-600',
    ];

    $priorityClass = [
        'High' => 'bg-red-50 text-red-600',
        'Medium' => 'bg-[#fff7e8] text-[#b7791f]',
        'Low' => 'bg-[#e8f8ef] text-[#0f9f6e]',
    ];
@endphp

@section('content')
    <div class="rounded-2xl bg-[#0067FF] p-6 text-white shadow-[0_12px_40px_rgba(0,103,255,0.22)] sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Reception dashboard</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight">Welcome back, {{ $name }}</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/85">
            Today’s front desk: new patients, approvals, messages, and the appointments on the board.
        </p>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($stats as $stat)
            @include('dashboard.partials.stat', ['stat' => $stat])
        @endforeach
    </div>

    <section class="mt-6 overflow-hidden rounded-2xl border border-[#0066ff1f] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <div class="border-b border-[#0066ff1f] px-6 py-4">
            <h2 class="text-lg font-bold text-[#181A1E]">Today’s appointments ({{ count($appointments) }})</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-[#f7faff] text-[#4E545F]">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Patient</th>
                        <th class="px-6 py-3 font-semibold">Doctor</th>
                        <th class="px-6 py-3 font-semibold">Time</th>
                        <th class="px-6 py-3 font-semibold">Type</th>
                        <th class="px-6 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#0066ff1f]">
                    @forelse ($appointments as $appointment)
                        <tr class="hover:bg-[#f7faff]">
                            <td class="px-6 py-4 font-semibold text-[#181A1E]">{{ $appointment['patient'] }}</td>
                            <td class="px-6 py-4 text-[#181A1E]">{{ $appointment['doctor'] }}</td>
                            <td class="px-6 py-4 text-[#181A1E]">{{ $appointment['time'] }}</td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-[#e8f2ff] px-2.5 py-1 text-xs font-semibold text-[#0067FF]">{{ $appointment['type'] }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$appointment['status']] ?? 'bg-[#f7faff] text-[#4E545F]' }}">
                                    {{ $appointment['status'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-[#4E545F]">No appointments on the board today.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]">
        <section class="rounded-2xl border border-[#0066ff1f] bg-white shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <div class="border-b border-[#0066ff1f] px-6 py-4">
                <h2 class="text-lg font-bold text-[#181A1E]">Pending tasks</h2>
            </div>
            <ul class="space-y-3 p-4">
                @forelse ($tasks as $task)
                    <li class="rounded-xl border border-[#0066ff1f] px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <p class="font-semibold text-[#181A1E]">{{ $task['task'] }}</p>
                            <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold {{ $priorityClass[$task['priority']] ?? 'bg-[#f7faff] text-[#4E545F]' }}">
                                {{ $task['priority'] }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-[#4E545F]">Due: {{ $task['due'] }}</p>
                    </li>
                @empty
                    <li class="px-2 py-4 text-sm text-[#4E545F]">No pending tasks.</li>
                @endforelse
            </ul>
        </section>

        <section class="rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <h2 class="text-lg font-bold text-[#181A1E]">Quick actions</h2>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @foreach ([
                    ['title' => 'Schedule appointment', 'text' => 'Book a new visit'],
                    ['title' => 'Register patient', 'text' => 'Add a new patient'],
                    ['title' => 'Send message', 'text' => 'Contact a patient or doctor'],
                    ['title' => 'Daily reports', 'text' => 'Review today’s desk activity'],
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