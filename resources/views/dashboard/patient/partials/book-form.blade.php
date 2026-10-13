@php
    $choices = $choices ?? [];
    $selectedDoctorId = (int) ($selectedDoctorId ?? 0);
    $known = collect($choices)->contains(fn ($choice) => (int) $choice['id'] === $selectedDoctorId);
    if (! $known) {
        $selectedDoctorId = (int) ($choices[0]['id'] ?? 0);
    }
    $action = $selectedDoctorId ? route('doctors.book.store', $selectedDoctorId) : '#';
    $minDate = now()->toDateString();
    $maxDate = now()->addDays(\App\Services\AppointmentBooking::DAYS - 1)->toDateString();
@endphp

<section id="book-a-doctor" class="dash-panel">
    <div class="border-b border-line px-6 py-4">
        <h2 class="text-lg font-bold text-navy">Book a doctor</h2>
        <p class="mt-1 text-sm leading-6 text-text">Choose a specialist, an open time, and what you want seen. Payment confirms the visit.</p>
    </div>

    @if ($choices === [])
        <p class="px-6 py-8 text-sm leading-6 text-text">No approved doctors are open for booking yet.</p>
    @else
        <form
            data-dashboard-book
            data-store="{{ url('/doctors/__DOCTOR__/book') }}"
            method="POST"
            action="{{ $action }}"
            class="space-y-5 p-6"
        >
            @csrf

            @if ($errors->any())
                <p class="rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-sm leading-6 text-red-700">{{ $errors->first() }}</p>
            @endif

            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="book-doctor" class="label">Doctor</label>
                    <select id="book-doctor" name="doctor_id" data-book-doctor required class="field">
                        @foreach ($choices as $choice)
                            <option value="{{ $choice['id'] }}" @selected($selectedDoctorId === (int) $choice['id'])>
                                {{ $choice['name'] }}@if ($choice['specialization']) — {{ $choice['specialization'] }}@endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="book-date" class="label">Date</label>
                    <input id="book-date" data-book-date type="date" name="appointment_date" value="{{ old('appointment_date') }}" min="{{ $minDate }}" max="{{ $maxDate }}" required class="field">
                </div>
                <div>
                    <label for="book-slot" class="label">Time</label>
                    <select id="book-slot" data-book-slot required class="field">
                        <option value="">Select a time</option>
                    </select>
                    <p data-book-note class="mt-2 text-sm text-text">Choose a doctor and a date.</p>
                </div>
                <div class="sm:col-span-2">
                    <label for="book-issues" class="label">Health issues</label>
                    <textarea id="book-issues" name="health_issues" rows="3" placeholder="Describe what you want the doctor to know" class="field">{{ old('health_issues') }}</textarea>
                </div>
            </div>

            <input type="hidden" name="slot_day" data-slot-day value="{{ old('slot_day') }}">
            <input type="hidden" name="starting_time" data-starting-time value="{{ old('starting_time') }}">
            <input type="hidden" name="ending_time" data-ending-time value="{{ old('ending_time') }}">

            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm font-semibold text-navy">Fee <span data-book-fee></span></p>
                <button type="submit" class="btn-primary">Confirm appointment</button>
            </div>
        </form>
    @endif
</section>

@once
    @push('scripts')
        <script type="application/json" id="dashboard-booking-choices">@json($choices)</script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-dashboard-book]');
                const payload = document.getElementById('dashboard-booking-choices');
                if (!form || !payload) return;

                let choices = [];
                try {
                    choices = JSON.parse(payload.textContent || '[]');
                } catch (error) {
                    choices = [];
                }

                const doctorSelect = form.querySelector('[data-book-doctor]');
                const dateInput = form.querySelector('[data-book-date]');
                const slotSelect = form.querySelector('[data-book-slot]');
                const dayInput = form.querySelector('[data-slot-day]');
                const startInput = form.querySelector('[data-starting-time]');
                const endInput = form.querySelector('[data-ending-time]');
                const note = form.querySelector('[data-book-note]');
                const fee = form.querySelector('[data-book-fee]');

                const formatTime = (time) => {
                    const [hours, minutes] = String(time).split(':').map(Number);
                    const meridian = hours >= 12 ? 'PM' : 'AM';
                    const hour = hours % 12 || 12;
                    return `${hour}:${String(minutes).padStart(2, '0')} ${meridian}`;
                };

                const currentDoctor = () => choices.find((item) => String(item.id) === doctorSelect.value);

                const applySlot = () => {
                    const [day, start, end] = String(slotSelect.value || '').split('|');
                    dayInput.value = day || '';
                    startInput.value = start || '';
                    endInput.value = end || '';
                };

                const render = () => {
                    if (doctorSelect.value) {
                        form.action = form.dataset.store.replace('__DOCTOR__', doctorSelect.value);
                    }

                    const doctor = currentDoctor();
                    fee.textContent = doctor ? `${doctor.fee} USD` : '';
                    slotSelect.innerHTML = '<option value="">Select a time</option>';

                    if (!doctor || !dateInput.value) {
                        note.textContent = 'Choose a doctor and a date.';
                        return;
                    }

                    const picked = new Date(`${dateInput.value}T00:00:00`);
                    const weekday = picked.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
                    const blocked = (doctor.blocked || {})[dateInput.value];

                    if (blocked === 'fully booked') {
                        note.textContent = 'All times are booked for this date.';
                        return;
                    }

                    const taken = Array.isArray(blocked) ? blocked : [];
                    const available = (doctor.slots || []).filter((slot) => {
                        return slot.day === weekday && !taken.includes(`${slot.startingTime}-${slot.endingTime}`);
                    });

                    if (available.length === 0) {
                        note.textContent = 'This doctor has no open time on that date.';
                        return;
                    }

                    note.textContent = '';
                    available.forEach((slot) => {
                        const option = document.createElement('option');
                        option.value = `${slot.day}|${slot.startingTime}|${slot.endingTime}`;
                        option.textContent = `${formatTime(slot.startingTime)} – ${formatTime(slot.endingTime)}`;
                        option.selected = startInput.value === slot.startingTime && endInput.value === slot.endingTime;
                        slotSelect.appendChild(option);
                    });

                    if (slotSelect.value) applySlot();
                };

                doctorSelect.addEventListener('change', () => {
                    startInput.value = '';
                    endInput.value = '';
                    dayInput.value = '';
                    render();
                });
                dateInput.addEventListener('change', render);
                slotSelect.addEventListener('change', applySlot);
                form.addEventListener('submit', (event) => {
                    applySlot();
                    if (!dateInput.value || !startInput.value || !endInput.value) {
                        event.preventDefault();
                        note.textContent = 'Choose a date and an open time.';
                    }
                });
                render();
            });
        </script>
    @endpush
@endonce
