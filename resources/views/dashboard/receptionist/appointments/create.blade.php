@extends('layouts.dashboard')

@php
    $inputClass = 'h-12 w-full rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 text-base text-[#0B1F3A] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';
    $selectedDate = is_string($selectedDate) ? $selectedDate : now()->toDateString();
    $times = $schedule[$selectedDoctorId][$selectedDate] ?? [];
    $labelFor = function (string $time) {
        return \Illuminate\Support\Carbon::parse($time)->format('g:i A');
    };
@endphp

@section('content')
    <div class="dash-banner">
        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Front desk</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight">Book a visit</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            Choose an approved doctor and an existing patient. The visit is confirmed when you save it.
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">{{ now()->format('l, j M Y') }}</span>
            <span class="dash-chip">Confirmed on save</span>
        </div>
    </div>

    @if ($errors->any())
        <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif

    <form method="GET" action="{{ route('receptionist.appointments.create') }}" class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
        <label for="q" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Find a patient</label>
        <div class="flex flex-wrap gap-3">
            <input id="q" type="search" name="q" value="{{ $search }}" placeholder="Name or email" class="{{ $inputClass }} max-w-md">
            <input type="hidden" name="doctor_id" value="{{ $selectedDoctorId }}">
            <input type="hidden" name="date" value="{{ $selectedDate }}">
            <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#1769FF] px-6 font-semibold text-white">Search</button>
        </div>
    </form>

    <form method="POST" action="{{ route('receptionist.appointments.store') }}" class="mt-6 space-y-6">
        @csrf

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <h2 class="text-xl font-bold text-[#0B1F3A]">Patient</h2>
            @if ($patients->isEmpty() && $searched)
                <p class="mt-4 text-sm leading-6 text-[#53647D]">No patients match that search.</p>
            @elseif ($patients->isEmpty())
                <p class="mt-4 text-sm leading-6 text-[#53647D]">Search for a patient by name or email.</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($patients as $patient)
                        <label class="flex items-center gap-3 rounded-xl bg-[#F4F7FB] px-4 py-3 text-[#0B1F3A]">
                            <input type="radio" name="patient_id" value="{{ $patient->id }}" @checked((string) $selectedPatientId === (string) $patient->id) required class="h-4 w-4 text-[#1769FF] focus:ring-[#1769FF]">
                            <span>
                                <span class="block font-semibold">{{ $patient->name }}</span>
                                <span class="text-sm text-[#53647D]">{{ $patient->email }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
            @error('patient_id')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </section>

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
            <h2 class="text-xl font-bold text-[#0B1F3A]">Visit</h2>

            @if ($doctors->isEmpty())
                <p class="mt-4 text-sm leading-6 text-[#53647D]">No approved doctors are available to book.</p>
            @endif

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="doctor_id" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Doctor</label>
                    <select id="doctor_id" name="doctor_id" required class="{{ $inputClass }}">
                        <option value="">Select a doctor</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected((string) $selectedDoctorId === (string) $doctor->id)>
                                {{ $doctor->user?->name }}@if ($doctor->specialization) — {{ $doctor->specialization }}@endif
                            </option>
                        @endforeach
                    </select>
                    @error('doctor_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="appointment-date" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Date</label>
                    <input id="appointment-date" type="date" name="date" value="{{ $selectedDate }}" min="{{ $minDate }}" max="{{ $maxDate }}" required class="{{ $inputClass }}">
                    @error('date')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="appointment-time" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Time</label>
                    <select id="appointment-time" name="time" required class="{{ $inputClass }}">
                        <option value="">Select a time</option>
                        @foreach ($times as $time)
                            <option value="{{ $time }}" @selected(old('time') === $time)>{{ $labelFor($time) }}</option>
                        @endforeach
                    </select>
                    <p id="no-times" class="mt-2 text-sm text-[#53647D] {{ count($times) ? 'hidden' : '' }}">No open times on this date.</p>
                    @error('time')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="visit_type" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Visit type</label>
                    <select id="visit_type" name="visit_type" required class="{{ $inputClass }}">
                        @foreach (['consultation' => 'Consultation', 'check-up' => 'Check-up', 'follow-up' => 'Follow-up', 'emergency' => 'Emergency'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('visit_type', 'consultation') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visit_type')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="reason" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Reason</label>
                    <input id="reason" type="text" name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Reason for the visit" class="{{ $inputClass }}">
                    @error('reason')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <button type="submit" class="mt-6 inline-flex h-12 items-center justify-center rounded-xl bg-[#1769FF] px-6 font-semibold text-white">Book appointment</button>
        </section>
    </form>
@endsection

@push('scripts')
    <script>
        const schedule = @json($schedule);
        const doctorSelect = document.getElementById('doctor_id');
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
            const doctorId = doctorSelect.value;
            const times = (schedule[doctorId] && schedule[doctorId][dateInput.value]) || [];
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

        doctorSelect?.addEventListener('change', renderTimes);
        dateInput?.addEventListener('change', renderTimes);
    </script>
@endpush
