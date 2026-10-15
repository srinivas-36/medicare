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

                <section class="dash-panel overflow-visible p-6 sm:p-8" aria-live="polite">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">Step 1</p>
                    <h2 class="mt-1 text-lg font-bold text-navy">Patient</h2>
                    <div class="mt-4 flex flex-wrap gap-2" role="tablist" aria-label="Patient type">
                        <button type="button" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold" :class="patientMode === 'existing' ? 'bg-[#0067FF] text-white' : 'bg-white text-body ring-1 ring-line'" @click="patientMode = 'existing'">Existing patient</button>
                        <button type="button" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-semibold" :class="patientMode === 'new' ? 'bg-[#0067FF] text-white' : 'bg-white text-body ring-1 ring-line'" @click="patientMode = 'new'">New patient</button>
                    </div>

                    @include('dashboard.partials.patient-roster')

                    <div x-show="patientMode === 'new'" x-cloak class="mt-5 grid gap-5 sm:grid-cols-2">
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
                    @include('dashboard.partials.visit-calendar')
                </section>

                <section class="dash-panel p-6 sm:p-8">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-primary">Step 3</p>
                    <h2 class="mt-1 text-lg font-bold text-navy">Visit details and confirm</h2>
                    <div class="mt-4 rounded-xl bg-page px-4 py-3 text-sm" x-show="summary() !== ''" x-cloak>
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
            @include('dashboard.partials.desk-booking-script')
        @endpush
    @endif
@endsection
