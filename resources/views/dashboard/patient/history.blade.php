@extends('layouts.dashboard')

@section('title', 'Medical history')

@section('content')
    @php
        $filtered = collect($filters)->filter(fn ($value) => $value !== '' && $value !== 0 && $value !== '0')->isNotEmpty();
        $visitLabels = [
            'consultation' => 'Consultation',
            'check-up' => 'Check-up',
            'follow-up' => 'Follow-up',
            'emergency' => 'Emergency',
        ];
        $statusMeta = [
            'completed' => ['label' => 'Completed', 'class' => 'bg-[#E7F8EE] text-[#16A34A]', 'icon' => 'check'],
            'confirmed' => ['label' => 'Confirmed', 'class' => 'bg-[#E8F2FF] text-[#2563EB]', 'icon' => 'calendar'],
            'pending' => ['label' => 'Pending', 'class' => 'bg-[#FFFBEB] text-[#B45309]', 'icon' => 'clock'],
            'checked_in' => ['label' => 'Checked in', 'class' => 'bg-[#E8F2FF] text-[#2563EB]', 'icon' => 'check'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-[#FEF2F2] text-[#DC2626]', 'icon' => 'close'],
            'no_show' => ['label' => 'No-show', 'class' => 'bg-[#FEF2F2] text-[#DC2626]', 'icon' => 'close'],
        ];
        $dateField = function (string $name, string $label, string $value) {
            return compact('name', 'label', 'value');
        };
    @endphp

    <header class="mb-5">
        <h1 class="text-[28px] font-bold leading-tight tracking-tight text-[#181A1E]">Medical history</h1>
        <p class="mt-1 text-sm text-[#64748B]">Review past visits, symptoms, and reports in one place.</p>
    </header>

    <form method="GET" action="{{ route('patient.history') }}" class="mb-4 rounded-2xl border border-[#E6EDF5] bg-white p-4 shadow-card sm:p-5">
        <div class="flex flex-wrap items-end gap-3">
            @foreach ([$dateField('from', 'From', $filters['from']), $dateField('to', 'To', $filters['to'])] as $date)
                <label class="min-w-[150px] flex-1 text-[13px] font-medium text-[#64748B]">
                    {{ $date['label'] }}
                    <div class="relative mt-1.5" x-data="{ filled: @js($date['value'] !== '') }">
                        <span class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-[#94A3B8]">
                            <x-ui.icon name="calendar" class="h-4 w-4" />
                        </span>
                        <input
                            type="date"
                            name="{{ $date['name'] }}"
                            value="{{ $date['value'] }}"
                            @input="filled = $event.target.value !== ''; $el.classList.toggle('text-transparent', ! filled); $el.classList.toggle('text-[#181A1E]', filled)"
                            @class([
                                'history-date field relative h-11 w-full cursor-pointer bg-white pl-10 pr-3',
                                'text-transparent' => $date['value'] === '',
                                'text-[#181A1E]' => $date['value'] !== '',
                            ])>
                        <span
                            x-show="!filled"
                            @class([
                                'pointer-events-none absolute left-10 top-1/2 -translate-y-1/2 text-sm text-[#94A3B8]',
                                'hidden' => $date['value'] !== '',
                            ])
                        >dd-mm-yyyy</span>
                    </div>
                </label>
            @endforeach

            <label class="min-w-[150px] flex-1 text-[13px] font-medium text-[#64748B]">
                Doctor
                <span class="relative mt-1.5 block">
                    <select name="doctor" class="field h-11 w-full appearance-none bg-white bg-none pr-9 text-[#181A1E]">
                        <option value="">All doctors</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected((int) $filters['doctor'] === (int) $doctor->id)>{{ $doctor->user?->name }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                        <x-ui.icon name="chevron" class="h-4 w-4" />
                    </span>
                </span>
            </label>

            <label class="min-w-[150px] flex-1 text-[13px] font-medium text-[#64748B]">
                Visit type
                <span class="relative mt-1.5 block">
                    <select name="visit" class="field h-11 w-full appearance-none bg-white bg-none pr-9 text-[#181A1E]">
                        <option value="">All types</option>
                        @foreach ($visitLabels as $value => $label)
                            <option value="{{ $value }}" @selected($filters['visit'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                        <x-ui.icon name="chevron" class="h-4 w-4" />
                    </span>
                </span>
            </label>

            <label class="min-w-[150px] flex-1 text-[13px] font-medium text-[#64748B]">
                Status
                <span class="relative mt-1.5 block">
                    <select name="scope" class="field h-11 w-full appearance-none bg-white bg-none pr-9 text-[#181A1E]">
                        <option value="">All records</option>
                        <option value="consultations" @selected($filters['scope'] === 'consultations')>Completed</option>
                        <option value="upcoming" @selected($filters['scope'] === 'upcoming')>Upcoming</option>
                        <option value="cancelled" @selected($filters['scope'] === 'cancelled')>Cancelled</option>
                    </select>
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                        <x-ui.icon name="chevron" class="h-4 w-4" />
                    </span>
                </span>
            </label>

            <div class="flex shrink-0 items-center gap-2">
                <a href="{{ route('patient.history') }}" class="inline-flex h-11 items-center gap-1.5 whitespace-nowrap px-1 text-sm font-semibold text-[#0067FF] no-underline hover:text-[#0052CC]">
                    <x-ui.icon name="refresh" class="h-4 w-4" />
                    Clear filters
                </a>
                <button type="submit" class="inline-flex h-11 items-center gap-2 whitespace-nowrap rounded-lg bg-[#0067FF] px-4 text-sm font-semibold text-white shadow-none hover:bg-[#0052CC]">
                    <x-ui.icon name="filter" class="h-4 w-4" />
                    Apply filters
                </button>
            </div>
        </div>
    </form>

    <ol class="space-y-3">
        @forelse ($visits as $visit)
            @php
                $rawName = trim((string) ($visit->doctor?->user?->name ?? ''));
                if ($rawName === '') {
                    $doctorName = 'Doctor';
                } elseif (preg_match('/^dr\.?\s/i', $rawName)) {
                    $doctorName = preg_replace('/^dr\.?\s*/i', 'Dr ', $rawName);
                } else {
                    $doctorName = 'Dr '.$rawName;
                }

                $when = $visit->appointment_date
                    ? \Illuminate\Support\Carbon::parse($visit->appointment_date->toDateString().' '.($visit->appointment_time ?: '00:00:00'))
                    : null;

                $duration = null;
                $symptomLines = [];
                foreach (preg_split('/\r\n|\r|\n/', (string) $visit->reason) ?: [] as $line) {
                    $line = trim($line);
                    if ($line === '') {
                        continue;
                    }
                    if (preg_match('/^duration:\s*(.+)$/i', $line, $match)) {
                        $duration = trim($match[1]);
                        continue;
                    }
                    if (preg_match('/^medicines:\s/i', $line)) {
                        continue;
                    }
                    $symptomLines[] = $line;
                }
                $symptoms = $symptomLines !== [] ? implode(', ', $symptomLines) : 'Not recorded';
                $diagnosis = trim((string) ($visit->consultation?->diagnosis ?? ''));
                if ($diagnosis !== '' && ! str_contains(mb_strtolower($symptoms), mb_strtolower($diagnosis))) {
                    $symptoms = $symptoms === 'Not recorded' ? $diagnosis : $symptoms.' · '.$diagnosis;
                }

                $status = $statusMeta[$visit->status] ?? [
                    'label' => ucfirst(str_replace('_', ' ', (string) $visit->status)),
                    'class' => 'bg-[#F1F5F9] text-[#475569]',
                    'icon' => 'clipboard',
                ];
                $specialty = $visit->doctor?->specialization ?: 'Specialist';
                $visitLabel = $visitLabels[$visit->visit_type] ?? ucfirst((string) str_replace('-', ' ', (string) $visit->visit_type));
                $reportCount = $visit->reports->count();
                $reportNames = $visit->reports
                    ->map(function ($report) {
                        $group = trim((string) $report->group_name);
                        if ($group !== '' && ! in_array(strtolower($group), ['other', 'other reports'], true)) {
                            return $group;
                        }
                        $stem = pathinfo((string) $report->original_name, PATHINFO_FILENAME);

                        return $stem !== '' ? $stem : 'Report';
                    })
                    ->unique()
                    ->implode(', ');
            @endphp
            <li class="rounded-2xl border border-[#E6EDF5] bg-white px-4 py-4 shadow-card sm:px-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                    <div class="flex min-w-0 flex-1 items-start gap-4">
                        <div class="w-[108px] shrink-0">
                            <div class="flex h-16 w-16 flex-col items-center justify-center rounded-xl bg-[#E8F2FF] text-center text-[#0067FF]">
                                <span class="text-[13px] font-bold leading-4">{{ $when?->format('M j') }}</span>
                                <span class="text-[13px] font-bold leading-4">{{ $when?->format('Y') }}</span>
                            </div>
                            @if ($when)
                                <p class="mt-2 whitespace-nowrap text-xs text-[#64748B]">{{ $when->format('D, h:i A') }}</p>
                            @endif
                        </div>

                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <x-ui.avatar :name="$rawName !== '' ? $rawName : 'Doctor'" :photo="$visit->doctor?->user?->photo" size="lg" class="mt-0.5" />
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-[15px] font-bold text-[#181A1E]">{{ $doctorName }}</h2>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $status['class'] }}">
                                        <x-ui.icon :name="$status['icon']" class="h-3.5 w-3.5" />
                                        {{ $status['label'] }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-sm text-[#64748B]">
                                    {{ $specialty }}<span class="px-1.5 text-[#CBD5E1]">·</span>{{ $visitLabel }}
                                </p>
                                <div class="mt-2.5 flex flex-wrap items-center gap-x-6 gap-y-1.5 text-sm text-[#334155]">
                                    <span class="inline-flex min-w-0 items-center gap-2">
                                        <x-ui.icon name="file" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                                        <span class="min-w-0">Symptoms: {{ $symptoms }}</span>
                                    </span>
                                    @if ($duration)
                                        <span class="inline-flex items-center gap-2">
                                            <x-ui.icon name="clock" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                                            <span>Duration: {{ $duration }}</span>
                                        </span>
                                    @endif
                                </div>
                                @if ($reportCount > 0)
                                    <p class="mt-1.5 flex min-w-0 items-center gap-2 text-sm text-[#64748B]">
                                        <x-ui.icon name="paperclip" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                                        <span class="min-w-0 truncate">{{ $reportCount === 1 ? '1 report attached' : $reportCount.' reports attached' }} · {{ $reportNames }}</span>
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('patient.history.show', $visit) }}" class="inline-flex shrink-0 items-center gap-1 self-start text-sm font-semibold text-[#0067FF] no-underline hover:text-[#0052CC] lg:self-center">
                        View visit details
                        <x-ui.icon name="chevron-right" class="h-4 w-4" />
                    </a>
                </div>
            </li>
        @empty
            <li class="rounded-2xl border border-[#E6EDF5] bg-white px-5 py-8 shadow-card">
                @if ($filtered)
                    <p class="text-sm font-semibold text-[#181A1E]">No visits match these filters</p>
                    <a href="{{ route('patient.history') }}" class="mt-2 inline-flex text-sm font-semibold text-[#0067FF] no-underline">Clear filters</a>
                @else
                    <p class="text-sm font-semibold text-[#181A1E]">No previous visits</p>
                    <p class="mt-1 text-sm leading-6 text-[#64748B]">When a doctor completes a visit, the consultation, symptoms, and reports appear here.</p>
                    <a href="{{ route('patient.book') }}" class="btn-primary mt-3">Find a Doctor</a>
                @endif
            </li>
        @endforelse
    </ol>

    @if ($visits->hasPages())
        <div class="mt-4">{{ $visits->links() }}</div>
    @endif
@endsection
