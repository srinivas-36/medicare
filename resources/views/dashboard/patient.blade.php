@extends('layouts.dashboard')

@php
    $name = auth()->user()->name ?? 'there';
    $next = $next ?? null;
    $upcoming = $upcoming ?? [];
    $previous = $previous ?? [];
    $updates = $updates ?? [];
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

    $statusClass = [
        'Confirmed' => 'badge-success',
        'Pending' => 'badge-warning',
        'Checked in' => 'badge-info',
        'Completed' => 'badge-info',
        'Cancelled' => 'badge-danger',
        'No-show' => 'badge-danger',
    ];
@endphp

@section('title', 'Overview')

@section('content')
    <header class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm text-muted">{{ $greeting }} · {{ now()->format('l, j F') }}</p>
            <h1 class="mt-0.5 truncate text-xl font-semibold tracking-tight text-heading sm:text-[22px]">Welcome back, {{ $name }}</h1>
        </div>
        <a href="{{ route('patient.book') }}" class="btn-primary">Book an Appointment</a>
    </header>

    <div class="grid items-start gap-4 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,0.85fr)]">
        <section class="panel" aria-labelledby="next-heading">
            <div class="panel-head">
                <h2 id="next-heading" class="text-sm font-semibold text-heading">Next appointment</h2>
                @if ($next)
                    <span class="{{ $statusClass[$next['status']] ?? 'badge-info' }}">{{ $next['status'] }}</span>
                @endif
            </div>

            @if ($next)
                <div class="flex flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center">
                    <x-ui.avatar :name="$next['doctor']" :photo="$next['photo'] ?? null" size="lg" />
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-base font-semibold text-heading">{{ $next['doctor'] }}</p>
                        <p class="mt-0.5 truncate text-sm text-body">{{ $next['specialization'] ?: 'Specialist' }}@if ($next['visit']) · {{ $next['visit'] }}@endif</p>
                        <p class="mt-2 text-sm font-medium text-heading">{{ $next['date'] }} · {{ $next['time'] }}</p>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-3 border-t border-line px-4 py-3">
                    <a href="{{ route('patient.bookings.show', $next['id']) }}" class="btn-primary">View details</a>
                    @if ($next['can_cancel'])
                        <a href="{{ route('patient.bookings.show', $next['id']) }}#reschedule" class="btn-secondary">Reschedule</a>
                        <form method="POST" action="{{ route('patient.bookings.cancel', $next['id']) }}" data-confirm="This visit will be cancelled and the time will open again." data-confirm-title="Cancel this appointment?" data-confirm-ok="Cancel appointment" data-confirm-tone="danger">
                            @csrf
                            <button type="submit" class="text-sm font-semibold text-danger">Cancel</button>
                        </form>
                    @endif
                    <a href="{{ route('patient.book') }}" class="ml-auto text-sm font-semibold text-primary no-underline">Find a Doctor</a>
                </div>
            @else
                <div class="px-4 py-6">
                    <p class="text-sm font-semibold text-heading">You do not have an upcoming visit.</p>
                    <p class="mt-1 text-sm text-body">Find a Doctor who is approved and has open hours, then choose a saved time slot.</p>
                    <a href="{{ route('patient.book') }}" class="btn-primary mt-3">Find a Doctor</a>
                </div>
            @endif
        </section>

        <x-ui.panel title="Upcoming appointments">
            <x-slot:action>
                <a href="{{ route('patient.bookings') }}" class="text-primary no-underline">View all</a>
            </x-slot:action>
            @if ($upcoming === [])
                <p class="px-4 py-5 text-sm text-body">{{ $next ? 'No other visits are scheduled after the next one.' : 'Upcoming visits will appear here after you book.' }}</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($upcoming as $visit)
                        <li>
                            <a href="{{ route('patient.bookings.show', $visit['id']) }}" class="flex items-center gap-3 px-4 py-3 no-underline">
                                <x-ui.avatar :name="$visit['doctor']" :photo="$visit['photo'] ?? null" size="sm" />
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-medium text-heading">{{ $visit['doctor'] }}</span>
                                    <span class="block truncate text-[13px] text-muted">{{ $visit['date'] }} · {{ $visit['time'] }}</span>
                                </span>
                                <span class="{{ $statusClass[$visit['status']] ?? 'badge-info' }}">{{ $visit['status'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    </div>

    <div class="mt-4 grid items-start gap-4 lg:grid-cols-2">
        <x-ui.panel title="Recent visits">
            <x-slot:action>
                <a href="{{ route('patient.history') }}" class="text-primary no-underline">Medical history</a>
            </x-slot:action>
            @if ($previous === [])
                <p class="px-4 py-5 text-sm text-body">Completed visits will appear here. Cancelled bookings stay with your appointments, not in this list.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($previous as $visit)
                        <li class="flex items-center justify-between gap-3 px-4 py-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-heading">{{ $visit['doctor'] }}</p>
                                <p class="truncate text-[13px] text-muted">{{ $visit['date'] }}@if ($visit['visit']) · {{ $visit['visit'] }}@endif</p>
                            </div>
                            <a href="{{ route('patient.history.show', $visit['id']) }}" class="shrink-0 text-sm font-semibold text-primary no-underline">View</a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>

        <x-ui.panel title="Updates">
            <x-slot:action>
                <a href="{{ route('account.notifications') }}" class="text-primary no-underline">Inbox</a>
            </x-slot:action>
            @if ($updates === [])
                <p class="px-4 py-5 text-sm text-body">Booking and referral updates will appear here.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($updates as $update)
                        <li>
                            <a href="{{ $update['url'] }}" class="block px-4 py-3 no-underline">
                                <span class="block truncate text-sm font-medium text-heading">{{ $update['title'] }}</span>
                                @if ($update['body'] !== '')
                                    <span class="mt-0.5 block truncate text-[13px] text-body">{{ $update['body'] }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.panel>
    </div>
@endsection
