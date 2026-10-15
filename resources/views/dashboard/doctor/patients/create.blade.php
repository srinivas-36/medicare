@extends('layouts.dashboard')

@section('title', 'Add a patient & Book Appointment')

@section('content')
    <div x-data="deskBooking()">
        <h1 class="text-[22px] font-bold tracking-tight text-[#181A1E]">Add a patient & Book Appointment</h1>
        <p class="mt-1 max-w-3xl text-sm leading-5 text-[#64748B]">Search for an existing patient or create a new one, then choose a date, time and visit details to complete the booking.</p>

        <ol class="mt-5 flex flex-wrap items-center gap-y-3" aria-label="Booking steps">
            @foreach ([1 => 'Patient', 2 => 'Date & time', 3 => 'Visit details', 4 => 'Confirm'] as $number => $label)
                <li class="flex items-center gap-2">
                    <span
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold"
                        :class="wizardStep() >= {{ $number }} ? 'bg-[#0067FF] text-white' : 'bg-[#EEF2F6] text-[#94A3B8]'"
                    >
                        <svg x-show="wizardStep() > {{ $number }}" x-cloak class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                            <path d="m6 12 4 4 8-8" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span x-show="wizardStep() <= {{ $number }}">{{ $number }}</span>
                    </span>
                    <span class="text-sm font-semibold" :class="wizardStep() >= {{ $number }} ? 'text-[#0067FF]' : 'text-[#94A3B8]'">{{ $label }}</span>
                </li>
                @if ($number < 4)
                    <li class="mx-3 hidden h-px min-w-8 flex-1 sm:block" :class="wizardStep() > {{ $number }} ? 'bg-[#0067FF]' : 'bg-[#E2E8F0]'" aria-hidden="true"></li>
                @endif
            @endforeach
        </ol>

        @if ($errors->any())
            <p class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{{ $errors->first() }}</p>
        @endif

        <form method="POST" action="{{ route('doctor.patients.store') }}" class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="mode" :value="patientMode">
            <input type="hidden" name="patient_id" :value="patientMode === 'existing' ? patientId : ''" :disabled="patientMode !== 'existing'">
            <input type="hidden" name="date" :value="date">
            <input type="hidden" name="time" :value="timeValue()">

            <section id="booking-step-1" class="rounded-2xl border border-[#E6EDF5] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex min-w-0 items-start gap-3">
                        <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EAF2FF] text-[#0067FF]">
                            <x-ui.icon name="user" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-xs font-medium text-[#94A3B8]">Step 1</p>
                            <h2 class="text-base font-bold text-[#181A1E]">Select or Add Patient</h2>
                            <p class="mt-0.5 text-sm text-[#64748B]">Search for an existing patient, or create a new one if they are not in the system.</p>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2" role="tablist" aria-label="Patient type">
                        <button type="button" class="inline-flex h-9 items-center rounded-lg px-3.5 text-sm font-semibold" :class="patientMode === 'existing' ? 'bg-[#0067FF] text-white' : 'bg-white text-[#4E545F] ring-1 ring-[#E2E8F0]'" @click="patientMode = 'existing'" role="tab" :aria-selected="patientMode === 'existing' ? 'true' : 'false'">Existing patient</button>
                        <button type="button" class="inline-flex h-9 items-center rounded-lg px-3.5 text-sm font-semibold" :class="patientMode === 'new' ? 'bg-[#0067FF] text-white' : 'bg-white text-[#4E545F] ring-1 ring-[#E2E8F0]'" @click="patientMode = 'new'" role="tab" :aria-selected="patientMode === 'new' ? 'true' : 'false'">New patient</button>
                    </div>
                </div>

                <div x-show="patientMode === 'existing'" x-cloak class="mt-5">
                    <div class="relative">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                            <x-ui.icon name="search" class="h-4 w-4" />
                        </span>
                        <input
                            id="patient-roster-search"
                            type="search"
                            x-model="query"
                            @input="scheduleSearch()"
                            @keydown.enter.prevent="lookup()"
                            @keydown.escape="query = ''; scheduleSearch()"
                            placeholder="Search by name, email, or phone number..."
                            autocomplete="off"
                            role="combobox"
                            aria-controls="patient-roster"
                            aria-autocomplete="list"
                            aria-expanded="true"
                            class="h-11 w-full rounded-xl border border-[#E2E8F0] bg-white pl-10 pr-10 text-sm text-[#181A1E] outline-none placeholder:text-[#94A3B8] focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15"
                        >
                        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-[#0067FF]" x-show="searching" role="status" aria-label="Searching patients">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"></circle>
                                <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                            </svg>
                        </span>
                    </div>

                    <div id="patient-roster" class="mt-4 overflow-hidden rounded-xl border border-[#E6EDF5]">
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[760px] text-left">
                                <thead class="bg-[#F8FAFC] text-[11px] font-semibold uppercase tracking-[0.08em] text-[#94A3B8]">
                                    <tr>
                                        <th class="px-4 py-3 font-semibold">Name</th>
                                        <th class="px-4 py-3 font-semibold">Contact</th>
                                        <th class="px-4 py-3 font-semibold">Age</th>
                                        <th class="px-4 py-3 font-semibold">Gender</th>
                                        <th class="px-4 py-3 font-semibold">Blood type</th>
                                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr x-show="searching">
                                        <td colspan="6" class="px-4 py-6 text-sm text-[#64748B]">Searching patients…</td>
                                    </tr>
                                    <tr x-show="!searching && searchError !== ''">
                                        <td colspan="6" class="px-4 py-6 text-sm text-red-600" x-text="searchError"></td>
                                    </tr>
                                    <tr x-show="!searching && searchError === '' && visiblePatients().length === 0">
                                        <td colspan="6" class="px-4 py-10 text-center text-sm text-[#64748B]" x-text="emptyRoster()"></td>
                                    </tr>
                                    <template x-for="(patient, index) in visiblePatients()" :key="patient.id">
                                        <tr class="border-t border-[#E6EDF5]" :class="String(patientId) === String(patient.id) ? 'bg-[#F4F8FF]' : 'hover:bg-[#F8FAFC]'">
                                            <td class="px-4 py-3">
                                                <span class="flex items-center gap-3">
                                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-semibold" :style="avatarStyle(index)" x-text="initials(patient.name)"></span>
                                                    <span class="truncate text-sm font-semibold text-[#181A1E]" x-text="patient.name"></span>
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="block truncate text-sm text-[#3A4150]" x-text="patient.email || '—'"></span>
                                                <span class="mt-0.5 block truncate text-xs text-[#94A3B8]" x-show="patient.phone" x-text="patient.phone"></span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-[#3A4150]" x-text="blank(patient.age)"></td>
                                            <td class="px-4 py-3 text-sm text-[#3A4150]" x-text="genderLabel(patient.gender)"></td>
                                            <td class="px-4 py-3 text-sm text-[#3A4150]" x-text="blank(patient.blood_type)"></td>
                                            <td class="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    @click="choose(patient)"
                                                    class="inline-flex h-8 items-center rounded-lg px-3 text-sm font-semibold"
                                                    :class="String(patientId) === String(patient.id) ? 'bg-[#0067FF] text-white' : 'border border-[#0067FF] bg-white text-[#0067FF] hover:bg-[#F4F8FF]'"
                                                    :aria-pressed="String(patientId) === String(patient.id) ? 'true' : 'false'"
                                                    x-text="String(patientId) === String(patient.id) ? 'Selected' : 'Select'"
                                                ></button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div x-show="patientMode === 'new'" x-cloak class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="patient-name" class="mb-2 block text-sm font-medium text-[#334155]">Full name <span class="text-[#E11D48]">*</span></label>
                        <input id="patient-name" name="name" x-model="newName" :required="patientMode === 'new'" :disabled="patientMode !== 'new'" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15" autocomplete="name">
                    </div>
                    <div>
                        <label for="patient-email" class="mb-2 block text-sm font-medium text-[#334155]">Email <span class="text-[#E11D48]">*</span></label>
                        <input id="patient-email" type="email" name="email" x-model="newEmail" :required="patientMode === 'new'" :disabled="patientMode !== 'new'" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15" autocomplete="off">
                        <p class="mt-1.5 text-xs leading-5 text-[#94A3B8]">If this email already belongs to a patient, that account is booked and no new password is needed.</p>
                    </div>
                    <div>
                        <label for="patient-phone" class="mb-2 block text-sm font-medium text-[#334155]">Phone <span class="text-[#E11D48]">*</span></label>
                        <input id="patient-phone" name="phone" x-model="newPhone" :required="patientMode === 'new'" :disabled="patientMode !== 'new'" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15">
                    </div>
                    <div>
                        <label for="patient-age" class="mb-2 block text-sm font-medium text-[#334155]">Age <span class="text-[#E11D48]">*</span></label>
                        <input id="patient-age" type="text" name="age" inputmode="numeric" maxlength="2" pattern="[0-9]{2}" title="Enter a 2-digit age using numbers only." x-model="newAge" @input="newAge = String(newAge).replace(/\D/g, '').slice(0, 2)" :required="patientMode === 'new'" :disabled="patientMode !== 'new'" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15" autocomplete="off">
                        <p class="mt-1.5 text-xs leading-5 text-[#94A3B8]">Enter a 2-digit age using numbers only.</p>
                    </div>
                    <div>
                        <label for="patient-gender" class="mb-2 block text-sm font-medium text-[#334155]">Gender</label>
                        <select id="patient-gender" name="gender" x-model="newGender" :disabled="patientMode !== 'new'" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15">
                            <option value="">Not specified</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="patient-blood" class="mb-2 block text-sm font-medium text-[#334155]">Blood type</label>
                        <select id="patient-blood" name="blood_type" x-model="newBlood" :disabled="patientMode !== 'new'" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15">
                            <option value="">Not specified</option>
                            @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="patient-password" class="mb-2 block text-sm font-medium text-[#334155]">Sign-in password</label>
                        <input id="patient-password" type="password" name="password" x-model="newPassword" :disabled="patientMode !== 'new'" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15" autocomplete="new-password">
                        <p class="mt-1.5 text-xs leading-5 text-[#94A3B8]">Required only when this email is new.</p>
                    </div>
                    <div>
                        <label for="patient-password-confirmation" class="mb-2 block text-sm font-medium text-[#334155]">Confirm password</label>
                        <input id="patient-password-confirmation" type="password" name="password_confirmation" x-model="newPasswordConfirmation" :disabled="patientMode !== 'new'" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15" autocomplete="new-password">
                    </div>
                </div>
            </section>

            <section id="booking-step-2" class="rounded-2xl border border-[#E6EDF5] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EAF2FF] text-[#0067FF]">
                        <x-ui.icon name="calendar" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-[#94A3B8]">Step 2</p>
                        <h2 class="text-base font-bold text-[#181A1E]">Choose Date & Time</h2>
                        <p class="mt-0.5 text-sm text-[#64748B]">Select a date and an available time slot from the doctor's schedule.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-[300px_minmax(0,1fr)]">
                    <div class="rounded-xl border border-[#E6EDF5] bg-white p-3">
                        <div class="flex items-center justify-between px-1">
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#64748B] hover:bg-[#F8FAFC] disabled:text-[#D5DEEA]" @click="shiftMonth(-1)" :disabled="!canShift(-1)" aria-label="Previous month">
                                <x-ui.icon name="chevron" class="h-4 w-4 rotate-90" />
                            </button>
                            <p class="text-sm font-semibold text-[#181A1E]" x-text="monthTitle()"></p>
                            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-[#64748B] hover:bg-[#F8FAFC] disabled:text-[#D5DEEA]" @click="shiftMonth(1)" :disabled="!canShift(1)" aria-label="Next month">
                                <x-ui.icon name="chevron" class="h-4 w-4 -rotate-90" />
                            </button>
                        </div>
                        <div class="mt-3 grid grid-cols-7 text-center text-[11px] font-medium text-[#94A3B8]">
                            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>
                        <div class="mt-1 grid grid-cols-7">
                            <template x-for="(day, index) in calendarCells()" :key="day ? day.date : 'pad-' + index">
                                <div class="flex flex-col items-center py-0.5">
                                    <button
                                        type="button"
                                        class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-medium disabled:cursor-default"
                                        :disabled="!day || !day.open"
                                        @click="day && selectDate(day.date)"
                                        :class="!day ? 'invisible' : (date === day.date ? 'bg-[#0067FF] font-semibold text-white' : (day.open ? 'text-[#181A1E] hover:bg-[#F4F8FF]' : 'text-[#C5CED9]'))"
                                        x-text="day ? day.label : ''"
                                    ></button>
                                    <span class="mt-0.5 h-1 w-1 rounded-full" :class="day && day.open && date !== day.date ? 'bg-[#0067FF]' : 'bg-transparent'"></span>
                                </div>
                            </template>
                        </div>
                        <div class="mt-2 flex items-center gap-4 px-1 text-[11px] text-[#64748B]">
                            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-[#0067FF]"></span> Available</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-[#D5DEEA]"></span> Unavailable</span>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-[#181A1E]">Available time slots</p>
                            <p class="text-sm text-[#64748B]" x-text="date === '' ? '' : slotDateLabel(date)"></p>
                        </div>
                        <p x-show="date === '' || periodBlocks().length === 0" class="flex min-h-[180px] items-center justify-center rounded-xl border border-dashed border-[#E2E8F0] bg-[#F8FAFC] px-4 text-center text-sm text-[#64748B]">
                            No saved time slots are open on this date. Choose an available day.
                        </p>
                        <div x-show="periodBlocks().length > 0" class="grid grid-cols-1 items-start gap-3 lg:grid-cols-3">
                            <template x-for="block in periodBlocks()" :key="block.period">
                                <div class="min-w-0">
                                    <p class="mb-2 flex items-center gap-2 text-sm font-semibold text-[#181A1E]">
                                        <svg x-show="block.period === 'Morning' || block.period === 'Afternoon'" class="h-4 w-4 text-[#F5A524]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <circle cx="12" cy="12" r="4" />
                                            <path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4" stroke-linecap="round" />
                                        </svg>
                                        <svg x-show="block.period === 'Evening'" x-cloak class="h-4 w-4 text-[#64748B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M20 15.5A8 8 0 0 1 9.5 4 7 7 0 1 0 20 15.5Z" stroke-linejoin="round" />
                                        </svg>
                                        <span x-text="block.period"></span>
                                    </p>
                                    <div class="space-y-2">
                                        <template x-for="group in block.rows" :key="group.key">
                                            <div>
                                                <button
                                                    type="button"
                                                    @click="toggleGroup(group)"
                                                    class="flex w-full items-start gap-2 rounded-xl border px-3 py-3 text-left transition"
                                                    :class="groupChosen(group) ? 'border-[#0067FF] bg-[#F3F8FF]' : 'border-[#E6EDF5] bg-white hover:border-[#C9D7EA]'"
                                                    :aria-pressed="groupChosen(group) ? 'true' : 'false'"
                                                >
                                                    <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#0067FF] text-white" x-show="groupChosen(group)" x-cloak>
                                                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true">
                                                            <path d="m6 12 4 4 8-8" stroke-linecap="round" stroke-linejoin="round" />
                                                        </svg>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-sm font-medium text-[#181A1E]" x-text="groupRange(group)"></span>
                                                        <span class="mt-1 block text-xs text-[#64748B]" x-text="slotCountLabel(group)"></span>
                                                    </span>
                                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-[#94A3B8] transition" :class="openGroups[group.key] ? 'rotate-90' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                                        <path d="m9 6 6 6-6 6" stroke-linecap="round" stroke-linejoin="round" />
                                                    </svg>
                                                </button>
                                                <div x-show="openGroups[group.key] && (group.slots || []).length > 1" x-cloak class="mt-2 grid gap-2 sm:grid-cols-2">
                                                    <template x-for="item in group.slots" :key="item.value">
                                                        <button
                                                            type="button"
                                                            @click="slot = item.value"
                                                            class="rounded-lg border px-3 py-2 text-left text-sm font-medium"
                                                            :class="slot === item.value ? 'border-[#0067FF] bg-[#0067FF] text-white' : 'border-[#E2E8F0] bg-white text-[#181A1E] hover:border-[#0067FF]'"
                                                            x-text="item.label + ' – ' + item.endLabel"
                                                        ></button>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </section>

            <section id="booking-step-3" class="rounded-2xl border border-[#E6EDF5] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EAF2FF] text-[#0067FF]">
                        <x-ui.icon name="clipboard" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-[#94A3B8]">Step 3</p>
                        <h2 class="text-base font-bold text-[#181A1E]">Visit Details</h2>
                        <p class="mt-0.5 text-sm text-[#64748B]">Provide appointment type and reason for the visit.</p>
                    </div>
                </div>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="visit_type" class="mb-2 block text-sm font-medium text-[#334155]">Visit type <span class="text-[#E11D48]">*</span></label>
                        <select id="visit_type" name="visit_type" required class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15">
                            @foreach (['consultation' => 'Consultation', 'check-up' => 'Check-up', 'follow-up' => 'Follow-up', 'emergency' => 'Emergency'] as $value => $label)
                                <option value="{{ $value }}" @selected(old('visit_type', 'consultation') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="reason" class="mb-2 block text-sm font-medium text-[#334155]">Reason for visit</label>
                        <input id="reason" type="text" name="reason" value="{{ old('reason') }}" maxlength="255" placeholder="Enter reason for the visit (optional)" class="h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] outline-none placeholder:text-[#94A3B8] focus:border-[#0067FF] focus:ring-4 focus:ring-[#0067FF]/15">
                    </div>
                </div>
            </section>

            <section id="booking-step-4" class="rounded-2xl border border-[#E6EDF5] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] sm:p-6">
                <div class="flex items-start gap-3">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#EAF2FF] text-[#0067FF]">
                        <x-ui.icon name="check" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="text-xs font-medium text-[#94A3B8]">Step 4</p>
                        <h2 class="text-base font-bold text-[#181A1E]">Confirm</h2>
                        <p class="mt-0.5 text-sm text-[#64748B]">Review the patient, time, and visit, then save the appointment.</p>
                    </div>
                </div>
                <div class="mt-5 rounded-xl border border-[#E6EDF5] bg-[#F8FAFC] px-4 py-3 text-sm" x-show="summary() !== ''" x-cloak>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.08em] text-[#94A3B8]">Selected visit</p>
                    <p class="mt-1 font-semibold text-[#181A1E]" x-text="summary()"></p>
                </div>
                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn-primary" :disabled="!canConfirm()" @click.prevent="askToBook($event.currentTarget)">Confirm booking</button>
                    <a href="{{ route('doctor.patients') }}" class="btn-ghost">Cancel</a>
                </div>
            </section>
        </form>
    </div>

    <script type="application/json" id="desk-booking-data">@json($desk)</script>
@endsection

@push('scripts')
    @include('dashboard.partials.desk-booking-script')
@endpush
