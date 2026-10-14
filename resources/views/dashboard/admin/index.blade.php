@extends('layouts.dashboard')

@php
    $trend = $trend ?? [];
    $maxTrend = max(1, ...array_map(fn ($point) => (int) ($point['value'] ?? 0), $trend ?: [['value' => 0]]));
    $breakdown = $todayBreakdown ?? [];
    $breakdownTotal = array_sum($breakdown);
    $tones = [
        'Confirmed' => '#0067FF',
        'Checked in' => '#0EA5E9',
        'Completed' => '#15803D',
        'Pending' => '#D97706',
    ];
    $cursor = 0;
    $stops = [];
    foreach ($breakdown as $label => $value) {
        if ($breakdownTotal === 0 || $value === 0) {
            continue;
        }
        $start = $cursor;
        $cursor += ($value / $breakdownTotal) * 100;
        $stops[] = ($tones[$label] ?? '#94A3B8').' '.$start.'% '.$cursor.'%';
    }
    $donut = $stops === [] ? 'conic-gradient(#E2E8F0 0 100%)' : 'conic-gradient('.implode(', ', $stops).')';
@endphp

@section('title', 'Administration')

@section('content')
    <x-ui.page-header title="System overview" description="Counts from the clinic database. Clinical notes are not shown here.">
        <a href="{{ route('admin.doctors') }}" class="btn-primary">Review doctors</a>
    </x-ui.page-header>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($headline as $stat)
            <x-ui.metric :label="$stat['label']" :value="$stat['value']" :tone="$stat['tone']" :href="$stat['href'] ?? null" />
        @endforeach
    </div>

    <p class="mt-3 text-sm text-muted">{{ $openReferrals }} open {{ \Illuminate\Support\Str::plural('referral', $openReferrals) }} waiting on a doctor.</p>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        <x-ui.panel title="Appointments, last 7 days">
            <div class="px-4 py-4">
                @if (collect($trend)->sum('value') === 0)
                    <p class="text-sm text-body">No appointments were scheduled in the last 7 clinic days.</p>
                @else
                    <div class="flex h-40 items-end gap-2">
                        @foreach ($trend as $point)
                            <div class="flex min-w-0 flex-1 flex-col items-center justify-end gap-2">
                                <span class="text-[11px] font-semibold tabular-nums text-heading">{{ $point['value'] }}</span>
                                <div class="flex w-full items-end justify-center" style="height: 7.5rem">
                                    <div class="w-full max-w-8 rounded-t-md bg-[#0067FF]" style="height: {{ max(4, (int) round(($point['value'] / $maxTrend) * 100)) }}%"></div>
                                </div>
                                <span class="text-[11px] font-medium text-muted">{{ $point['label'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </x-ui.panel>

        <x-ui.panel title="Today’s visit status">
            <div class="flex items-center gap-5 px-4 py-4">
                <div class="relative h-28 w-28 shrink-0 rounded-full" style="background: {{ $donut }}" role="img" aria-label="Today’s appointment status">
                    <div class="absolute inset-3 flex items-center justify-center rounded-full bg-white text-center">
                        <span>
                            <span class="block text-lg font-semibold tabular-nums text-heading">{{ $breakdownTotal }}</span>
                            <span class="block text-[11px] text-muted">today</span>
                        </span>
                    </div>
                </div>
                <ul class="min-w-0 flex-1 space-y-2">
                    @foreach ($breakdown as $label => $value)
                        <li class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex items-center gap-2 text-body">
                                <span class="h-2 w-2 rounded-full" style="background: {{ $tones[$label] ?? '#94A3B8' }}"></span>
                                {{ $label }}
                            </span>
                            <span class="font-semibold tabular-nums text-heading">{{ $value }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-ui.panel>
    </div>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        <x-ui.panel title="Pending doctor approvals">
            <x-slot:action>
                <a href="{{ route('admin.doctors') }}" class="text-primary no-underline">Review</a>
            </x-slot:action>
            @if ($pendingDoctors->isEmpty())
                <p class="px-4 py-5 text-sm text-body">No doctor applications are waiting. Approved doctors appear in the public directory.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($pendingDoctors as $doctor)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <x-ui.avatar :name="$doctor->user?->name ?? 'Doctor'" :photo="$doctor->user?->photo" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-heading">{{ $doctor->user?->name ?? 'Doctor' }}</p>
                                    <p class="truncate text-xs text-muted">{{ $doctor->specialization ?: 'Specialization not set' }}</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.doctors') }}" class="shrink-0 text-sm font-semibold text-primary no-underline">Review</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>

        <x-ui.panel title="Recent accounts">
            <x-slot:action>
                <a href="{{ route('admin.users') }}" class="text-primary no-underline">Users</a>
            </x-slot:action>
            @if ($recentAccounts->isEmpty())
                <p class="px-4 py-5 text-sm text-body">No accounts have been created yet.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($recentAccounts as $account)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-heading">{{ $account->name }}</p>
                                <p class="text-xs text-muted">{{ ucfirst($account->role) }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-muted">{{ $account->created_at?->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    </div>
@endsection
