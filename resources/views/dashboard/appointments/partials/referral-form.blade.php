@php
    $referralDoctors = $referralDoctors ?? [];
    $referralPreview = (bool) ($referralPreview ?? false);
    $referralSymptoms = $referralSymptoms ?? [];
    $referralSpecialties = $referralSpecialties ?? [];
    $referralDraft = $referralDraft ?? '';
    $sentReferrals = $sentReferrals ?? [];
    $referralModalOnly = (bool) ($referralModalOnly ?? false);
    $canRefer = $appointment->canRefer();
    $reportsChosen = old('report_ids_present');
    $selectedReports = collect(old('report_ids', []))->map(fn ($id) => (int) $id);
    $desk = [
        'preview' => $referralPreview,
        'doctors' => $referralDoctors,
        'draft' => $referralDraft,
        'reopen' => $errors->hasAny(['to_doctor_id', 'reason', 'clinical_question', 'urgency', 'report_ids', 'report_ids.*']),
        'doctorId' => (string) old('to_doctor_id', ''),
        'reason' => (string) old('reason', $referralDraft),
        'question' => (string) old('clinical_question', ''),
        'urgency' => (string) old('urgency', 'routine'),
        'previewUrl' => route('doctor.referrals.preview.store', $appointment),
        'sent' => \App\Support\MockReferralInbox::forAppointment((int) auth()->id(), (int) $appointment->id),
    ];

    if ($referralModalOnly) {
        $desk['visits'] = array_values($referralVisits ?? []);
        $desk['visitId'] = (string) ($referralVisitId ?: $appointment->id);
    }
@endphp

@if ($referralModalOnly ? $canRefer : ($canRefer || $sentReferrals !== []))
    <section
        @unless ($referralModalOnly) id="refer" class="dash-panel p-4 sm:p-5" @endunless
        x-data="referralDesk(@js($desk))"
        @open-referral.window="openList()"
    >
        @unless ($referralModalOnly)
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <h2 class="text-base font-semibold text-heading">Refer to another doctor</h2>
                <p class="mt-0.5 truncate text-sm text-body">
                    @if ($referralSymptoms !== [])
                        {{ implode(', ', $referralSymptoms) }}
                        @if ($referralSpecialties !== [])
                            <span class="text-muted">· {{ implode(', ', $referralSpecialties) }}</span>
                        @endif
                    @else
                        Send this visit to a specialist.
                    @endif
                </p>
            </div>
            @if ($canRefer && $referralDoctors !== [])
                <button type="button" class="btn-primary h-9 shrink-0 px-3 text-sm" @click="openList()">Refer</button>
            @endif
        </div>

        <p x-show="banner !== ''" x-cloak class="alert-success mt-3" x-text="banner"></p>

        @if ($sentReferrals !== [])
            <ul class="mt-3 divide-y divide-line rounded-lg border border-line">
                @foreach ($sentReferrals as $sent)
                    <li class="flex items-center justify-between gap-3 px-3 py-2">
                        <div class="min-w-0">
                            <a href="{{ route('doctor.referrals.show', $sent['id']) }}" class="text-sm font-semibold text-heading no-underline hover:text-primary">
                                {{ $sent['doctor'] }}
                                @if ($sent['specialization'] !== '')
                                    <span class="font-normal text-body">({{ $sent['specialization'] }})</span>
                                @endif
                            </a>
                            <p class="truncate text-xs text-body">{{ $sent['reason'] }}</p>
                        </div>
                        <span class="badge-info">{{ $sent['status'] }}</span>
                    </li>
                @endforeach
            </ul>
        @endif

        <ul class="mt-3 divide-y divide-line rounded-lg border border-line" x-show="sent.length > 0" x-cloak>
            <template x-for="item in sent" :key="item.id">
                <li class="flex items-center justify-between gap-3 px-3 py-2">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-heading">
                            <span x-text="item.name"></span>
                            <span class="font-normal text-body" x-text="'(' + item.specialization + ')'"></span>
                        </p>
                        <p class="truncate text-xs text-body" x-text="item.reason"></p>
                    </div>
                    <span class="badge-info">Sent</span>
                </li>
            </template>
        </ul>

        @if ($canRefer && $referralDoctors === [])
            <p class="mt-4 text-sm leading-6 text-body">No other approved doctors are available to receive a referral.</p>
        @endif
        @endunless

        @if ($canRefer && $referralDoctors !== [])
            <div
                x-show="open"
                x-cloak
                class="fixed inset-0 z-[80] flex items-end justify-center sm:items-center sm:p-6"
                role="dialog"
                aria-modal="true"
                aria-labelledby="refer-title"
                @keydown.escape.window="if (open) close()"
            >
                <div class="absolute inset-0 bg-[#0B1F3A]/50" @click="close()"></div>

                <div class="relative flex max-h-[100dvh] w-full flex-col overflow-hidden bg-white shadow-[0_24px_80px_rgba(11,31,58,0.28)] sm:max-h-[min(860px,92dvh)] sm:max-w-3xl sm:rounded-2xl">
                    <div class="flex items-center gap-3 border-b border-line px-5 py-4">
                        <div class="min-w-0 flex-1">
                            <h2 id="refer-title" class="truncate text-lg font-semibold text-navy" x-text="view === 'profile' ? 'Doctor profile' : (view === 'note' ? 'Referral note' : (view === 'sent' ? 'Referral sent' : 'Choose a specialist'))">Choose a specialist</h2>
                            <p class="mt-0.5 truncate text-sm text-body">
                                {{ $appointment->patient?->name ?? 'Patient' }}
                                @if (filled($appointment->reason))
                                    <span class="text-muted"> · {{ $appointment->reason }}</span>
                                @endif
                            </p>
                        </div>
                        <button type="button" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-navy hover:bg-page" @click="close()" aria-label="Close referral">
                            <x-ui.icon name="close" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5" :class="view === 'list' ? 'bg-[#F4F7FB]' : 'bg-white'">
                        @if ($referralModalOnly)
                            <label class="mb-4 block text-sm font-semibold text-navy" x-show="view !== 'sent' && visits.length" x-cloak>
                                Visit
                                <select class="field mt-1.5" x-model="visitId" @change="chooseVisit(visitId)">
                                    <template x-for="visit in visits" :key="visit.id">
                                        <option :value="String(visit.id)" x-text="visit.label"></option>
                                    </template>
                                </select>
                                <span class="mt-1 block text-xs font-medium text-body">This referral is saved on the visit you choose.</span>
                            </label>
                        @endif
                        <div x-show="view === 'list'">
                            <label class="sr-only" for="referral-doctor-search">Search doctors</label>
                            <input id="referral-doctor-search" type="search" x-model="query" placeholder="Search by name or specialty" autocomplete="off" class="field">
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="button" class="rounded-full border px-3 py-1.5 text-sm font-semibold" x-show="hasMatches" :class="specialty === 'suggested' ? 'border-primary bg-white text-primary shadow-sm' : 'border-transparent bg-white/70 text-body'" @click="specialty = 'suggested'">Suggested</button>
                                <button type="button" class="rounded-full border px-3 py-1.5 text-sm font-semibold" :class="specialty === 'all' ? 'border-primary bg-white text-primary shadow-sm' : 'border-transparent bg-white/70 text-body'" @click="specialty = 'all'">All</button>
                                <template x-for="name in specialtyNames" :key="name">
                                    <button type="button" class="rounded-full border px-3 py-1.5 text-sm font-semibold" :class="specialty === name ? 'border-primary bg-white text-primary shadow-sm' : 'border-transparent bg-white/70 text-body'" @click="specialty = name" x-text="name"></button>
                                </template>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($referralDoctors as $choice)
                                    <article
                                        class="flex gap-3 rounded-2xl border border-[#E7EEF6] bg-white p-3 shadow-[0_8px_20px_rgba(15,23,42,0.05)]"
                                        x-show="visible(@js($choice['id']))"
                                    >
                                        @if (! empty($choice['photo']))
                                            <img src="{{ $choice['photo'] }}" alt="" class="h-14 w-14 shrink-0 rounded-full object-cover object-top ring-2 ring-[#EAF3FF]">
                                        @else
                                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-[#EAF3FF] text-sm font-semibold text-primary">{{ $choice['initials'] }}</span>
                                        @endif
                                        <div class="flex min-w-0 flex-1 flex-col">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <h3 class="truncate text-sm font-semibold text-navy">{{ $choice['name'] }}</h3>
                                                    <p class="truncate text-xs font-semibold text-primary">{{ $choice['specialization'] ?: 'Specialty not listed' }}</p>
                                                </div>
                                                @if (($choice['fee'] ?? '') !== '')
                                                    <p class="shrink-0 text-sm font-bold text-navy">{{ $choice['fee'] }}</p>
                                                @endif
                                            </div>
                                            @if (($choice['degree'] ?? '') !== '' || ($choice['clinic'] ?? '') !== '')
                                                <p class="mt-0.5 truncate text-xs text-body">{{ collect([$choice['degree'] ?? '', $choice['clinic'] ?? ''])->filter()->implode(' · ') }}</p>
                                            @endif
                                            @if ((int) ($choice['reviews'] ?? 0) > 0)
                                                <p class="mt-1 text-xs text-navy">
                                                    {{ number_format((float) $choice['rating'], 1) }}
                                                    <span class="text-muted">({{ (int) $choice['reviews'] }} {{ (int) $choice['reviews'] === 1 ? 'rating' : 'ratings' }})</span>
                                                </p>
                                            @endif
                                            @if (! empty($choice['matched']))
                                                <p class="mt-1 truncate text-xs font-medium text-success">Matches this visit</p>
                                            @endif
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                <button type="button" class="btn-secondary h-8 px-2 text-xs" @click="showProfile(@js($choice['id']))">View profile</button>
                                                <button type="button" class="btn-primary h-8 px-2 text-xs" @click="startReferral(@js($choice['id']))">Refer</button>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <p x-show="visibleCount() === 0" x-cloak class="mt-4 text-sm text-body">No doctor matches that search.</p>
                        </div>

                        <div x-show="view === 'profile'" x-cloak>
                            <button type="button" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary" @click="view = 'list'">
                                <x-ui.icon name="arrow" class="h-4 w-4 rotate-180" />
                                Back to specialists
                            </button>
                            <div class="mt-4 flex items-start gap-4 rounded-2xl border border-[#E7EEF6] bg-[#F4F7FB] p-4">
                                <template x-if="doctor && doctor.photo">
                                    <img :src="doctor.photo" alt="" class="h-16 w-16 shrink-0 rounded-full object-cover object-top ring-2 ring-white">
                                </template>
                                <div x-show="doctor && !doctor.photo" class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-white text-lg font-semibold text-primary ring-2 ring-[#EAF3FF]" x-text="doctor ? doctor.initials : ''"></div>
                                <div class="min-w-0">
                                    <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-primary" x-text="doctor ? doctor.specialization : ''"></span>
                                    <h3 class="mt-2 text-xl font-semibold tracking-tight text-heading" x-text="doctor ? doctor.name : ''"></h3>
                                    <p class="mt-1 text-sm text-body" x-text="doctor ? [doctor.degree, doctor.clinic].filter(Boolean).join(' · ') : ''"></p>
                                    <p class="mt-1 text-sm text-navy" x-show="doctor && (doctor.reviews > 0 || doctor.experience || doctor.fee)" x-cloak>
                                        <span x-show="doctor && doctor.reviews > 0">
                                            <span class="font-semibold text-[#E99A17]" x-text="doctor ? Number(doctor.rating).toFixed(1) : ''"></span>
                                            <span class="text-muted" x-text="doctor ? '(' + doctor.reviews + (doctor.reviews === 1 ? ' rating' : ' ratings') + ')' : ''"></span>
                                        </span>
                                        <span class="text-muted" x-show="doctor && doctor.experience" x-text="doctor && doctor.experience ? (doctor.reviews > 0 ? ' · ' : '') + doctor.experience : ''"></span>
                                        <span class="font-bold" x-show="doctor && doctor.fee" x-text="doctor && doctor.fee ? (doctor.reviews > 0 || doctor.experience ? ' · ' : '') + doctor.fee : ''"></span>
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4 space-y-4">
                                <p class="text-sm leading-7 text-body" x-text="doctor && doctor.bio ? doctor.bio : 'No biography has been added yet.'"></p>
                                <p class="text-sm leading-7 text-body" x-show="doctor && doctor.about && doctor.about !== doctor.bio" x-cloak x-text="doctor ? doctor.about : ''"></p>
                                <div x-show="doctor && doctor.qualifications && doctor.qualifications.length" x-cloak>
                                    <p class="text-sm font-semibold text-heading">Qualifications</p>
                                    <ul class="mt-2 space-y-2">
                                        <template x-for="(item, index) in (doctor ? doctor.qualifications : [])" :key="'q-' + index">
                                            <li class="rounded-xl bg-[#F4F7FB] px-3 py-2">
                                                <p class="text-sm font-semibold text-heading" x-text="item.degree"></p>
                                                <p class="text-xs text-body" x-show="item.institution || item.period" x-text="[item.institution, item.period].filter(Boolean).join(' · ')"></p>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                                <div x-show="doctor && doctor.experiences && doctor.experiences.length" x-cloak>
                                    <p class="text-sm font-semibold text-heading">Experience</p>
                                    <ul class="mt-2 space-y-2">
                                        <template x-for="(item, index) in (doctor ? doctor.experiences : [])" :key="'e-' + index">
                                            <li class="rounded-xl bg-[#F4F7FB] px-3 py-2">
                                                <p class="text-sm font-semibold text-heading" x-text="[item.role, item.place].filter(Boolean).join(' · ')"></p>
                                                <p class="text-xs text-body" x-show="item.period" x-text="item.period"></p>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                                <div x-show="doctor && doctor.focus && doctor.focus.length" x-cloak>
                                    <p class="text-sm font-semibold text-heading">Sees patients for</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <template x-for="item in (doctor ? doctor.focus : [])" :key="item">
                                            <span class="rounded-full bg-[#F4F7FB] px-3 py-1 text-xs font-semibold text-heading" x-text="item"></span>
                                        </template>
                                    </div>
                                </div>
                                <p class="rounded-xl bg-[#F0FDF4] px-4 py-3 text-sm font-medium text-success" x-show="doctor && doctor.matched" x-cloak x-text="doctor ? 'Fits this visit: ' + doctor.matchedOn.join(', ') : ''"></p>
                            </div>
                            <div class="mt-5 flex justify-end gap-2">
                                <button type="button" class="btn-secondary" @click="view = 'list'">Back</button>
                                <button type="button" class="btn-primary" @click="startReferral(doctorId)">Refer this doctor</button>
                            </div>
                        </div>

                        @unless ($referralPreview)
                            <form method="POST" action="{{ route('doctor.referrals.store', $appointment) }}" @if ($referralModalOnly) :action="currentVisit ? currentVisit.refer_url : ''" @endif x-show="view === 'note'" x-cloak class="space-y-3">
                                @csrf
                                <input type="hidden" name="report_ids_present" value="1">
                                <input type="hidden" name="to_doctor_id" :value="doctorId">
                        @else
                            <div x-show="view === 'note'" x-cloak class="space-y-3">
                        @endunless
                            <div class="flex items-center gap-2">
                                <template x-if="doctor && doctor.photo">
                                    <img :src="doctor.photo" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover object-top">
                                </template>
                                <div x-show="doctor && !doctor.photo" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#EAF3FF] text-xs font-semibold text-primary" x-text="doctor ? doctor.initials : ''"></div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-heading" x-text="doctor ? doctor.name : ''"></p>
                                    <p class="truncate text-xs text-body" x-text="doctor ? [doctor.specialization, doctor.clinic].filter(Boolean).join(' · ') : ''"></p>
                                </div>
                                <button type="button" class="shrink-0 text-xs font-semibold text-primary" @click="showProfile(doctorId)">View profile</button>
                            </div>
                            <p class="text-xs text-success" x-show="doctor && doctor.matched" x-cloak x-text="doctor ? 'Fits ' + doctor.matchedOn.join(', ') : ''"></p>

                            <label class="block text-sm font-semibold text-navy">
                                Reason
                                <textarea name="reason" x-model="reason" @input="reasonTouched = true" rows="3" maxlength="5000" required class="field mt-1.5" placeholder="What should this doctor take over?"></textarea>
                            </label>
                            <x-input-error :messages="$errors->get('reason')" class="mt-1" />

                            <div class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_9rem]">
                                <label class="block text-sm font-semibold text-navy">
                                    Clinical question
                                    <textarea name="clinical_question" x-model="question" rows="2" maxlength="5000" class="field mt-1.5" placeholder="Optional"></textarea>
                                </label>
                                <label class="block text-sm font-semibold text-navy">
                                    Urgency
                                    <select name="urgency" x-model="urgency" class="field mt-1.5">
                                        @foreach (['routine' => 'Routine', 'soon' => 'Soon', 'urgent' => 'Urgent'] as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('clinical_question')" class="mt-1" />
                            <x-input-error :messages="$errors->get('urgency')" class="mt-1" />
                            <x-input-error :messages="$errors->get('to_doctor_id')" class="mt-1" />

                            @php
                                $referralSummary = $referralSummary ?? [];
                                $summaryText = $referralSummary['text'] ?? null;
                                $legacySummary = trim((string) ($appointment->consultation?->ai_summary ?? ''));
                                $hasStructuredAnalysis = $appointment->relationLoaded('analyses')
                                    && $appointment->analyses->contains(fn ($row) => filled($row->original_summary) || filled($row->clinician_summary) || filled($row->approved_summary));
                                $showReferralSummary = filled($summaryText)
                                    && ($summaryText !== $legacySummary || $legacySummary === '' || $hasStructuredAnalysis);
                            @endphp
                            @if ($showReferralSummary)
                                <div class="rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-3 py-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="text-sm font-semibold text-navy">Patient summary</p>
                                        <span class="text-xs font-semibold text-primary">
                                            @if (($referralSummary['source'] ?? '') === 'approved')
                                                Approved
                                            @elseif (($referralSummary['source'] ?? '') === 'analysis')
                                                Included
                                            @else
                                                From the chart
                                            @endif
                                        </span>
                                    </div>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-heading">{{ $referralSummary['text'] }}</p>
                                    @if (filled($referralSummary['referral_brief'] ?? null) && $referralSummary['referral_brief'] !== $referralSummary['text'])
                                        <p class="mt-2 whitespace-pre-line text-sm leading-6 text-body">{{ $referralSummary['referral_brief'] }}</p>
                                    @endif
                                    <p class="mt-2 text-xs leading-5 text-body">
                                        @if ($appointment->reports->isNotEmpty())
                                            This summary is sent with the reports you select below.
                                        @else
                                            This summary is sent with the referral.
                                        @endif
                                    </p>
                                </div>
                            @endif

                            @if ($referralModalOnly)
                                <fieldset x-show="currentVisit && currentVisit.reports.length" x-cloak>
                                    <legend class="text-sm font-semibold text-navy">Reports</legend>
                                    <div class="mt-1.5 space-y-1.5">
                                        <template x-for="report in (currentVisit ? currentVisit.reports : [])" :key="report.id">
                                            <label class="flex items-center gap-2 text-sm text-heading">
                                                <input type="checkbox" name="report_ids[]" :value="report.id" class="h-4 w-4 rounded border-line text-primary focus:ring-primary" checked>
                                                <span class="truncate" x-text="report.name"></span>
                                            </label>
                                        </template>
                                    </div>
                                </fieldset>
                            @elseif ($appointment->reports->isNotEmpty())
                                <fieldset>
                                    <legend class="text-sm font-semibold text-navy">Reports</legend>
                                    <div class="mt-1.5 space-y-1.5">
                                        @foreach ($appointment->reports as $report)
                                            <label class="flex items-center gap-2 text-sm text-heading">
                                                <input
                                                    type="checkbox"
                                                    name="report_ids[]"
                                                    value="{{ $report->id }}"
                                                    class="h-4 w-4 rounded border-line text-primary focus:ring-primary"
                                                    @checked($reportsChosen ? $selectedReports->contains($report->id) : true)
                                                >
                                                <span class="truncate">{{ $report->original_name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif

                            <p x-show="error !== ''" x-cloak x-text="error" class="text-sm text-danger"></p>

                            <div class="flex justify-end gap-2 pt-1">
                                <button type="button" class="btn-secondary h-9 px-3 text-sm" @click="view = 'list'">Back</button>
                                @unless ($referralPreview)
                                    <button type="submit" class="btn-primary h-9 px-3 text-sm">Send referral</button>
                                @else
                                    <button type="button" class="btn-primary h-9 px-3 text-sm" @click="sendPreview()" :disabled="saving" x-text="saving ? 'Sending…' : 'Send referral'">Send referral</button>
                                @endunless
                            </div>
                        @unless ($referralPreview)
                            </form>
                        @else
                            </div>
                        @endunless

                        <div x-show="view === 'sent'" x-cloak class="py-2 text-center">
                            <span class="mx-auto flex h-11 w-11 items-center justify-center rounded-full bg-[#E8F8EF] text-success">
                                <x-ui.icon name="check" class="h-5 w-5" />
                            </span>
                            <h3 class="mt-3 text-base font-semibold text-heading">Referral sent</h3>
                            <p class="mt-1 text-sm text-body" x-text="doctor ? doctor.name + ' · ' + doctor.specialization : ''"></p>
                            <dl class="mt-4 space-y-2 rounded-lg border border-line px-3 py-2.5 text-left text-sm">
                                <div class="flex justify-between gap-3">
                                    <dt class="text-body">Patient</dt>
                                    <dd class="truncate font-semibold text-heading">{{ $appointment->patient?->name ?? 'Patient' }}</dd>
                                </div>
                                <div class="flex justify-between gap-3">
                                    <dt class="text-body">Urgency</dt>
                                    <dd class="font-semibold text-heading" x-text="urgencyLabel()"></dd>
                                </div>
                                @if (filled($referralSummary['text'] ?? null))
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-body">Patient summary</dt>
                                        <dd class="font-semibold text-heading">Included</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-body">Reason</dt>
                                    <dd class="mt-0.5 whitespace-pre-line text-heading" x-text="reason"></dd>
                                </div>
                            </dl>
                            <p class="mt-3 text-sm leading-6 text-body">They can accept or decline, then book the visit. This referral is now listed under Referrals.</p>
                            <div class="mt-4 flex justify-center gap-2">
                                <button type="button" class="btn-secondary h-9 px-3 text-sm" @click="referAnother()">Refer another</button>
                                <button type="button" class="btn-primary h-9 px-3 text-sm" @click="close()">Done</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </section>
@endif
