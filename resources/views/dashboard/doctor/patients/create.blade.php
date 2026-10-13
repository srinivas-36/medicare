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
    <section class="dash-banner">
        <a href="{{ route('doctor.patients') }}" class="text-sm font-semibold text-white/75 hover:text-white">Back to patients</a>
        <p class="mt-4 text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Book a visit</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">Add a patient</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            Enter the patient and an open time on your schedule. Saving creates the visit and puts them on your patient list. If the email already belongs to a patient, that account is booked without a new password.
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
            <span class="dash-chip">Confirmed when you save</span>
        </div>
    </section>

    @if ($errors->any())
        <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('doctor.patients.store') }}" class="mt-6 space-y-6">
        @csrf

        <section class="dash-panel p-6 sm:p-8">
            <h2 class="text-lg font-bold text-navy">Patient</h2>
            <p class="mt-1 text-sm text-text">The person this visit is for.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="patient-name" class="label">Full name</label>
                    <input id="patient-name" name="name" value="{{ old('name') }}" required class="{{ $inputClass }}" autocomplete="name">
                </div>
                <div>
                    <label for="patient-email" class="label">Email</label>
                    <input id="patient-email" type="email" name="email" value="{{ old('email') }}" required class="{{ $inputClass }}" autocomplete="off">
                </div>
                <div>
                    <label for="patient-phone" class="label">Phone</label>
                    <input id="patient-phone" name="phone" value="{{ old('phone') }}" required class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="patient-age" class="label">Age</label>
                    <input id="patient-age" type="number" name="age" value="{{ old('age') }}" min="0" max="120" required class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="patient-gender" class="label">Gender</label>
                    <select id="patient-gender" name="gender" class="{{ $inputClass }}">
                        <option value="">Not specified</option>
                        @foreach ($genders as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="patient-blood" class="label">Blood type</label>
                    <select id="patient-blood" name="blood_type" class="{{ $inputClass }}">
                        <option value="">Not specified</option>
                        @foreach ($bloodTypes as $type)
                            <option value="{{ $type }}" @selected(old('blood_type') === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="patient-password" class="label">Sign-in password</label>
                    <input id="patient-password" type="password" name="password" class="{{ $inputClass }}" autocomplete="new-password">
                    <p class="mt-2 text-xs leading-5 text-muted">Required only when this email is new. Leave it blank for a patient who already has an account.</p>
                </div>
                <div>
                    <label for="patient-password-confirmation" class="label">Confirm password</label>
                    <input id="patient-password-confirmation" type="password" name="password_confirmation" class="{{ $inputClass }}" autocomplete="new-password">
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
                            <option value="{{ $time }}" @selected(old('time') === $time)>{{ $labelFor($time) }}</option>
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

            times.forEach((time) => {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = labelFor(time);
                option.selected = time === current;
                timeSelect.appendChild(option);
            });

            emptyNote.classList.toggle('hidden', times.length > 0);
        };

        dateInput?.addEventListener('change', renderTimes);
    </script>
@endpush
