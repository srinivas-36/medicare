@extends('layouts.dashboard')

@php
    $doctorName = $doctorName ?? null;
    $hours = $hours ?? [];
    $duration = $duration ?? 30;
    $specialization = $specialization ?? null;
@endphp

@section('title', 'Book a visit')

@section('content')
    <x-ui.page-header
        title="Book a visit"
        description="{{ $doctorName ? 'Add a new patient or choose an existing one, then book one of '.$doctorName.'’s saved time slots.' : 'This desk is not linked to a doctor yet.' }}"
    >
        <a href="{{ route('receptionist.appointments') }}" class="btn-secondary">Back</a>
    </x-ui.page-header>

    @if (! $doctorName)
        @include('dashboard.receptionist.partials.unassigned')
    @else
        @if ($errors->any())
            <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif

        <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]" x-data="deskBooking()">
            <form method="POST" action="{{ route('receptionist.appointments.store') }}" class="space-y-6">
                @csrf
                <input type="hidden" name="patient_mode" :value="patientMode">
                <input type="hidden" name="patient_id" :value="patientMode === 'existing' ? patientId : ''" :disabled="patientMode !== 'existing'">
                <input type="hidden" name="date" :value="date">
                <input type="hidden" name="slot" :value="slot">

                <section class="dash-panel p-6 sm:p-8" aria-live="polite">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">Step 1</p>
                    <h2 class="mt-1 text-lg font-bold text-navy">Patient</h2>
                    <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Patient type">
                        <button type="button" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold" :class="patientMode === 'existing' ? 'bg-[#0067FF] text-white' : 'bg-white text-body ring-1 ring-line'" @click="patientMode = 'existing'">Existing patient</button>
                        <button type="button" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold" :class="patientMode === 'new' ? 'bg-[#0067FF] text-white' : 'bg-white text-body ring-1 ring-line'" @click="patientMode = 'new'">New patient</button>
                    </div>

                    <div x-show="patientMode === 'existing'" class="relative mt-5 max-w-xl" @click.outside="menuOpen = false">
                        <label for="q" class="label">Find a patient</label>
                        <p class="mb-3 text-sm text-text">Search by name, email, or phone, then choose someone from the list.</p>
                        <div class="relative">
                            <input
                                id="q"
                                type="search"
                                x-model="query"
                                @input="scheduleSearch()"
                                @focus="menuOpen = query.trim().length >= 2"
                                @keydown.enter.prevent="lookup()"
                                @keydown.escape="menuOpen = false"
                                placeholder="Name, email, or phone"
                                autocomplete="off"
                                role="combobox"
                                :aria-expanded="menuOpen ? 'true' : 'false'"
                                aria-controls="patient-results"
                                :aria-busy="searching ? 'true' : 'false'"
                                class="field pr-10"
                            >
                            <span x-show="searching" class="absolute right-3 top-1/2 -translate-y-1/2 text-primary" role="status" aria-label="Searching patients">
                                <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"></circle>
                                    <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                </svg>
                            </span>
                        </div>
                        <div
                            id="patient-results"
                            x-show="menuOpen"
                            x-cloak
                            role="listbox"
                            class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-xl border border-line bg-white py-1 shadow-lift"
                        >
                            <p x-show="searching" class="px-4 py-3 text-sm text-text">Searching patients…</p>
                            <p x-show="!searching && searchError !== ''" x-text="searchError" class="px-4 py-3 text-sm text-red-600"></p>
                            <p x-show="!searching && searchError === '' && statusText() !== ''" x-text="statusText()" class="px-4 py-3 text-sm text-text"></p>
                            <template x-for="patient in visiblePatients()" :key="patient.id">
                                <button
                                    type="button"
                                    role="option"
                                    @click="choose(patient)"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-light"
                                    :class="String(patientId) === String(patient.id) ? 'bg-light' : ''"
                                    :aria-selected="String(patientId) === String(patient.id) ? 'true' : 'false'"
                                >
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-xs font-semibold text-primary" x-text="initials(patient.name)"></span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-navy" x-text="patient.name"></span>
                                        <span class="block truncate text-xs text-text" x-text="[patient.email, patient.phone].filter(Boolean).join(' · ')"></span>
                                    </span>
                                </button>
                            </template>
                        </div>
                        <p x-show="chosen" class="mt-3 text-sm font-semibold text-navy">
                            Selected: <span x-text="chosen ? [chosen.name, chosen.email, chosen.phone].filter(Boolean).join(' · ') : ''"></span>
                        </p>
                    </div>

                    <div x-show="patientMode === 'new'" class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="new-name" class="label">Full name</label>
                            <input id="new-name" name="name" x-model="newName" :disabled="patientMode !== 'new'" :required="patientMode === 'new'" class="field" autocomplete="name">
                        </div>
                        <div>
                            <label for="new-email" class="label">Email</label>
                            <input id="new-email" type="email" name="email" x-model="newEmail" :disabled="patientMode !== 'new'" :required="patientMode === 'new'" class="field" autocomplete="off">
                        </div>
                        <div>
                            <label for="new-phone" class="label">Phone</label>
                            <input id="new-phone" name="phone" x-model="newPhone" :disabled="patientMode !== 'new'" :required="patientMode === 'new'" class="field">
                        </div>
                        <div>
                            <label for="new-age" class="label">Age</label>
                            <input id="new-age" type="number" name="age" min="0" max="120" x-model="newAge" :disabled="patientMode !== 'new'" :required="patientMode === 'new'" class="field">
                        </div>
                        <div>
                            <label for="new-gender" class="label">Gender</label>
                            <select id="new-gender" name="gender" x-model="newGender" :disabled="patientMode !== 'new'" class="field">
                                <option value="">Not specified</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label for="new-password" class="label">Sign-in password</label>
                            <input id="new-password" type="password" name="password" x-model="newPassword" :disabled="patientMode !== 'new'" class="field" autocomplete="new-password">
                            <p class="mt-2 text-xs leading-5 text-muted">Required for a new email. Leave blank if this patient already has an account.</p>
                        </div>
                        <div>
                            <label for="new-password-confirmation" class="label">Confirm password</label>
                            <input id="new-password-confirmation" type="password" name="password_confirmation" x-model="newPasswordConfirmation" :disabled="patientMode !== 'new'" class="field" autocomplete="new-password">
                        </div>
                    </div>
                </section>

                <section class="dash-panel p-6 sm:p-8">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">Step 2</p>
                    <h2 class="mt-1 text-lg font-bold text-navy">Choose date and time</h2>
                    <p class="mt-1 text-sm text-text">Open days follow this doctor’s saved hours. Each choice is one exact saved time slot.</p>

                    <div class="mt-5 grid gap-4 lg:grid-cols-[268px_minmax(0,1fr)]">
                        <div class="rounded-2xl border border-line bg-white p-3">
                            <div class="flex items-center justify-between px-1">
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-body hover:bg-page disabled:text-[#d5deea]" @click="shiftMonth(-1)" :disabled="!canShift(-1)" aria-label="Previous month">
                                    <x-ui.icon name="chevron" class="h-4 w-4 rotate-90" />
                                </button>
                                <p class="text-sm font-semibold text-navy" x-text="monthTitle()"></p>
                                <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-body hover:bg-page disabled:text-[#d5deea]" @click="shiftMonth(1)" :disabled="!canShift(1)" aria-label="Next month">
                                    <x-ui.icon name="chevron" class="h-4 w-4 -rotate-90" />
                                </button>
                            </div>
                            <div class="mt-3 grid grid-cols-7 gap-y-1 text-center text-[11px] font-medium text-muted">
                                <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                            </div>
                            <div class="mt-1 grid grid-cols-7 gap-y-1">
                                <template x-for="(day, index) in calendarCells()" :key="day ? day.date : 'pad-' + index">
                                    <button
                                        type="button"
                                        class="mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-medium disabled:cursor-default"
                                        :disabled="!day || !day.open"
                                        @click="selectDate(day.date)"
                                        :class="!day ? 'invisible' : (date === day.date ? 'bg-primary font-semibold text-white' : (day.open ? 'text-navy hover:bg-light' : 'text-[#b7c3d4]'))"
                                        x-text="day ? day.label : ''"
                                    ></button>
                                </template>
                            </div>
                            <div class="mt-3 flex items-center gap-3 px-1 text-[11px] text-body">
                                <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-primary"></span> Open</span>
                                <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-[#d5deea]"></span> Closed</span>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <p class="text-sm font-semibold text-navy">Available time slots</p>
                                <p class="text-sm font-medium text-text" x-text="date === '' ? '' : prettyDate(date)"></p>
                            </div>
                            <p x-show="date === ''" class="flex min-h-[220px] items-center justify-center rounded-xl border border-dashed border-line bg-page px-4 text-center text-sm text-text">
                                Select a date to see this doctor’s saved time slots.
                            </p>
                            <p x-show="date !== '' && groups().length === 0" class="rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-4 py-3 text-sm leading-6 text-ink">
                                This doctor has no saved time slot on this date. Choose another open day.
                            </p>
                            <div x-show="exactSlots().length > 0" class="grid gap-2 sm:grid-cols-2">
                                <template x-for="item in exactSlots()" :key="item.value">
                                    <button
                                        type="button"
                                        @click="slot = item.value"
                                        class="rounded-xl border px-4 py-3 text-left transition"
                                        :class="slot === item.value ? 'border-primary bg-primary text-white' : 'border-line bg-white text-navy hover:border-primary'"
                                        :aria-pressed="slot === item.value ? 'true' : 'false'"
                                    >
                                        <span class="block text-[11px] font-semibold uppercase tracking-[0.12em]" :class="slot === item.value ? 'text-white/80' : 'text-primary'" x-text="item.period"></span>
                                        <span class="mt-1 block text-sm font-semibold" x-text="item.range"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="dash-panel p-6 sm:p-8">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">Step 3</p>
                    <h2 class="mt-1 text-lg font-bold text-navy">Visit details and confirm</h2>
                    <div class="mt-4 rounded-xl bg-page px-4 py-3 text-sm" x-show="summary() !== ''">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">Selected visit</p>
                        <p class="mt-1 font-semibold text-navy" x-text="summary()"></p>
                    </div>
                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="visit_type" class="label">Visit type</label>
                            <select id="visit_type" name="visit_type" required class="field">
                                @foreach (['consultation' => 'Consultation', 'check-up' => 'Check-up', 'follow-up' => 'Follow-up', 'emergency' => 'Emergency'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('visit_type', 'consultation') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="reason" class="label">Reason</label>
                            <input id="reason" type="text" name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Reason for the visit" class="field">
                        </div>
                    </div>

                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <button type="submit" class="btn-primary" :disabled="!canConfirm()" @click.prevent="askToBook($event.currentTarget)">Confirm booking</button>
                        <a href="{{ route('receptionist.appointments') }}" class="btn-ghost">Cancel</a>
                    </div>
                </section>
            </form>

            <aside class="dash-panel h-fit p-6 lg:sticky lg:top-0">
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-primary">Your doctor</p>
                <h2 class="mt-2 text-xl font-bold text-navy">{{ $doctorName }}</h2>
                @if ($specialization)
                    <p class="mt-1 text-sm text-text">{{ $specialization }}</p>
                @endif
                <h3 class="mt-6 text-sm font-semibold text-navy">Saved weekly hours</h3>
                @if (count($hours))
                    <ul class="mt-3 space-y-2">
                        @foreach ($hours as $hour)
                            <li class="flex items-center justify-between gap-3 rounded-xl bg-page px-3 py-2 text-sm">
                                <span class="font-semibold text-navy">{{ $hour['day'] }}</span>
                                <span class="text-text">{{ $hour['from'] }} – {{ $hour['to'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-3 text-sm leading-6 text-text">No weekly hours are published yet.</p>
                @endif
                <p class="mt-4 text-xs leading-5 text-muted">These are the exact times this doctor saved. A slot disappears after it is booked.</p>
            </aside>
        </div>

        <script type="application/json" id="desk-booking-data">@json($desk)</script>
        @push('scripts')
            <script>
                window.deskBooking = function () {
                    const node = document.getElementById('desk-booking-data');
                    const config = node ? JSON.parse(node.textContent || '{}') : {};
                    const patients = Array.isArray(config.patients) ? config.patients : [];
                    const patientId = String(config.patientId || '');

                    return {
                        searchUrl: config.searchUrl || '',
                        query: config.query || '',
                        patients,
                        patientId,
                        patientMode: config.patientMode === 'new' ? 'new' : 'existing',
                        newName: config.newName || '',
                        newEmail: config.newEmail || '',
                        newPhone: config.newPhone || '',
                        newAge: config.newAge || '',
                        newGender: config.newGender || '',
                        newPassword: '',
                        newPasswordConfirmation: '',
                        chosen: patients.find((patient) => String(patient.id) === patientId) || null,
                        searched: Boolean(config.searched),
                        searching: false,
                        searchError: '',
                        menuOpen: false,
                        statusText() {
                            if (this.searching || this.searchError !== '' || this.visiblePatients().length > 0) {
                                return '';
                            }
                            const term = this.query.trim();
                            if (term.length === 1) {
                                return 'Type at least 2 characters.';
                            }
                            if (this.searched) {
                                return 'No patients match that search.';
                            }
                            return '';
                        },
                        date: config.date || '',
                        slot: config.slot || '',
                        days: config.days || {},
                        minDate: config.minDate || '',
                        maxDate: config.maxDate || '',
                        cursor: config.month || '',
                        duration: config.duration || 30,
                        controller: null,
                        timer: null,
                        visiblePatients() {
                            return this.patients;
                        },
                        choose(patient) {
                            this.patientId = String(patient.id);
                            this.chosen = patient;
                            this.query = patient.name || '';
                            this.menuOpen = false;
                        },
                        initials(name) {
                            const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
                            const letters = (parts[0] ? parts[0][0] : '') + (parts[1] ? parts[1][0] : '');
                            return letters.toUpperCase() || 'P';
                        },
                        scheduleSearch() {
                            if (this.chosen && this.query.trim() !== (this.chosen.name || '')) {
                                this.chosen = null;
                                this.patientId = '';
                            }
                            this.menuOpen = this.query.trim().length >= 2;
                            window.clearTimeout(this.timer);
                            this.timer = window.setTimeout(() => this.lookup(), 300);
                        },
                        lookup() {
                            const term = this.query.trim();
                            if (this.controller) {
                                this.controller.abort();
                            }
                            if (term.length < 2) {
                                this.searching = false;
                                this.searchError = '';
                                this.controller = null;
                                this.menuOpen = false;
                                this.searched = term.length === 1;
                                this.patients = [];
                                return;
                            }
                            this.menuOpen = true;
                            const controller = new AbortController();
                            this.controller = controller;
                            this.searching = true;
                            this.searchError = '';
                            this.searched = true;
                            const timer = window.setTimeout(() => controller.abort(), 8000);
                            fetch(this.searchUrl + '?q=' + encodeURIComponent(term), {
                                headers: { Accept: 'application/json' },
                                signal: controller.signal,
                            }).then((response) => {
                                if (!response.ok) {
                                    throw new Error('search failed');
                                }
                                return response.json();
                            }).then((payload) => {
                                if (this.controller !== controller) {
                                    return;
                                }
                                this.patients = payload.patients || [];
                            }).catch((error) => {
                                if (this.controller !== controller) {
                                    return;
                                }
                                this.patients = [];
                                this.searchError = error.name === 'AbortError'
                                    ? 'Search is taking too long. Try again.'
                                    : 'Search failed. Try again.';
                            }).finally(() => {
                                window.clearTimeout(timer);
                                if (this.controller === controller) {
                                    this.searching = false;
                                    this.controller = null;
                                }
                            });
                        },
                        groups() {
                            return this.days[this.date] || [];
                        },
                        exactSlots() {
                            return this.groups().flatMap((group) => group.slots.map((item) => ({
                                value: item.value,
                                period: group.period,
                                range: item.label + ' – ' + item.endLabel,
                                label: item.label,
                                endLabel: item.endLabel,
                            })));
                        },
                        selectDate(iso) {
                            if (this.date !== iso) {
                                this.slot = '';
                            }
                            this.date = iso;
                        },
                        prettyDate(iso) {
                            const [year, month, day] = String(iso).split('-').map(Number);
                            return new Date(year, month - 1, day).toLocaleDateString(undefined, {
                                weekday: 'long',
                                day: 'numeric',
                                month: 'short',
                                year: 'numeric',
                            });
                        },
                        monthTitle() {
                            const [year, month] = String(this.cursor).split('-').map(Number);
                            if (!year || !month) {
                                return '';
                            }
                            return new Date(year, month - 1, 1).toLocaleDateString(undefined, {
                                month: 'long',
                                year: 'numeric',
                            });
                        },
                        calendarCells() {
                            const [year, month] = String(this.cursor).split('-').map(Number);
                            if (!year || !month) {
                                return [];
                            }
                            const first = new Date(year, month - 1, 1);
                            const count = new Date(year, month, 0).getDate();
                            const cells = Array.from({ length: first.getDay() }, () => null);
                            for (let day = 1; day <= count; day += 1) {
                                const iso = year + '-' + String(month).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                                const groups = this.days[iso] || [];
                                cells.push({
                                    date: iso,
                                    label: day,
                                    open: iso >= this.minDate && iso <= this.maxDate && groups.length > 0,
                                });
                            }
                            return cells;
                        },
                        canShift(delta) {
                            const [year, month] = String(this.cursor).split('-').map(Number);
                            if (!year || !month) {
                                return false;
                            }
                            const next = new Date(year, month - 1 + delta, 1);
                            const key = next.getFullYear() + '-' + String(next.getMonth() + 1).padStart(2, '0');
                            const min = String(this.minDate).slice(0, 7);
                            const max = String(this.maxDate).slice(0, 7);
                            return key >= min && key <= max;
                        },
                        shiftMonth(delta) {
                            if (!this.canShift(delta)) {
                                return;
                            }
                            const [year, month] = String(this.cursor).split('-').map(Number);
                            const next = new Date(year, month - 1 + delta, 1);
                            this.cursor = next.getFullYear() + '-' + String(next.getMonth() + 1).padStart(2, '0');
                        },
                        selectedVisit() {
                            for (const group of this.groups()) {
                                const match = group.slots.find((item) => item.value === this.slot);
                                if (match) {
                                    return match;
                                }
                            }
                            return null;
                        },
                        askToBook(button) {
                            const visit = this.summary();

                            window.appConfirm({
                                title: 'Confirm this appointment?',
                                message: 'The visit will be saved on this doctor’s schedule.',
                                detail: visit,
                                confirmLabel: 'Confirm booking',
                                cancelLabel: 'Go back',
                            }).then((accepted) => {
                                if (accepted && button.form) {
                                    button.form.requestSubmit(button);
                                }
                            });
                        },
                        canConfirm() {
                            if (this.date === '' || this.slot === '') {
                                return false;
                            }
                            if (this.patientMode === 'new') {
                                return this.newName.trim() !== '' && this.newEmail.trim() !== '' && this.newPhone.trim() !== '' && String(this.newAge).trim() !== '';
                            }
                            return this.patientId !== '';
                        },
                        summary() {
                            const visit = this.selectedVisit();
                            if (!this.date || !visit) {
                                return '';
                            }
                            const who = this.patientMode === 'new'
                                ? (this.newName.trim() !== '' ? this.newName.trim() + ' · ' : '')
                                : (this.chosen ? this.chosen.name + ' · ' : '');
                            return who + this.prettyDate(this.date) + ' · ' + visit.label + ' – ' + visit.endLabel;
                        },
                    };
                };
            </script>
        @endpush
    @endif
@endsection
