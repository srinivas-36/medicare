@extends('layouts.dashboard')

@php
    $inputClass = 'field';
    $genders = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
    $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    $selectedDate = is_string($selectedDate) ? $selectedDate : now()->toDateString();
    $times = $times ?? [];
    $labelFor = function (string $time) {
        return \Illuminate\Support\Carbon::parse($time)->format('g:i A');
    };
@endphp

@section('title', 'Add a patient')

@section('content')
    <x-ui.page-header title="Add a patient" description="Add a new patient or choose someone who already has an account, then book an open time. Your patient list records that you added them.">
        <a href="{{ route('doctor.patients') }}" class="btn-secondary">Back</a>
    </x-ui.page-header>

    @if ($errors->any())
        <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif

    <form
        method="POST"
        action="{{ route('doctor.patients.store') }}"
        class="mt-6 space-y-6"
        x-data="addPatient(@js([
            'mode' => old('mode', 'new'),
            'searchUrl' => route('doctor.patients.search'),
            'patientId' => (string) old('patient_id', $selectedPatient->id ?? ''),
            'chosen' => $selectedPatient ? ['id' => $selectedPatient->id, 'name' => $selectedPatient->name, 'email' => $selectedPatient->email, 'phone' => $selectedPatient->phone] : null,
        ]))"
    >
        @csrf
        <input type="hidden" name="mode" :value="mode">
        <input type="hidden" name="patient_id" :value="mode === 'existing' ? patientId : ''" :disabled="mode !== 'existing'">

        <section class="dash-panel p-6 sm:p-8">
            <h2 class="text-lg font-bold text-navy">Patient</h2>
            <p class="mt-1 text-sm text-text">Create a new account, or search for a patient who already has one.</p>
            <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Patient type">
                <button type="button" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold" :class="mode === 'new' ? 'bg-[#0067FF] text-white' : 'bg-white text-body ring-1 ring-line'" @click="mode = 'new'" role="tab" :aria-selected="mode === 'new' ? 'true' : 'false'">New patient</button>
                <button type="button" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold" :class="mode === 'existing' ? 'bg-[#0067FF] text-white' : 'bg-white text-body ring-1 ring-line'" @click="mode = 'existing'" role="tab" :aria-selected="mode === 'existing' ? 'true' : 'false'">Existing patient</button>
            </div>

            <div x-show="mode === 'existing'" x-cloak class="relative mt-5 max-w-xl" @click.outside="menuOpen = false">
                <label for="existing-patient-search" class="label">Find a patient</label>
                <p class="mb-3 text-sm text-text">Search by name, email, or phone, then choose someone from the list.</p>
                <div class="relative">
                    <input
                        id="existing-patient-search"
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
                        aria-controls="doctor-patient-results"
                        class="{{ $inputClass }} pr-10"
                    >
                </div>
                <div
                    id="doctor-patient-results"
                    x-show="menuOpen"
                    x-cloak
                    role="listbox"
                    class="absolute z-20 mt-1 max-h-72 w-full overflow-auto rounded-xl border border-line bg-white py-1 shadow-lift"
                >
                    <p x-show="searchError !== ''" x-text="searchError" class="px-4 py-3 text-sm text-red-600"></p>
                    <p x-show="searchError === '' && searched && patients.length === 0" class="px-4 py-3 text-sm text-text">No patients match that search.</p>
                    <template x-for="patient in patients" :key="patient.id">
                        <button
                            type="button"
                            role="option"
                            @click="choose(patient)"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-light"
                            :class="String(patientId) === String(patient.id) ? 'bg-light' : ''"
                            :aria-selected="String(patientId) === String(patient.id) ? 'true' : 'false'"
                        >
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

            <div class="mt-5 grid gap-5 sm:grid-cols-2" x-show="mode === 'new'">
                <div>
                    <label for="patient-name" class="label">Full name</label>
                    <input id="patient-name" name="name" value="{{ old('name') }}" :required="mode === 'new'" :disabled="mode !== 'new'" class="{{ $inputClass }}" autocomplete="name">
                </div>
                <div>
                    <label for="patient-email" class="label">Email</label>
                    <input id="patient-email" type="email" name="email" value="{{ old('email') }}" :required="mode === 'new'" :disabled="mode !== 'new'" class="{{ $inputClass }}" autocomplete="off">
                    <p class="mt-2 text-xs leading-5 text-muted">If this email already belongs to a patient, that account is booked and no new password is needed.</p>
                </div>
                <div>
                    <label for="patient-phone" class="label">Phone</label>
                    <input id="patient-phone" name="phone" value="{{ old('phone') }}" :required="mode === 'new'" :disabled="mode !== 'new'" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="patient-age" class="label">Age</label>
                    <input id="patient-age" type="number" name="age" value="{{ old('age') }}" min="0" max="120" :required="mode === 'new'" :disabled="mode !== 'new'" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="patient-gender" class="label">Gender</label>
                    <select id="patient-gender" name="gender" :disabled="mode !== 'new'" class="{{ $inputClass }}">
                        <option value="">Not specified</option>
                        @foreach ($genders as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="patient-blood" class="label">Blood type</label>
                    <select id="patient-blood" name="blood_type" :disabled="mode !== 'new'" class="{{ $inputClass }}">
                        <option value="">Not specified</option>
                        @foreach ($bloodTypes as $type)
                            <option value="{{ $type }}" @selected(old('blood_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="patient-password" class="label">Sign-in password</label>
                    <input id="patient-password" type="password" name="password" :disabled="mode !== 'new'" class="{{ $inputClass }}" autocomplete="new-password">
                    <p class="mt-2 text-xs leading-5 text-muted">Required only when this email is new.</p>
                </div>
                <div>
                    <label for="patient-password-confirmation" class="label">Confirm password</label>
                    <input id="patient-password-confirmation" type="password" name="password_confirmation" :disabled="mode !== 'new'" class="{{ $inputClass }}" autocomplete="new-password">
                </div>
            </div>
        </section>

        <section class="dash-panel p-6 sm:p-8">
            <h2 class="text-lg font-bold text-navy">Visit</h2>
            <p class="mt-1 text-sm text-text">An open time from your weekly hours.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="appointment-date" class="label">Date</label>
                    <input id="appointment-date" type="date" name="date" value="{{ old('date', $selectedDate) }}" min="{{ $minDate }}" max="{{ $maxDate }}" required class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="appointment-time" class="label">Time</label>
                    <select id="appointment-time" name="time" required class="{{ $inputClass }}">
                        <option value="">Select a time</option>
                        @foreach ($times as $time)
                            <option value="{{ $time['start'] }}" @selected(old('time') === $time['start'])>{{ $labelFor($time['start']) }} – {{ $labelFor($time['end']) }}</option>
                        @endforeach
                    </select>
                    <p id="no-times" class="mt-2 text-sm text-text {{ count($times) ? 'hidden' : '' }}">No open times on this date.</p>
                </div>
                <div>
                    <label for="visit_type" class="label">Visit type</label>
                    <select id="visit_type" name="visit_type" required class="{{ $inputClass }}">
                        @foreach (['consultation' => 'Consultation', 'check-up' => 'Check-up', 'follow-up' => 'Follow-up', 'emergency' => 'Emergency'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('visit_type', 'consultation') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="reason" class="label">Reason</label>
                    <input id="reason" type="text" name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Reason for the visit" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary">Book appointment</button>
                <a href="{{ route('doctor.patients') }}" class="btn-ghost">Cancel</a>
            </div>
        </section>
    </form>
@endsection

@push('scripts')
    <script>
        const schedule = @json($schedule);
        const dateInput = document.getElementById('appointment-date');
        const timeSelect = document.getElementById('appointment-time');
        const emptyNote = document.getElementById('no-times');

        const labelFor = (time) => {
            const [hourText, minute] = time.split(':');
            const hour = Number(hourText);
            const suffix = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;

            return `${hour12}:${minute} ${suffix}`;
        };

        const renderTimes = () => {
            const times = schedule[dateInput.value] || [];
            const current = timeSelect.value;

            timeSelect.innerHTML = '<option value="">Select a time</option>';

            times.forEach((slot) => {
                const option = document.createElement('option');
                option.value = slot.start;
                option.textContent = labelFor(slot.start) + ' – ' + labelFor(slot.end);
                option.selected = slot.start === current;
                timeSelect.appendChild(option);
            });

            emptyNote.classList.toggle('hidden', times.length > 0);
        };

        dateInput?.addEventListener('change', renderTimes);

        window.addPatient = function (config) {
            return {
                mode: config.mode || 'new',
                searchUrl: config.searchUrl || '',
                patients: [],
                patientId: String(config.patientId || ''),
                chosen: config.chosen || null,
                query: config.chosen ? (config.chosen.name || '') : '',
                searchError: '',
                searched: false,
                menuOpen: false,
                timer: null,
                controller: null,
                choose(patient) {
                    this.patientId = String(patient.id);
                    this.chosen = patient;
                    this.query = patient.name || '';
                    this.menuOpen = false;
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
                        this.searchError = term.length === 1 ? 'Type at least 2 characters.' : '';
                        this.searched = term.length === 1;
                        this.menuOpen = false;
                        this.patients = [];
                        return;
                    }
                    const controller = new AbortController();
                    this.controller = controller;
                    this.menuOpen = true;
                    this.searched = true;
                    this.searchError = '';
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
                    }).catch(() => {
                        if (this.controller !== controller) {
                            return;
                        }
                        this.patients = [];
                        this.searchError = 'Search failed. Try again.';
                    }).finally(() => {
                        if (this.controller === controller) {
                            this.controller = null;
                        }
                    });
                },
            };
        };
    </script>
@endpush
