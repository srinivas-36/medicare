@extends('layouts.public')

@php
    $rawName = trim((string) ($appointment->doctor?->user?->name ?? ''));
    if ($rawName === '') {
        $doctorName = 'your doctor';
    } elseif (preg_match('/^dr\.?\s/i', $rawName)) {
        $doctorName = $rawName;
    } else {
        $doctorName = 'Dr. '.$rawName;
    }

    $specialty = $appointment->doctor?->specialization ?: 'Specialist';
    $degrees = $appointment->doctor?->qualifications?->pluck('degree')->filter()->take(3)->implode(', ') ?? '';
    $photo = $appointment->doctor?->user?->photo;
    $when = $appointment->appointment_date?->format('l, F j, Y');
    $start = \Illuminate\Support\Carbon::parse((string) $appointment->appointment_time)->format('g:i A');
    $end = filled($appointment->ending_time)
        ? \Illuminate\Support\Carbon::parse((string) $appointment->ending_time)->format('g:i A')
        : null;
    $window = $end ? $start.' – '.$end : $start;
    $fee = $appointment->ticket_price !== null ? number_format((float) $appointment->ticket_price, 2).' USD' : null;
    $statusLabel = \App\Support\AppointmentPresenter::STATUS_LABELS[$appointment->status] ?? ucfirst((string) $appointment->status);
    $statusTone = match ($appointment->status) {
        'cancelled', 'no_show' => 'bg-red-50 text-danger',
        'pending' => 'bg-amber-50 text-warning',
        default => 'bg-[#ECFDF3] text-[#039855]',
    };
    $statusDot = match ($appointment->status) {
        'cancelled', 'no_show' => 'bg-danger',
        'pending' => 'bg-warning',
        default => 'bg-[#12B76A]',
    };
    $visitLabel = match ($appointment->visit_type) {
        'check-up' => 'Check-up',
        'follow-up' => 'Follow-up',
        'emergency' => 'Emergency',
        default => 'In-clinic Visit',
    };
    $shortcuts = [
        [
            'href' => route('patient.bookings'),
            'title' => 'My Appointments',
            'text' => 'View, reschedule or cancel your upcoming appointments.',
            'icon' => 'calendar',
            'card' => 'bg-[#EEF4FF]',
            'mark' => 'bg-white text-[#2563EB]',
        ],
        [
            'href' => route('patient.history'),
            'title' => 'Prescriptions',
            'text' => 'Access your prescriptions and medical reports.',
            'icon' => 'file',
            'card' => 'bg-[#E8F8F0]',
            'mark' => 'bg-white text-[#059669]',
        ],
        [
            'href' => route('doctors.index'),
            'title' => 'Browse Doctors',
            'text' => 'Explore more doctors and book new appointments.',
            'icon' => 'users',
            'card' => 'bg-[#F3EEFF]',
            'mark' => 'bg-white text-[#7C3AED]',
        ],
        [
            'href' => route('contact'),
            'title' => 'Need Help?',
            'text' => 'Contact our support team for any assistance.',
            'icon' => 'headset',
            'card' => 'bg-[#FFF8E8]',
            'mark' => 'bg-white text-[#D97706]',
        ],
    ];
@endphp

@section('title', 'Appointment confirmed')

@section('hide-footer', '1')

@section('content')
    <section class="relative overflow-hidden bg-[#F3F7FD] py-8 sm:py-12 lg:py-16">
        <div class="pointer-events-none absolute -right-28 top-16 h-[28rem] w-[28rem] rounded-full bg-[#E3EDF9]"></div>
        <div class="pointer-events-none absolute -left-24 bottom-10 h-72 w-72 rounded-full bg-[#EAF2FB]"></div>

        <div class="relative mx-auto w-full max-w-[1280px] px-5">
            <div class="rounded-[28px] border border-[#E7EEF6] bg-white p-5 shadow-[0_10px_40px_rgba(15,39,68,0.06)] sm:p-8">
                <div class="grid items-stretch gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(320px,0.85fr)] lg:gap-10">
                    <div class="flex flex-col items-center text-center lg:px-4 lg:py-4">
                        <div class="relative flex h-[92px] w-[92px] items-center justify-center" aria-hidden="true">
                            <span class="absolute left-0 top-5 h-1 w-3.5 -rotate-45 rounded-full bg-[#3B82F6]"></span>
                            <span class="absolute left-5 top-1 h-1 w-2.5 rotate-12 rounded-full bg-[#F59E0B]"></span>
                            <span class="absolute right-3 top-2 h-1 w-3 rotate-[35deg] rounded-full bg-[#22C55E]"></span>
                            <span class="absolute right-0 top-8 h-1 w-2.5 -rotate-12 rounded-full bg-[#8B5CF6]"></span>
                            <span class="absolute bottom-4 left-1 h-1 w-2.5 rotate-45 rounded-full bg-[#F97316]"></span>
                            <span class="absolute bottom-1 left-8 h-1 w-3 -rotate-6 rounded-full bg-[#06B6D4]"></span>
                            <span class="absolute bottom-5 right-1 h-1 w-2.5 rotate-[28deg] rounded-full bg-[#EC4899]"></span>
                            <span class="absolute right-7 top-0 h-1.5 w-1.5 rounded-full bg-[#FBBF24]"></span>
                            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-[#12B981] text-white shadow-[0_10px_24px_rgba(18,185,129,0.28)]">
                                <svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true">
                                    <path d="m6.5 12.5 3.5 3.5 7.5-8" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </div>

                        <h1 class="mt-5 text-[1.75rem] font-extrabold tracking-tight text-[#0F2744] sm:text-[2rem]">Appointment Confirmed!</h1>
                        <p class="mt-2 max-w-sm text-sm leading-6 text-[#64748B]">
                            Your appointment with {{ $doctorName }} has been successfully booked.
                        </p>

                        <div class="mt-6 flex w-full max-w-lg items-start gap-3 rounded-2xl bg-[#E8F8EF] px-4 py-3.5 text-left">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#D1FAE5] text-[#059669]">
                                <x-ui.icon name="mail" class="h-5 w-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-[#0F2744]">Confirmation sent</p>
                                <p class="mt-0.5 text-[13px] leading-5 text-[#64748B]">A confirmation email and SMS has been sent to your registered contact.</p>
                            </div>
                        </div>

                        <div class="mt-5 flex w-full max-w-lg flex-col gap-3 sm:flex-row">
                            <a href="{{ route('patient.bookings') }}" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-[#0067FF] px-4 text-sm font-semibold text-white no-underline shadow-sm transition hover:bg-[#0052CC]">
                                <x-ui.icon name="calendar" class="h-4 w-4" />
                                View My Appointments
                            </a>
                            <a href="{{ route('dashboard') }}" class="inline-flex h-12 flex-1 items-center justify-center gap-2 rounded-xl border border-[#0067FF] bg-white px-4 text-sm font-semibold text-[#0067FF] no-underline transition hover:bg-[#F4F8FF]">
                                <x-ui.icon name="home" class="h-4 w-4" />
                                Go to Dashboard
                            </a>
                        </div>
                    </div>

                    <aside class="relative overflow-hidden rounded-2xl border border-[#E6EDF5] bg-white p-5 sm:p-6">
                        <div class="pointer-events-none absolute right-4 top-4 grid grid-cols-3 gap-1.5" aria-hidden="true">
                            @for ($dot = 0; $dot < 9; $dot++)
                                <span class="h-1 w-1 rounded-full bg-[#D5DEE8]"></span>
                            @endfor
                        </div>

                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-base font-bold text-[#0F2744]">Appointment Details</h2>
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusTone }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $statusDot }}"></span>
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <div class="mt-5 flex items-center gap-3">
                            <x-ui.avatar :name="$doctorName" :photo="$photo" size="xl" />
                            <div class="min-w-0">
                                <p class="truncate text-[15px] font-bold text-[#0F2744]">{{ $doctorName }}</p>
                                <p class="truncate text-sm text-[#64748B]">{{ $specialty }}</p>
                                @if ($degrees !== '')
                                    <p class="truncate text-[13px] text-[#94A3B8]">{{ $degrees }}</p>
                                @endif
                            </div>
                        </div>

                        <dl class="mt-5">
                            <div class="flex items-center justify-between gap-4 border-b border-[#EEF2F6] py-3.5">
                                <dt class="inline-flex items-center gap-2.5 text-sm text-[#64748B]">
                                    <x-ui.icon name="calendar" class="h-4 w-4 text-[#94A3B8]" />
                                    Date
                                </dt>
                                <dd class="text-right text-sm font-semibold text-[#0F2744]">{{ $when }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-[#EEF2F6] py-3.5">
                                <dt class="inline-flex items-center gap-2.5 text-sm text-[#64748B]">
                                    <x-ui.icon name="clock" class="h-4 w-4 text-[#94A3B8]" />
                                    Time
                                </dt>
                                <dd class="text-right text-sm font-semibold text-[#0F2744]">{{ $window }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-4 border-b border-[#EEF2F6] py-3.5">
                                <dt class="inline-flex items-center gap-2.5 text-sm text-[#64748B]">
                                    <x-ui.icon name="pin" class="h-4 w-4 text-[#94A3B8]" />
                                    Consultation type
                                </dt>
                                <dd class="text-right text-sm font-semibold text-[#0F2744]">{{ $visitLabel }}</dd>
                            </div>
                            @if ($fee !== null)
                                <div class="flex items-center justify-between gap-4 py-3.5">
                                    <dt class="inline-flex items-center gap-2.5 text-sm text-[#64748B]">
                                        <x-ui.icon name="card" class="h-4 w-4 text-[#94A3B8]" />
                                        Consultation fee
                                    </dt>
                                    <dd class="text-right text-sm font-semibold text-[#0F2744]">{{ $fee }}</dd>
                                </div>
                            @endif
                        </dl>
                    </aside>
                </div>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($shortcuts as $shortcut)
                    <a href="{{ $shortcut['href'] }}" class="flex items-center gap-3 rounded-2xl px-4 py-4 no-underline transition hover:shadow-lift {{ $shortcut['card'] }}">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $shortcut['mark'] }}">
                            <x-ui.icon :name="$shortcut['icon']" class="h-5 w-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-bold text-[#0F2744]">{{ $shortcut['title'] }}</span>
                            <span class="mt-0.5 block text-[13px] leading-5 text-[#64748B]">{{ $shortcut['text'] }}</span>
                        </span>
                        <x-ui.icon name="chevron-right" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                    </a>
                @endforeach
            </div>

            <div class="mt-5 flex items-start gap-3 rounded-2xl border border-[#D7E6F8] bg-[#F4F8FF] px-5 py-4">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-[#2563EB]">
                    <x-ui.icon name="info" class="h-4 w-4" />
                </span>
                <div>
                    <p class="text-sm font-bold text-[#0F2744]">Important Information</p>
                    <p class="mt-1 text-[13px] leading-5 text-[#64748B]">Please arrive 10 minutes before your appointment time. If you need to reschedule or cancel, you can do so from your appointments page.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
