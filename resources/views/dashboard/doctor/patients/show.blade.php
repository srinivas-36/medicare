@extends('layouts.dashboard')

@php
    $name = $patient->name ?? 'Patient';
    $visits = $visits ?? [];
    $profile = $profile ?? null;
    $chartId = $chartId ?? null;
    $chartUrl = $chartId ? route('doctor.appointments.show', $chartId) : null;
    $analysisUrl = route('doctor.patients.analyses', $patient);
    $canRefer = ($referralAppointment ?? null) !== null;

    $readNote = function (string $key) use ($profile) {
        $record = data_get($profile?->health_notes, $key, []);
        $known = filter_var(data_get($record, 'known', true), FILTER_VALIDATE_BOOLEAN);
        $value = data_get($record, 'value');

        if (! $known) {
            return ['raw' => '', 'label' => 'Not known'];
        }

        if ($value === null || $value === '') {
            return ['raw' => '', 'label' => '—'];
        }

        return ['raw' => (string) $value, 'label' => (string) $value];
    };

    $bp = $readNote('bp');
    $diabetic = $readNote('diabetic');
    $thyroid = $readNote('hyperthyroidism');
    $bpStatus = null;
    $bpLabel = $bp['label'];

    if ($bp['raw'] !== '' && preg_match('/(\d{2,3})\s*\/\s*(\d{2,3})/', $bp['raw'], $bpMatch)) {
        $systolic = (int) $bpMatch[1];
        $diastolic = (int) $bpMatch[2];
        $bpLabel = $bpMatch[1].'/'.$bpMatch[2].' mmHg';
        $bpStatus = ($systolic >= 140 || $diastolic >= 90)
            ? ['label' => 'High', 'class' => 'bg-red-50 text-[#B91C1C]']
            : (($systolic > 120 || $diastolic > 80)
                ? ['label' => 'Elevated', 'class' => 'bg-[#FFFBEB] text-[#B45309]']
                : ['label' => 'Normal', 'class' => 'bg-[#ECFDF3] text-[#15803D]']);
    }

    $yesNoClass = function (string $raw): string {
        return match ($raw) {
            'yes' => 'text-[#15803D]',
            'no' => 'text-[#DC2626]',
            default => 'text-[#181A1E]',
        };
    };

    $yesNoLabel = function (array $note): string {
        return $note['raw'] === '' ? $note['label'] : ucfirst($note['raw']);
    };

    $allergyRaw = data_get($profile?->health_notes, 'allergies');
    $allergy = is_array($allergyRaw) ? ($allergyRaw['value'] ?? null) : $allergyRaw;
    $allergyLabel = filled($allergy) ? (string) $allergy : null;

    $genderLabel = match ($patient->gender) {
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Other',
        default => filled($patient->gender) ? ucfirst((string) $patient->gender) : null,
    };
    $ageLabel = filled($profile?->age) ? $profile->age.' years' : null;
    $bloodLabel = filled($profile?->blood_type) ? $profile->blood_type : '—';

    $chips = [];
    if (($bpStatus['label'] ?? null) === 'High') {
        $chips[] = ['label' => 'Hypertension', 'icon' => 'activity', 'class' => 'bg-[#ECFDF3] text-[#15803D]'];
    }
    if ($diabetic['raw'] === 'yes') {
        $chips[] = ['label' => 'Diabetic', 'icon' => 'droplet', 'class' => 'bg-[#ECFDF3] text-[#15803D]'];
    }
    if ($thyroid['raw'] === 'yes') {
        $chips[] = ['label' => 'Hyperthyroidism', 'icon' => 'alert', 'class' => 'bg-[#F3E8FF] text-[#7C3AED]'];
    }

    $visits = collect($visits)->map(function (array $visit) {
        $parts = preg_split('/\r\n|\r|\n/', (string) ($visit['condition'] ?? '')) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($line) => $line !== '' && $line !== 'Not specified'));
        $complaint = $parts[0] ?? null;

        if (is_string($complaint) && ! str_contains($complaint, ':')) {
            $complaint = ucfirst(mb_strtolower($complaint));
        }

        $lines = array_values(array_filter([
            filled($visit['diagnosis'] ?? null) ? $visit['diagnosis'] : null,
            filled($visit['plan'] ?? null) ? $visit['plan'] : null,
        ]));

        if ($lines === [] && filled($visit['clinical_notes'] ?? null)) {
            $lines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $visit['clinical_notes']) ?: [])));
            $lines = array_slice($lines, 0, 2);
        }

        $visit['complaint'] = $complaint;
        $visit['extras'] = array_slice($parts, 1);
        $visit['note_lines'] = $lines;

        return $visit;
    })->all();

    $actionVisits = collect($visits)
        ->filter(fn ($visit) => ! empty($visit['can_act']))
        ->map(fn ($visit) => [
            'id' => $visit['id'],
            'label' => trim($visit['date'].' · '.$visit['time'].(filled($visit['complaint'] ?? null) ? ' · '.$visit['complaint'] : '')),
            'consult_url' => $visit['consult_url'],
            'refer_url' => $visit['refer_url'],
            'preview_url' => $visit['preview_url'],
            'clinical_notes' => (string) ($visit['clinical_notes'] ?? ''),
            'diagnosis' => (string) ($visit['diagnosis'] ?? ''),
            'plan' => (string) ($visit['plan'] ?? ''),
            'follow_up_instructions' => (string) ($visit['follow_up_instructions'] ?? ''),
            'follow_up_on' => (string) ($visit['follow_up_on'] ?? ''),
            'reports' => $visit['report_files'] ?? [],
            'draft' => (string) ($visit['draft'] ?? ''),
        ])
        ->values()
        ->all();

    $actionDefaultId = $actionDefaultId ?? ($actionVisits[0]['id'] ?? null);

    $chartAction = $errors->hasAny(['follow_up_on', 'follow_up_instructions'])
        ? 'follow'
        : ($errors->hasAny(['clinical_notes', 'diagnosis', 'plan']) ? 'note' : null);

    $consultations = array_values(array_filter($visits, fn ($visit) => ($visit['note_lines'] ?? []) !== []));
    $withReports = array_values(array_filter($visits, fn ($visit) => ($visit['reports'] ?? 0) > 0));
    $referrals = collect($visits)->flatMap(fn ($visit) => collect($visit['referrals'] ?? [])->map(fn ($referral) => $referral + ['date' => $visit['date']]))->all();
    $lastVisit = $visits[0]['date'] ?? null;

    $complaints = collect($visits)->map(function ($visit) {
        $line = mb_strtolower(trim(strtok((string) ($visit['condition'] ?? ''), "\n")));

        return ($line === '' || $line === 'not specified' || ! preg_match('/[aeiou]/i', $line)) ? null : $line;
    })->filter()->values();

    $grouped = [];
    foreach ($complaints as $complaint) {
        $key = str_contains($complaint, 'fever') ? 'fever' : (str_contains($complaint, 'headache') ? 'headache' : $complaint);
        $grouped[$key]['count'] = ($grouped[$key]['count'] ?? 0) + 1;
        $grouped[$key]['sample'] = $grouped[$key]['sample'] ?? $complaint;
        $grouped[$key]['samples'][] = $complaint;
    }

    $keyNotes = [];
    foreach ($grouped as $key => $group) {
        if ($key === 'fever') {
            $high = collect($group['samples'])->contains(fn ($sample) => str_contains($sample, 'high fever'));
            $label = $high ? 'high fever' : 'fever';
            $keyNotes[] = $group['count'] > 1 ? 'History of '.$label.' episodes' : 'Reports '.$label;
            continue;
        }

        if ($key === 'headache') {
            $simple = collect($group['samples'])->every(fn ($sample) => in_array($sample, ['headache', 'headaches', 'occasional headaches'], true));
            $keyNotes[] = $simple ? 'Reports occasional headaches' : 'Reports '.$group['sample'];
            continue;
        }

        if (in_array($key, ['health', 'checkup', 'check-up', 'check up'], true)) {
            continue;
        }

        $keyNotes[] = ($group['count'] > 1 ? 'History of ' : 'Reports ').$group['sample'];
    }

    if ($thyroid['raw'] === 'yes') {
        $keyNotes[] = 'Thyroid condition present';
    }

    if ($diabetic['raw'] === 'yes') {
        $keyNotes[] = 'Diabetic — requires monitoring';
    }

    $keyNotes = array_slice(array_values(array_unique($keyNotes)), 0, 4);

    $statusClass = [
        'Confirmed' => 'bg-[#ECFDF3] text-[#15803D]',
        'Pending' => 'bg-[#FFFBEB] text-[#B45309]',
        'Checked in' => 'bg-[#E8F2FF] text-[#1D4ED8]',
        'Completed' => 'bg-[#E8F2FF] text-[#1D4ED8]',
        'Cancelled' => 'bg-red-50 text-[#B91C1C]',
        'No-show' => 'bg-red-50 text-[#B91C1C]',
    ];

    $summaryBadge = function (array $note): array {
        return match ($note['raw']) {
            'yes' => ['label' => 'Requires monitoring', 'class' => 'bg-red-50 text-[#B91C1C]'],
            'no' => ['label' => 'Normal', 'class' => 'bg-[#ECFDF3] text-[#15803D]'],
            default => ['label' => $note['label'], 'class' => 'bg-[#F1F5F9] text-[#64748B]'],
        };
    };

    $diabeticBadge = $summaryBadge($diabetic);
    $thyroidBadge = $summaryBadge($thyroid);
@endphp

@section('title', $name)

@section('content')
    <div
        x-data="patientChart(@js([
            'editing' => $errors->any() && $chartAction === null,
            'action' => $chartAction,
            'reportsOpen' => false,
            'visitId' => (string) ($actionDefaultId ?? ''),
            'visits' => $actionVisits,
        ]))"
        @keydown.escape.window="menu = false; reportsOpen = false; if (action) closeAction()"
    >
        @if (session('status'))
            <p class="mb-4 rounded-lg border border-[#BBF7D0] bg-[#F0FDF4] px-4 py-3 text-sm font-medium text-success">{{ session('status') }}</p>
        @endif

        <header class="mb-5 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <x-ui.avatar :name="$name" size="2xl" tone="lavender" />
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="truncate text-2xl font-semibold tracking-tight text-[#181A1E]">{{ $name }}</h1>
                        @if (filled($addedLabel ?? null))
                            <span class="badge-info">{{ $addedLabel }}</span>
                        @endif
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-[#64748B]">
                        @if ($ageLabel)
                            <span class="inline-flex items-center gap-1.5">
                                <x-ui.icon name="user" class="h-4 w-4" />
                                {{ $ageLabel }}
                            </span>
                        @endif
                        @if ($genderLabel)
                            <span class="inline-flex items-center gap-1.5">
                                <x-ui.icon name="gender" class="h-4 w-4" />
                                {{ $genderLabel }}
                            </span>
                        @endif
                        @if (filled($patient->phone))
                            <span class="inline-flex items-center gap-1.5">
                                <x-ui.icon name="phone" class="h-4 w-4" />
                                {{ $patient->phone }}
                            </span>
                        @endif
                        @if (filled($patient->email))
                            <span class="inline-flex min-w-0 items-center gap-1.5">
                                <x-ui.icon name="mail" class="h-4 w-4 shrink-0" />
                                <span class="truncate">{{ $patient->email }}</span>
                            </span>
                        @endif
                    </div>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @foreach ($chips as $chip)
                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold {{ $chip['class'] }}">
                                <x-ui.icon :name="$chip['icon']" class="h-3.5 w-3.5" />
                                {{ $chip['label'] }}
                            </span>
                        @endforeach
                        @if ($lastVisit)
                            <span class="text-sm text-[#64748B]">Last visit: {{ $lastVisit }}</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-stretch gap-2 sm:items-end">
                <div class="flex items-center gap-2">
                    @if ($chartUrl)
                       
                    @endif
                    <div class="relative">
                        <button type="button" class="inline-flex h-11 w-11 items-center justify-center rounded-lg border border-[#E2E8F0] bg-white text-[#64748B] hover:bg-[#F8FAFC]" @click="menu = !menu" aria-label="More actions" :aria-expanded="menu">
                            <x-ui.icon name="dots" class="h-4 w-4" />
                        </button>
                        <div x-show="menu" x-cloak @click.outside="menu = false" class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-xl border border-[#E7EEF6] bg-white py-1 text-left shadow-lift">
                            <a href="{{ route('doctor.patients') }}" class="block px-3 py-2 text-sm font-medium text-[#181A1E] no-underline hover:bg-[#F8FAFC]">All patients</a>
                            @if ($visits !== [])
                                <a href="{{ route('doctor.appointments.show', $visits[0]['id']) }}" class="block px-3 py-2 text-sm font-medium text-[#181A1E] no-underline hover:bg-[#F8FAFC]">Open latest visit</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($analysisUrl)
                        <a href="{{ $analysisUrl }}" class="btn-secondary h-10">
                            <x-ui.icon name="spark" class="h-4 w-4 text-[#0067FF]" />
                            AI Analysis
                        </a>
                    @endif
                    @if ($actionVisits !== [])
                        <button type="button" class="btn-secondary h-10" @click="openAction('note')">
                            <x-ui.icon name="note" class="h-4 w-4 text-[#0067FF]" />
                            Add Note
                        </button>
                    @endif
                </div>
            </div>
        </header>

        <nav class="mb-4 flex gap-1 overflow-x-auto border-b border-[#E2E8F0]" aria-label="Chart sections">
            @foreach ([
                'overview' => ['Overview', 'grid'],
                'history' => ['Visit History', 'calendar'],
                'consultations' => ['Consultations', 'stethoscope'],
                'reports' => ['Reports', 'file'],
                'referrals' => ['Referrals', 'users'],
            ] as $key => [$label, $icon])
                <button
                    type="button"
                    @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'border-[#0067FF] text-[#0067FF]' : 'border-transparent text-[#64748B] hover:text-[#181A1E]'"
                    class="-mb-px inline-flex items-center gap-2 whitespace-nowrap border-b-2 px-3 py-2.5 text-sm font-semibold"
                >
                    <x-ui.icon :name="$icon" class="h-4 w-4" />
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <div x-show="tab === 'overview'" class="space-y-4">
            <div class="grid items-start gap-4 xl:grid-cols-[minmax(420px,1.25fr)_minmax(0,1fr)_minmax(260px,0.85fr)]">
                <form method="POST" action="{{ route('doctor.patients.update', $patient) }}" x-ref="chartForm" class="dash-panel h-full p-4 sm:p-5">
                    @csrf
                    @method('PUT')
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="flex items-center gap-2 text-[15px] font-semibold text-[#181A1E]">
                            <x-ui.icon name="id" class="h-4 w-4 text-[#0067FF]" />
                            Patient Information
                        </h2>
                        <!-- <button type="button" x-show="!editing" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0067FF]" @click="editing = true">
                            <x-ui.icon name="pencil" class="h-3.5 w-3.5" />
                            Edit
                        </button> -->
                    </div>

                    <dl class="mt-2">
                        @include('dashboard.doctor.patients.partials.fact', ['icon' => 'mail', 'label' => 'Email', 'value' => $patient->email ?: '—'])
                        <div class="flex items-center gap-3 border-b border-[#F1F5F9] py-2.5">
                            <x-ui.icon name="phone" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                            <dt class="w-32 shrink-0 text-sm text-[#64748B]">Phone</dt>
                            <dd class="min-w-0 text-left">
                                <span x-show="!editing" class="text-sm font-semibold text-[#181A1E]">{{ $patient->phone ?: '—' }}</span>
                                <input x-show="editing" x-cloak type="text" name="phone" value="{{ old('phone', $patient->phone) }}" class="field h-9 w-44 text-left">
                            </dd>
                        </div>
                        <div class="flex items-center gap-3 border-b border-[#F1F5F9] py-2.5">
                            <x-ui.icon name="calendar" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                            <dt class="w-32 shrink-0 text-sm text-[#64748B]">Age</dt>
                            <dd class="text-left">
                                <span x-show="!editing" class="text-sm font-semibold text-[#181A1E]">{{ $ageLabel ?: '—' }}</span>
                                <input x-show="editing" x-cloak type="number" min="0" max="120" name="age" value="{{ old('age', $profile?->age) }}" class="field h-9 w-24 text-left">
                            </dd>
                        </div>
                        <div class="flex items-center gap-3 border-b border-[#F1F5F9] py-2.5">
                            <x-ui.icon name="user" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                            <dt class="w-32 shrink-0 text-sm text-[#64748B]">Gender</dt>
                            <dd class="text-left">
                                <span x-show="!editing" class="text-sm font-semibold text-[#181A1E]">{{ $genderLabel ?: '—' }}</span>
                                <select x-show="editing" x-cloak name="gender" class="field h-9 w-32 text-left">
                                    <option value="">Select</option>
                                    @foreach (['male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('gender', $patient->gender) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </dd>
                        </div>
                        <div class="flex items-center gap-3 border-b border-[#F1F5F9] py-2.5">
                            <x-ui.icon name="droplet" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                            <dt class="w-32 shrink-0 text-sm text-[#64748B]">Blood type</dt>
                            <dd class="text-left">
                                <span x-show="!editing" class="text-sm font-semibold text-[#181A1E]">{{ $bloodLabel }}</span>
                                <input x-show="editing" x-cloak type="text" name="blood_type" value="{{ old('blood_type', $profile?->blood_type) }}" placeholder="O+" class="field h-9 w-24 text-left">
                            </dd>
                        </div>
                        <div class="flex items-center gap-3 border-b border-[#F1F5F9] py-2.5">
                            <x-ui.icon name="activity" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                            <dt class="w-32 shrink-0 text-sm text-[#64748B]">Blood pressure</dt>
                            <dd class="flex items-center gap-2 text-left">
                                <span x-show="!editing" class="text-sm font-semibold text-[#181A1E]">{{ $bpLabel }}</span>
                                @if ($bpStatus)
                                    <span x-show="!editing" class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $bpStatus['class'] }}">{{ $bpStatus['label'] }}</span>
                                @endif
                                <input x-show="editing" x-cloak type="text" name="bp" value="{{ old('bp', $bp['raw']) }}" placeholder="120/80" class="field h-9 w-28 text-left">
                            </dd>
                        </div>
                        <div class="flex items-center gap-3 border-b border-[#F1F5F9] py-2.5">
                            <x-ui.icon name="droplet" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                            <dt class="w-32 shrink-0 text-sm text-[#64748B]">Diabetic</dt>
                            <dd class="text-left">
                                <span x-show="!editing" class="text-sm font-semibold {{ $yesNoClass($diabetic['raw']) }}">{{ $yesNoLabel($diabetic) }}</span>
                                <select x-show="editing" x-cloak name="diabetic" class="field h-9 w-28 text-left">
                                    <option value="">Select</option>
                                    <option value="yes" @selected(old('diabetic', $diabetic['raw']) === 'yes')>Yes</option>
                                    <option value="no" @selected(old('diabetic', $diabetic['raw']) === 'no')>No</option>
                                </select>
                            </dd>
                        </div>
                        <div class="flex items-center gap-3 py-2.5">
                            <x-ui.icon name="thyroid" class="h-4 w-4 shrink-0 text-[#94A3B8]" />
                            <dt class="w-32 shrink-0 text-sm text-[#64748B]">Hyperthyroidism</dt>
                            <dd class="text-left">
                                <span x-show="!editing" class="text-sm font-semibold {{ $yesNoClass($thyroid['raw']) }}">{{ $yesNoLabel($thyroid) }}</span>
                                <select x-show="editing" x-cloak name="hyperthyroidism" class="field h-9 w-28 text-left">
                                    <option value="">Select</option>
                                    <option value="yes" @selected(old('hyperthyroidism', $thyroid['raw']) === 'yes')>Yes</option>
                                    <option value="no" @selected(old('hyperthyroidism', $thyroid['raw']) === 'no')>No</option>
                                </select>
                            </dd>
                        </div>
                    </dl>
                    <div x-show="editing" x-cloak class="mt-3 flex justify-end gap-2">
                        <button type="button" class="btn-secondary h-9" @click="editing = false; $refs.chartForm.reset()">Cancel</button>
                        <button type="submit" class="btn-primary h-9">Save</button>
                    </div>
                    @if ($errors->any())
                        <p class="mt-3 text-sm text-[#B91C1C]">{{ $errors->first() }}</p>
                    @endif
                </form>

                <section class="dash-panel h-full p-4 sm:p-5">
                    <h2 class="flex items-center gap-2 text-[15px] font-semibold text-[#181A1E]">
                        <x-ui.icon name="activity" class="h-4 w-4 text-[#0067FF]" />
                        Health Summary
                    </h2>
                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-xl border border-[#EEF2F6] bg-[#FAFBFD] p-3">
                            <x-ui.icon name="heart" class="h-5 w-5 text-[#EF4444]" />
                            <p class="mt-2 text-lg font-semibold leading-none text-[#181A1E]">{{ $bp['raw'] !== '' ? preg_replace('/\s*\/\s*/', '/', $bp['raw']) : '—' }}</p>
                            <p class="mt-1 text-xs text-[#64748B]">Blood Pressure</p>
                            @if ($bpStatus)
                                <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $bpStatus['class'] }}">{{ $bpStatus['label'] }}</span>
                            @endif
                        </div>
                        <div class="rounded-xl border border-[#EEF2F6] bg-[#FAFBFD] p-3">
                            <x-ui.icon name="droplet" class="h-5 w-5 text-[#14B8A6]" />
                            <p class="mt-2 text-lg font-semibold leading-none text-[#181A1E]">{{ $yesNoLabel($diabetic) }}</p>
                            <p class="mt-1 text-xs text-[#64748B]">Diabetic</p>
                            <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $diabeticBadge['class'] }}">{{ $diabeticBadge['label'] }}</span>
                        </div>
                        <div class="rounded-xl border border-[#EEF2F6] bg-[#FAFBFD] p-3">
                            <x-ui.icon name="thyroid" class="h-5 w-5 text-[#E11D48]" />
                            <p class="mt-2 text-lg font-semibold leading-none text-[#181A1E]">{{ $yesNoLabel($thyroid) }}</p>
                            <p class="mt-1 text-xs text-[#64748B]">Hyperthyroidism</p>
                            <span class="mt-2 inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $thyroidBadge['class'] }}">{{ $thyroidBadge['label'] }}</span>
                        </div>
                        <div class="rounded-xl border border-[#EEF2F6] bg-[#FAFBFD] p-3">
                            <x-ui.icon name="pill" class="h-5 w-5 text-[#3B82F6]" />
                            <p class="mt-2 text-lg font-semibold leading-none text-[#181A1E]">{{ $allergyLabel ?: 'Allergies' }}</p>
                            <p class="mt-1 text-xs text-[#64748B]">{{ $allergyLabel ? 'Allergies' : 'Not specified' }}</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-xl bg-[#F4F8FF] px-4 py-3">
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-[#181A1E]">
                            <x-ui.icon name="clipboard" class="h-4 w-4 text-[#0067FF]" />
                            Key Notes
                        </h3>
                        @if ($keyNotes !== [])
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-[#4E545F]">
                                @foreach ($keyNotes as $note)
                                    <li>{{ $note }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p class="mt-2 text-sm text-[#64748B]">No chart notes yet.</p>
                        @endif
                    </div>
                </section>

                <section class="dash-panel h-full p-4 sm:p-5">
                    <h2 class="flex items-center gap-2 text-[15px] font-semibold text-[#181A1E]">
                        <x-ui.icon name="bolt" class="h-4 w-4 text-[#0067FF]" />
                        Quick Actions
                    </h2>
                    <div class="mt-4 flex flex-col gap-2">
                        <button type="button" class="btn-secondary h-11 w-full" @click="reportsOpen = true">
                            <x-ui.icon name="file" class="h-4 w-4 text-[#0067FF]" />
                            View All Reports
                        </button>
                        @if ($actionVisits !== [])
                            <button type="button" class="btn-secondary h-11 w-full" @click="openAction('note')">
                                <x-ui.icon name="note" class="h-4 w-4 text-[#0067FF]" />
                                Add Note
                            </button>
                            @if ($canRefer)
                                <button type="button" class="btn-secondary h-11 w-full" @click="$dispatch('open-referral')">
                                    <x-ui.icon name="users" class="h-4 w-4 text-[#0067FF]" />
                                    Send Referral
                                </button>
                            @endif
                            <button type="button" class="btn-secondary h-11 w-full" @click="openAction('follow')">
                                <x-ui.icon name="calendar" class="h-4 w-4 text-[#0067FF]" />
                                Schedule Follow-up
                            </button>
                        @else
                            <p class="text-sm text-[#64748B]">Book a confirmed visit before adding a note, referral, or follow-up.</p>
                        @endif
                    </div>
                </section>
            </div>

            <div class="grid items-start gap-4 xl:grid-cols-3">
                <section class="dash-panel p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-[15px] font-semibold text-[#181A1E]">Recent Visit History</h2>
                        <button type="button" class="text-sm font-semibold text-[#0067FF]" @click="tab = 'history'">View All</button>
                    </div>
                    @include('dashboard.doctor.patients.partials.visits', ['rows' => array_slice($visits, 0, 4), 'statusClass' => $statusClass])
                </section>

                <section class="dash-panel p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-[15px] font-semibold text-[#181A1E]">Recent Consultations</h2>
                        <button type="button" class="text-sm font-semibold text-[#0067FF]" @click="tab = 'consultations'">View All</button>
                    </div>
                    @include('dashboard.doctor.patients.partials.consultations', ['rows' => array_slice($consultations, 0, 2)])
                </section>

                <section class="dash-panel p-4 sm:p-5">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-[15px] font-semibold text-[#181A1E]">Reports</h2>
                        <button type="button" class="text-sm font-semibold text-[#0067FF]" @click="tab = 'reports'">View All</button>
                    </div>
                    @include('dashboard.doctor.patients.partials.reports', ['rows' => array_slice($withReports, 0, 5)])
                </section>
            </div>

            @include('dashboard.doctor.patients.partials.referrals', ['referrals' => $referrals, 'canRefer' => $canRefer])
        </div>

        <section x-show="tab === 'history'" x-cloak class="dash-panel p-4 sm:p-5">
            <h2 class="text-[15px] font-semibold text-[#181A1E]">Visit History</h2>
            @include('dashboard.doctor.patients.partials.visits', ['rows' => $visits, 'statusClass' => $statusClass])
        </section>

        <section x-show="tab === 'consultations'" x-cloak class="dash-panel p-4 sm:p-5">
            <h2 class="text-[15px] font-semibold text-[#181A1E]">Consultations</h2>
            @include('dashboard.doctor.patients.partials.consultations', ['rows' => $consultations])
        </section>

        <section x-show="tab === 'reports'" x-cloak class="dash-panel p-4 sm:p-5">
            <h2 class="text-[15px] font-semibold text-[#181A1E]">Reports</h2>
            @include('dashboard.doctor.patients.partials.reports', ['rows' => $withReports])
        </section>

        <div x-show="tab === 'referrals'" x-cloak>
            @include('dashboard.doctor.patients.partials.referrals', ['referrals' => $referrals, 'canRefer' => $canRefer])
        </div>

        @if ($canRefer)
            @include('dashboard.appointments.partials.referral-form', array_merge($referralDesk, [
                'appointment' => $referralAppointment,
                'referralModalOnly' => true,
                'referralVisits' => $actionVisits,
                'referralVisitId' => $actionDefaultId,
            ]))
        @endif

        @include('dashboard.doctor.patients.partials.action-modals', ['reportRows' => $withReports])
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('patientChart', (config) => ({
                tab: 'overview',
                menu: false,
                editing: Boolean(config.editing),
                action: config.action,
                reportsOpen: Boolean(config.reportsOpen),
                visitId: config.visitId ? String(config.visitId) : '',
                visits: Array.isArray(config.visits) ? config.visits : [],

                openAction(name) {
                    this.action = name;
                    this.menu = false;

                    if (!this.visit) {
                        this.visitId = this.visits[0] ? String(this.visits[0].id) : '';
                    }
                },

                closeAction() {
                    this.action = null;
                },

                get visit() {
                    return this.visits.find((item) => String(item.id) === String(this.visitId)) ?? this.visits[0] ?? null;
                },
            }));
        });
    </script>
@endpush
