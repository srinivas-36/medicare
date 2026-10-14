@extends('layouts.dashboard')

@php
    $role = $role ?? 'patient';
    $filters = $filters ?? [
        'date' => '',
        'startingTime' => '',
        'endingTime' => '',
        'status' => '',
        'q' => '',
        'tab' => '',
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
        'tab' => $filters['tab'] ?? '',
        'page' => $appointments->currentPage() > 1 ? $appointments->currentPage() : null,
    ], fn ($value) => $value !== null && $value !== '');
    $count = $appointments->total();
    $selectedTab = ($filters['tab'] ?? '') !== ''
        ? $filters['tab']
        : (in_array($filters['status'] ?? '', ['completed', 'cancelled'], true)
            ? $filters['status']
            : (($filters['status'] ?? '') === '' ? 'all' : ''));
    $tabs = $role === 'patient'
        ? [
            'all' => 'All',
            'upcoming' => 'Upcoming',
            'completed' => 'Past',
            'cancelled' => 'Cancelled',
        ]
        : [
            'all' => 'All',
            'upcoming' => 'Upcoming',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
        ];
    $showPayment = $role === 'doctor' && $appointments->getCollection()->contains(
        fn ($appointment) => filled(\App\Support\AppointmentPage::payment($appointment->payment_status)['label'])
    );
    $emptyMessage = $filtersActive && $hasAny
        ? 'No appointments found for the selected filters.'
        : 'You do not have any appointments yet.';
    $patientEmptyMessage = $filtersActive && $hasAny
        ? 'No appointments match these filters.'
        : 'You do not have any appointments yet.';
    $exportQuery = array_filter(array_merge($query, ['export' => 'csv']), fn ($value) => $value !== null && $value !== '');
@endphp

@section('content')
    @if ($role === 'doctor')
        <header class="mb-5 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-[#181A1E] sm:text-[22px]">Appointments</h1>
                <p class="mt-1 text-sm text-[#4E545F]">Manage your patient visits and schedule.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route($listRoute, $exportQuery) }}" class="btn-secondary">
                    <x-ui.icon name="download" class="h-4 w-4" />
                    Export
                </a>
                <a href="{{ route('doctor.patients.create') }}" class="btn-primary">
                    <x-ui.icon name="plus" class="h-4 w-4" />
                    Add appointment
                </a>
            </div>
        </header>

        <div class="mb-5 -mx-1 flex gap-2 overflow-x-auto px-1" role="tablist" aria-label="Appointment status">
            @foreach ($tabs as $value => $label)
                <a
                    href="{{ route($listRoute, \App\Support\AppointmentPage::tabQuery($filters, $value === 'all' ? null : $value)) }}"
                    role="tab"
                    id="appointment-tab-{{ $value }}"
                    aria-selected="{{ $selectedTab === $value ? 'true' : 'false' }}"
                    @if ($selectedTab === $value) aria-current="page" @endif
                    @class([
                        'inline-flex h-10 shrink-0 items-center rounded-lg px-3.5 text-sm font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0067FF]',
                        'bg-[#0067FF] text-white' => $selectedTab === $value,
                        'bg-white text-[#4E545F] ring-1 ring-[#E2E8F0] hover:bg-[#F8FAFC] hover:text-[#181A1E]' => $selectedTab !== $value,
                    ])
                >{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET" action="{{ route($listRoute) }}" class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center">
            @if (in_array($selectedTab, ['upcoming', 'completed', 'cancelled'], true))
                <input type="hidden" name="tab" value="{{ $selectedTab }}">
            @endif

            <div class="flex flex-wrap items-center gap-2">
                <label for="appointment-date" class="text-sm font-medium text-[#4E545F]">Date</label>
                <input
                    id="appointment-date"
                    type="date"
                    name="date"
                    value="{{ $filters['date'] }}"
                    class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20 sm:w-44"
                >
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-[#0067FF] px-3.5 text-sm font-semibold text-white hover:bg-[#0052CC] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0067FF]">Apply</button>
                @if (($filters['date'] ?? '') !== '')
                    @php
                        $clearedDate = \App\Support\AppointmentPage::tabQuery($filters, $selectedTab === 'all' || $selectedTab === '' ? null : $selectedTab);
                        unset($clearedDate['date']);
                    @endphp
                    <a href="{{ route($listRoute, $clearedDate) }}" class="inline-flex h-10 items-center px-1 text-sm font-semibold text-[#0067FF] hover:text-[#0052CC]">Clear date</a>
                @endif
            </div>

            <label for="appointment-search" class="sr-only">Search patient</label>
            <input id="appointment-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search patient..." class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] placeholder:text-[#94A3B8] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20 lg:ml-auto lg:w-64">
        </form>

        <div class="space-y-3 md:hidden">
            @forelse ($appointments as $appointment)
                @php
                    $href = route($showRoute, array_merge(['appointment' => $appointment], $query));
                    $status = \App\Support\AppointmentPage::status($appointment->status);
                    $payment = \App\Support\AppointmentPage::payment($appointment->payment_status);
                    $patient = $appointment->patient;
                    $primary = $patient?->name ?: 'Patient';
                    $photo = \App\Support\AppointmentPage::photoUrl($patient?->photo);
                    $initials = \App\Support\AppointmentPage::initials((string) ($patient?->name ?? ''), 'P');
                @endphp
                <article class="rounded-xl border border-[#E2E8F0] bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            @if ($photo)
                                <img src="{{ $photo }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover">
                            @else
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-xs font-semibold text-[#0067FF]" aria-hidden="true">{{ $initials }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-[#181A1E]">{{ $primary }}</p>
                                @if (filled($patient?->email))
                                    <p class="truncate text-xs text-[#64748B]">{{ $patient->email }}</p>
                                @endif
                                @if (filled($appointment->reason))
                                    <p class="truncate text-xs text-[#64748B]">{{ $appointment->reason }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-[13px]">
                        <div>
                            <dt class="text-[#64748B]">Date</dt>
                            <dd class="mt-0.5 font-medium text-[#181A1E]">{{ \App\Support\AppointmentPage::date($appointment->appointment_date) }}</dd>
                        </div>
                        <div>
                            <dt class="text-[#64748B]">Time</dt>
                            <dd class="mt-0.5 font-medium text-[#181A1E]">{{ \App\Support\AppointmentPage::timeSlot($appointment) }}</dd>
                        </div>
                    </dl>
                    @if ($payment['label'])
                        <p class="mt-3">
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $payment['class'] }}">{{ $payment['label'] }}</span>
                        </p>
                    @endif
                    <a href="{{ $href }}" class="mt-3 inline-flex h-10 items-center rounded-lg px-1 text-sm font-semibold text-[#0067FF] hover:text-[#0052CC]">View appointment</a>
                </article>
            @empty
                <div class="rounded-xl border border-[#E2E8F0] bg-white px-4 py-6 text-sm text-[#4E545F]">
                    <p>{{ $emptyMessage }}</p>
                    @if ($filtersActive && $hasAny)
                        <a href="{{ route($listRoute) }}" class="mt-2 inline-flex font-semibold text-[#0067FF] hover:text-[#0052CC]">Show all appointments</a>
                    @endif
                </div>
            @endforelse
        </div>

        <section class="hidden overflow-hidden rounded-xl border border-[#E2E8F0] bg-white md:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-[#F8FAFC] text-[13px] text-[#64748B]">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Patient</th>
                            <th class="px-4 py-2.5 font-semibold">Date</th>
                            <th class="px-4 py-2.5 font-semibold">Time</th>
                            <th class="px-4 py-2.5 font-semibold">Status</th>
                            <th class="px-4 py-2.5 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E2E8F0]">
                        @forelse ($appointments as $appointment)
                            @php
                                $href = route($showRoute, array_merge(['appointment' => $appointment], $query));
                                $status = \App\Support\AppointmentPage::status($appointment->status);
                                $payment = \App\Support\AppointmentPage::payment($appointment->payment_status);
                                $patient = $appointment->patient;
                                $primary = $patient?->name ?: 'Patient';
                                $photo = \App\Support\AppointmentPage::photoUrl($patient?->photo);
                                $initials = \App\Support\AppointmentPage::initials((string) ($patient?->name ?? ''), 'P');
                            @endphp
                            <tr class="hover:bg-[#F8FAFC]">
                                <td class="px-4 py-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        @if ($photo)
                                            <img src="{{ $photo }}" alt="" class="h-8 w-8 shrink-0 rounded-full object-cover">
                                        @else
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-xs font-semibold text-[#0067FF]" aria-hidden="true">{{ $initials }}</span>
                                        @endif
                                        <div class="min-w-0">
                                            <a href="{{ $href }}" class="block truncate font-semibold text-[#181A1E] hover:text-[#0067FF]">{{ $primary }}</a>
                                            @if (filled($patient?->email))
                                                <p class="truncate text-xs text-[#0067FF]">{{ $patient->email }}</p>
                                            @endif
                                            @if (filled($appointment->reason))
                                                <p class="truncate text-xs text-[#64748B]">{{ $appointment->reason }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-[#181A1E]">{{ \App\Support\AppointmentPage::date($appointment->appointment_date) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-[#181A1E]">{{ \App\Support\AppointmentPage::timeSlot($appointment) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span>
                                    @if ($payment['label'])
                                        <span class="ml-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $payment['class'] }}">{{ $payment['label'] }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ $href }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#64748B] hover:bg-[#F8FAFC] hover:text-[#181A1E]" aria-label="View appointment">
                                        <x-ui.icon name="dots" class="h-4 w-4" />
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-sm text-[#4E545F]">
                                    <p>{{ $emptyMessage }}</p>
                                    @if ($filtersActive && $hasAny)
                                        <a href="{{ route($listRoute) }}" class="mt-2 inline-flex font-semibold text-[#0067FF] hover:text-[#0052CC]">Show all appointments</a>
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($appointments->total() > 0)
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[#E2E8F0] px-4 py-3">
                    <p class="text-[13px] text-[#64748B]">Showing {{ $appointments->firstItem() }} to {{ $appointments->lastItem() }} of {{ $appointments->total() }} results</p>
                </div>
            @endif
        </section>
    @else
        <x-ui.page-header title="Your bookings" description="Upcoming, past, and cancelled visits stay separate. A cancelled booking is not a completed consultation.">
            <a href="{{ route('patient.book') }}" class="btn-primary">Find a Doctor</a>
        </x-ui.page-header>

        <div class="mb-4 -mx-1 flex gap-2 overflow-x-auto px-1" role="tablist" aria-label="Appointment status">
            @foreach ($tabs as $value => $label)
                <a
                    href="{{ route($listRoute, \App\Support\AppointmentPage::tabQuery($filters, $value === 'all' ? null : $value)) }}"
                    @class([
                        'inline-flex h-9 shrink-0 items-center rounded-lg px-3 text-sm font-semibold',
                        'bg-[#0067FF] text-white' => $selectedTab === $value,
                        'bg-white text-[#4E545F] ring-1 ring-[#E2E8F0] hover:bg-[#F8FAFC]' => $selectedTab !== $value,
                    ])
                >{{ $label }}</a>
            @endforeach
        </div>

        <form method="GET" action="{{ route($listRoute) }}" class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            @if (in_array($selectedTab, ['upcoming', 'completed', 'cancelled'], true))
                <input type="hidden" name="tab" value="{{ $selectedTab }}">
            @endif
            <div class="flex flex-wrap items-center gap-2">
                <label for="appointment-date" class="text-sm font-medium text-[#4E545F]">Date</label>
                <input id="appointment-date" type="date" name="date" value="{{ $filters['date'] }}" class="h-10 rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E]">
                <label for="appointment-search" class="sr-only">Doctor</label>
                <input id="appointment-search" type="search" name="q" value="{{ $filters['q'] }}" placeholder="Doctor name" class="h-10 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm sm:w-52">
                <button type="submit" class="btn-primary">Apply</button>
            </div>
            <p class="text-[13px] font-medium text-[#64748B]">{{ $count }} {{ \Illuminate\Support\Str::plural('appointment', $count) }}</p>
        </form>

        <div class="space-y-3 md:hidden">
            @forelse ($appointments as $appointment)
                @php
                    $href = route($showRoute, array_merge(['appointment' => $appointment], $query));
                    $status = \App\Support\AppointmentPage::status($appointment->status);
                    $primary = $appointment->doctor?->user?->name ?: 'Doctor';
                @endphp
                <article class="rounded-xl border border-line bg-white p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-heading">{{ $primary }}</p>
                            <p class="truncate text-[13px] text-body">{{ $appointment->doctor?->specialization ?: 'Specialist' }}</p>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span>
                    </div>
                    <p class="mt-2 text-[13px] text-heading">{{ \App\Support\AppointmentPage::date($appointment->appointment_date) }} · {{ \App\Support\AppointmentPage::timeSlot($appointment) }}</p>
                    <a href="{{ $href }}" class="mt-2 inline-flex text-sm font-semibold text-primary">View</a>
                </article>
            @empty
                <p class="rounded-xl border border-line bg-white px-4 py-5 text-sm text-body">{{ $patientEmptyMessage }}</p>
            @endforelse
        </div>

        <section class="mt-4 hidden overflow-hidden rounded-xl border border-line bg-white md:block">
            <div class="overflow-x-auto">
                <table class="appointment-table w-full text-left text-sm">
                    <thead class="bg-[#f7faff] text-[#4E545F]">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Doctor</th>
                            <th class="px-6 py-3 font-semibold">Specialization</th>
                            <th class="px-6 py-3 font-semibold">Date</th>
                            <th class="px-6 py-3 font-semibold">Time</th>
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
                                $primary = $appointment->doctor?->user?->name ?: 'Doctor';
                            @endphp
                            <tr class="hover:bg-[#f7faff]">
                                <td class="relative px-6 py-4 font-semibold text-[#181A1E]">
                                    <a href="{{ $href }}" class="row-link text-[#181A1E]">{{ $primary }}</a>
                                </td>
                                <td class="px-6 py-4 text-[#181A1E]">{{ $appointment->doctor?->specialization ?: 'Not provided' }}</td>
                                <td class="px-6 py-4 text-[#181A1E]">{{ \App\Support\AppointmentPage::date($appointment->appointment_date) }}</td>
                                <td class="px-6 py-4 text-[#181A1E]">{{ \App\Support\AppointmentPage::timeSlot($appointment) }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $status['class'] }}">{{ $status['label'] }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if ($payment['label'])
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $payment['class'] }}">{{ $payment['label'] }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-[#4E545F]">{{ $patientEmptyMessage }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($appointments->lastPage() > 1)
        <nav class="mt-5 flex flex-wrap items-center gap-2" aria-label="Pagination">
            @if ($appointments->onFirstPage())
                <span class="inline-flex h-10 items-center rounded-lg px-3 text-sm font-semibold text-[#64748B]">Previous</span>
            @else
                <a href="{{ $appointments->previousPageUrl() }}" class="inline-flex h-10 items-center rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm font-semibold text-[#181A1E]">Previous</a>
            @endif

            @for ($page = 1; $page <= $appointments->lastPage(); $page++)
                <a
                    href="{{ $appointments->url($page) }}"
                    @class([
                        'inline-flex h-10 min-w-10 items-center justify-center rounded-lg px-3 text-sm font-semibold',
                        'bg-[#0067FF] text-white' => $appointments->currentPage() === $page,
                        'border border-[#E2E8F0] bg-white text-[#181A1E]' => $appointments->currentPage() !== $page,
                    ])
                >{{ $page }}</a>
            @endfor

            @if ($appointments->hasMorePages())
                <a href="{{ $appointments->nextPageUrl() }}" class="inline-flex h-10 items-center rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm font-semibold text-[#181A1E]">Next</a>
            @else
                <span class="inline-flex h-10 items-center rounded-lg px-3 text-sm font-semibold text-[#64748B]">Next</span>
            @endif
        </nav>
    @endif
@endsection
