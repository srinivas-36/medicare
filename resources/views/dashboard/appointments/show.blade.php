@extends('layouts.dashboard')

@php
    $role = $role ?? 'patient';
    $booking = \App\Support\AppointmentPage::booking($appointment, $role);
    $person = $booking['person'];
    $analysis = $booking['analysis'];

    $healthLabel = function ($record, $yesNo = false) {
        $known = filter_var(data_get($record, 'known', true), FILTER_VALIDATE_BOOLEAN);
        $value = data_get($record, 'value', '');

        if (! $known) {
            return 'Not known';
        }

        if ($value === null || $value === '') {
            return null;
        }

        return $yesNo ? ucfirst((string) $value) : $value;
    };
@endphp

@section('content')
    <div class="dash-banner">
        <a href="{{ $backUrl }}" class="text-sm font-semibold text-white/80 hover:text-white">{{ $backLabel }}</a>
        <p class="mt-4 text-sm font-semibold uppercase tracking-wide text-white/80">Appointment</p>
        <h1 class="mt-2 text-3xl font-extrabold">{{ $booking['title'] }}</h1>
        <div class="mt-3 flex flex-wrap items-center gap-3 text-sm text-white/90">
            <span>{{ $booking['date'] }}</span>
            <span>{{ $booking['slot'] }}</span>
            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $booking['status']['class'] }}">{{ $booking['status']['label'] }}</span>
        </div>
    </div>

    <section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
        <h2 class="text-xl font-bold text-[#181A1E]">Appointment</h2>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @include('dashboard.partials.detail', ['label' => 'Doctor', 'value' => $booking['doctor_name']])
            @include('dashboard.partials.detail', ['label' => 'Specialization', 'value' => $booking['specialization']])
            @include('dashboard.partials.detail', ['label' => 'Patient', 'value' => $booking['patient_name']])
            @include('dashboard.partials.detail', ['label' => 'Date', 'value' => $booking['date']])
            @include('dashboard.partials.detail', ['label' => 'Day', 'value' => $booking['day']])
            @include('dashboard.partials.detail', ['label' => 'Time slot', 'value' => $booking['slot']])
            @include('dashboard.partials.detail', ['label' => 'Status', 'value' => $booking['status']['label']])
            @include('dashboard.partials.detail', ['label' => 'Ticket price', 'value' => $booking['ticket_price']])
            @include('dashboard.partials.detail', ['label' => 'Payment', 'value' => $booking['payment']['label']])
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
        <h2 class="text-xl font-bold text-[#181A1E]">{{ $role === 'doctor' ? 'Patient' : 'Doctor' }}</h2>

        <div class="mt-6 flex flex-wrap items-center gap-4">
            @if ($person['photo'])
                <img src="{{ $person['photo'] }}" alt="{{ $person['name'] }}" class="h-20 w-20 rounded-full border-2 border-[#1769FF] object-cover">
            @else
                <span class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-[#1769FF] bg-[#EAF3FF] text-xl font-bold text-[#1769FF]">
                    {{ $person['initials'] }}
                </span>
            @endif
            <div>
                <p class="text-lg font-bold text-[#0B1F3A]">{{ $person['name'] ?: 'Not provided' }}</p>
                @if ($role === 'doctor')
                    <p class="text-sm text-[#53647D]">{{ filled($person['blood_type']) ? 'Blood type '.$person['blood_type'] : 'Blood type not set' }}</p>
                @else
                    <p class="text-sm text-[#53647D]">{{ $person['specialization'] ?: 'Specialist' }}</p>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @include('dashboard.partials.detail', ['label' => 'Name', 'value' => $person['name']])
            @include('dashboard.partials.detail', ['label' => 'Email', 'value' => $person['email']])
            @include('dashboard.partials.detail', ['label' => 'Phone', 'value' => $person['phone']])
            @if ($role === 'doctor')
                @include('dashboard.partials.detail', ['label' => 'Age', 'value' => $person['age']])
                @include('dashboard.partials.detail', ['label' => 'Gender', 'value' => $person['gender']])
                @include('dashboard.partials.detail', ['label' => 'Blood type', 'value' => $person['blood_type']])
            @else
                @include('dashboard.partials.detail', ['label' => 'Specialization', 'value' => $person['specialization']])
            @endif
        </div>
    </section>

    @if ($role === 'doctor')
        <section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <h2 class="text-xl font-bold text-[#181A1E]">Health notes</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-3">
                @include('dashboard.partials.detail', ['label' => 'Blood pressure', 'value' => $healthLabel($booking['health']['bp'] ?? [])])
                @include('dashboard.partials.detail', ['label' => 'Diabetic', 'value' => $healthLabel($booking['health']['diabetic'] ?? [], true)])
                @include('dashboard.partials.detail', ['label' => 'Hyperthyroidism', 'value' => $healthLabel($booking['health']['hyperthyroidism'] ?? [], true)])
            </div>

            <div class="mt-6">
                <p class="text-sm text-[#4E545F]">Current health issues</p>
                <p class="mt-1 whitespace-pre-line text-sm font-normal leading-7 text-[#181A1E]">{{ $booking['current_health_issues'] ?: 'Not provided' }}</p>
            </div>
        </section>
    @endif

    <section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
        <h2 class="text-xl font-bold text-[#181A1E]">Reports</h2>
        @if (count($booking['reports']) === 0)
            <p class="mt-4 text-sm leading-6 text-[#4E545F]">No reports were attached to this booking.</p>
        @else
            <div class="mt-6 space-y-6">
                @foreach ($booking['reports'] as $group)
                    <div>
                        <p class="text-sm text-[#53647D]">{{ $group['name'] }}</p>
                        <ul class="mt-2 space-y-2">
                            @foreach ($group['files'] as $file)
                                <li>
                                    <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="text-sm font-semibold text-[#1769FF] hover:underline">{{ $file['name'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
        <h2 class="text-xl font-bold text-[#181A1E]">Doctor notes</h2>
        @if (count($booking['notes']) === 0)
            <p class="mt-4 text-sm leading-6 text-[#4E545F]">No notes from the doctor yet.</p>
        @else
            <div class="mt-6 space-y-6">
                @foreach ($booking['notes'] as $note)
                    <div>
                        <p class="whitespace-pre-line text-sm font-normal leading-7 text-[#181A1E]">{{ $note['text'] }}</p>
                        @if ($note['date'] !== '')
                            <p class="mt-1 text-sm text-[#4E545F]">{{ $note['date'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
        <h2 class="text-xl font-bold text-[#181A1E]">AI analysis</h2>

        @if ($analysis === null)
            <p class="mt-4 text-sm leading-6 text-[#4E545F]">AI analysis is not available for this booking yet.</p>
        @else
            <h3 class="mt-6 text-lg font-bold text-[#181A1E]">Patient overview</h3>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                @include('dashboard.partials.detail', ['label' => 'Name', 'value' => $analysis['patientInfo']['name']])
                @include('dashboard.partials.detail', ['label' => 'Age', 'value' => $analysis['patientInfo']['age']])
                @include('dashboard.partials.detail', ['label' => 'Gender', 'value' => $analysis['patientInfo']['gender']])
                @include('dashboard.partials.detail', ['label' => 'Blood pressure', 'value' => $analysis['patientInfo']['bloodPressure']])
                @include('dashboard.partials.detail', ['label' => 'Diabetic', 'value' => $analysis['patientInfo']['diabetic']])
                @include('dashboard.partials.detail', ['label' => 'Hyperthyroidism', 'value' => $analysis['patientInfo']['hyperthyroidism']])
            </div>

            <h3 class="mt-8 text-lg font-bold text-[#181A1E]">Overall summary</h3>
            <p class="mt-4 text-sm text-[#4E545F]">Summary</p>
            <p class="mt-1 whitespace-pre-line text-sm font-normal leading-7 text-[#181A1E]">{{ $analysis['overallSummary'] ?: 'Not provided' }}</p>

            <h3 class="mt-8 text-lg font-bold text-[#181A1E]">Report analysis</h3>
            @if (count($analysis['reportAnalyses']) === 0)
                <p class="mt-4 text-sm leading-7 text-[#181A1E]">Not provided</p>
            @else
                <div class="mt-6 space-y-8">
                    @foreach ($analysis['reportAnalyses'] as $report)
                        <div>
                            @include('dashboard.partials.detail', ['label' => 'Group', 'value' => $report['groupName']])

                            @if (count($report['reportUrls']) > 0)
                                <ul class="mt-4 space-y-2">
                                    @foreach ($report['reportUrls'] as $url)
                                        <li>
                                            <a href="{{ $url }}" target="_blank" rel="noopener" class="break-all text-sm font-semibold text-[#1769FF] hover:underline">{{ $url }}</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif

                            @if (count($report['warnings']) > 0)
                                <div class="mt-4">
                                    <p class="text-sm text-[#4E545F]">Warnings</p>
                                    @foreach ($report['warnings'] as $warning)
                                        <p class="mt-1 text-sm font-normal leading-7 text-[#181A1E]">{{ $warning }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <p class="mt-4 text-sm text-[#4E545F]">Analysis</p>
                            <p class="mt-1 whitespace-pre-line text-sm font-normal leading-7 text-[#181A1E]">{{ $report['analysis'] ?: 'Not provided' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <h3 class="mt-8 text-lg font-bold text-[#181A1E]">Recommendations</h3>
            @if (count($analysis['doctorRecommendations']) === 0)
                <p class="mt-4 text-sm leading-7 text-[#181A1E]">Not provided</p>
            @else
                <ol class="mt-4 list-decimal space-y-2 pl-5 text-sm font-normal leading-7 text-[#181A1E]">
                    @foreach ($analysis['doctorRecommendations'] as $recommendation)
                        <li>{{ $recommendation }}</li>
                    @endforeach
                </ol>
            @endif
        @endif
    </section>
@endsection
