@php
    $doctor = $doctor ?? [];
    $blocked = $blocked ?? [];
    $doctorId = data_get($doctor, 'id');
    $role = auth()->check() ? (auth()->user()->role ?? null) : null;
    $canBook = $role === 'patient';

    $storeUrl = $doctorId ? route('doctors.book.store', $doctorId) : route('login');
    $rememberUrl = $doctorId ? route('doctors.book.remember', $doctorId) : route('login');

    $photo = data_get($doctor, 'photo');
    $photoUrl = $photo && preg_match('/^https?:\/\//', (string) $photo)
        ? $photo
        : asset($photo ?: 'images/avatar-icon.png');
    $rating = number_format((float) data_get($doctor, 'averageRating', 0), 1);
    $fee = data_get($doctor, 'ticketPrice', 0);

    $slots = collect(data_get($doctor, 'timeSlots', []))
        ->filter(fn ($slot) => (bool) data_get($slot, 'isAvailable', true))
        ->map(fn ($slot) => [
            'day' => strtolower((string) data_get($slot, 'day')),
            'startingTime' => data_get($slot, 'startingTime'),
            'endingTime' => data_get($slot, 'endingTime'),
        ])
        ->values();

    $dayOrder = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    $allowedDays = $slots->pluck('day')->unique()->sortBy(fn ($day) => array_search($day, $dayOrder, true))->values();
    $indices = $allowedDays->map(fn ($day) => array_search($day, $dayOrder, true))->values();
    $consecutive = $indices->count() >= 2 && $indices->every(fn ($value, $index) => $index === 0 || $value === $indices[$index - 1] + 1);
    $labelDay = fn ($day) => ucfirst($day);

    $inputClass = 'h-11 w-full rounded-xl border border-line bg-white px-3 text-sm text-ink shadow-sm outline-none transition placeholder:text-muted focus:border-primary focus:ring-4 focus:ring-primary/15';
    $reportGroups = [
        ['key' => 'blood', 'name' => 'Blood Test Reports'],
        ['key' => 'xray', 'name' => 'X-Ray Scans'],
    ];
@endphp

@if (session('status'))
    <p class="mb-4 rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-4 py-2.5 text-sm leading-6 text-ink">{{ session('status') }}</p>
@endif

<div class="overflow-hidden rounded-2xl border border-line bg-white shadow-card">
    <div class="flex items-center gap-3 border-b border-line bg-[#f7faff] px-4 py-3 sm:px-5">
        <img src="{{ $photoUrl }}" alt="{{ data_get($doctor, 'name') }}" class="h-12 w-12 shrink-0 rounded-full object-cover">
        <div class="min-w-0 flex-1">
            <p class="truncate font-semibold text-navy">{{ data_get($doctor, 'name') }}</p>
            <p class="truncate text-sm text-body">
                {{ data_get($doctor, 'specialization') }}
                <span class="text-muted">·</span>
                {{ $rating }} · {{ data_get($doctor, 'totalRating', 0) }} reviews
            </p>
        </div>
        <p class="shrink-0 text-right text-sm font-bold text-navy">{{ $fee }} USD</p>
    </div>

    @if ($role === 'doctor')
        <div class="p-5">
            <p class="rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-4 py-3 text-sm leading-6 text-ink">
                Doctors can’t book appointments. Patients use this page to reserve a slot.
            </p>
        </div>
    @else
        <form
            data-booking-form
            data-intent="{{ $canBook ? 'book' : 'login' }}"
            data-no-lock="true"
            method="POST"
            action="{{ $canBook ? $storeUrl : $rememberUrl }}"
            enctype="multipart/form-data"
            class="space-y-5 p-4 sm:p-5"
        >
            @csrf

            @if ($errors->any())
                <div class="rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-sm leading-6 text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <p data-booking-error class="hidden rounded-xl border border-red-100 bg-red-50 px-3 py-2 text-sm leading-6 text-red-700"></p>

            @if ($allowedDays->isNotEmpty())
                <p class="text-sm italic leading-6 text-body">
                    You can book an appointment with this doctor
                    @if ($consecutive)
                        from <span class="font-semibold text-primary">{{ $labelDay($dayOrder[$indices->first()]) }} to {{ $labelDay($dayOrder[$indices->last()]) }}</span>.
                    @else
                        on <span class="font-semibold text-primary">{{ $allowedDays->map($labelDay)->join(', ') }}</span>.
                    @endif
                </p>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="appointment-date" class="label">Appointment date</label>
                    <input
                        id="appointment-date"
                        data-booking-date
                        type="date"
                        name="appointment_date"
                        value="{{ old('appointment_date') }}"
                        min="{{ date('Y-m-d') }}"
                        @if ($canBook) required @endif
                        class="{{ $inputClass }}"
                    >
                    <p data-selected-date class="mt-2 hidden text-sm text-body">
                        Selected: <span class="font-semibold text-primary"></span>
                    </p>
                    <p data-date-warning class="mt-2 hidden rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-3 py-2 text-sm leading-6 text-ink"></p>
                </div>
                <div>
                    <label for="health-issues" class="label">Health issues</label>
                    <textarea
                        id="health-issues"
                        name="health_issues"
                        rows="2"
                        placeholder="Describe your health issues in detail..."
                        class="{{ $inputClass }} h-auto min-h-11 py-2.5"
                    >{{ old('health_issues') }}</textarea>
                </div>
            </div>

            <div data-slot-panel class="hidden">
                <p class="mb-2 text-sm font-semibold text-navy">Select time slot</p>
                <div data-slot-grid class="grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                <div data-slot-empty class="hidden rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-3 py-2.5 text-sm leading-6 text-ink">
                    <span data-slot-message>All time slots are booked for this date. Please try another date.</span>
                </div>
            </div>

            <input type="hidden" name="slot_day" data-slot-day value="{{ old('slot_day') }}">
            <input type="hidden" name="starting_time" data-starting-time value="{{ old('starting_time') }}">
            <input type="hidden" name="ending_time" data-ending-time value="{{ old('ending_time') }}">

            <div>
                <p class="text-sm font-semibold text-navy">Upload medical reports</p>
                <div class="mt-2 grid gap-2 sm:grid-cols-3">
                    @foreach ($reportGroups as $group)
                        <div class="rounded-xl border border-line px-3 py-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <h3 class="text-sm font-semibold text-navy">{{ $group['name'] }}</h3>
                                <label class="shrink-0 cursor-pointer text-xs font-semibold text-primary hover:text-primary-hover">
                                    Add
                                    <input
                                        data-file-input="{{ $group['key'] }}"
                                        type="file"
                                        name="{{ $group['key'] }}_reports[]"
                                        accept="image/*,application/pdf"
                                        multiple
                                        class="sr-only"
                                    >
                                </label>
                            </div>
                            <div data-file-list="{{ $group['key'] }}" class="mt-2 grid grid-cols-2 gap-2 empty:hidden"></div>
                        </div>
                    @endforeach

                    <div class="rounded-xl border border-line px-3 py-2.5">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="text-sm font-semibold text-navy">Other reports</h3>
                            <label class="shrink-0 cursor-pointer text-xs font-semibold text-primary hover:text-primary-hover">
                                Add
                                <input
                                    data-file-input="other"
                                    type="file"
                                    name="other_reports[]"
                                    accept="image/*,application/pdf"
                                    multiple
                                    class="sr-only"
                                >
                            </label>
                        </div>
                        <label for="other-report-name" class="sr-only">Report group name</label>
                        <input
                            id="other-report-name"
                            data-other-name
                            type="text"
                            name="other_report_name"
                            value="{{ old('other_report_name') }}"
                            placeholder="e.g. MRI scans, ultrasound reports"
                            class="{{ $inputClass }} mt-2 h-9 px-2.5 text-xs"
                        >
                        <p data-other-count class="mt-1 hidden text-xs text-body"></p>
                        <div data-file-list="other" class="mt-2 grid grid-cols-2 gap-2 empty:hidden"></div>
                    </div>
                </div>
            </div>

            <div data-booking-summary class="hidden rounded-xl bg-[#e8f2ff] px-3 py-2.5 text-sm leading-6 text-ink">
                <div class="sm:flex sm:items-center sm:justify-between sm:gap-4">
                    <p><span class="font-semibold">Date:</span> <span data-summary-date></span></p>
                    <p><span class="font-semibold">Time:</span> <span data-summary-time></span></p>
                    <p><span class="font-semibold">Fee:</span> {{ $fee }} USD</p>
                </div>
            </div>

            @unless ($canBook)
                <p class="-mb-2 text-sm text-body">Log in as a patient to confirm this booking. Your date, time, and notes will be waiting after you sign in.</p>
            @endunless

            <button
                type="submit"
                data-submit
                class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-primary px-6 text-sm font-semibold text-white transition hover:bg-primary-hover disabled:cursor-not-allowed disabled:opacity-70"
            >
                {{ $canBook ? 'Confirm appointment' : 'Log in to book' }}
            </button>
        </form>
    @endif
</div>

@once
    @push('scripts')
        <script type="application/json" id="booking-slots">@json($slots)</script>
        <script type="application/json" id="booking-blocked">@json($blocked)</script>
        <script type="application/json" id="booking-days">@json($allowedDays)</script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const readJson = (id, fallback) => {
                    const node = document.getElementById(id);
                    if (!node) return fallback;
                    try {
                        return JSON.parse(node.textContent || '');
                    } catch (error) {
                        return fallback;
                    }
                };

                const slots = readJson('booking-slots', []);
                const blocked = readJson('booking-blocked', {});
                const allowedDays = readJson('booking-days', []);
                const form = document.querySelector('[data-booking-form]');
                if (!form) return;

                const loggingIn = form.dataset.intent === 'login';
                const dateInput = form.querySelector('[data-booking-date]');
                const slotPanel = form.querySelector('[data-slot-panel]');
                const slotGrid = form.querySelector('[data-slot-grid]');
                const slotEmpty = form.querySelector('[data-slot-empty]');
                const slotMessage = form.querySelector('[data-slot-message]');
                const selectedLabel = form.querySelector('[data-selected-date]');
                const dateWarning = form.querySelector('[data-date-warning]');
                const summary = form.querySelector('[data-booking-summary]');
                const summaryDate = form.querySelector('[data-summary-date]');
                const summaryTime = form.querySelector('[data-summary-time]');
                const errorBox = form.querySelector('[data-booking-error]');
                const dayInput = form.querySelector('[data-slot-day]');
                const startInput = form.querySelector('[data-starting-time]');
                const endInput = form.querySelector('[data-ending-time]');
                const otherName = form.querySelector('[data-other-name]');
                const otherCount = form.querySelector('[data-other-count]');
                const previous = {
                    start: startInput.value,
                    end: endInput.value,
                };
                let restored = false;
                const files = { blood: [], xray: [], other: [] };

                const formatTime = (time) => {
                    const [hours, minutes] = String(time).split(':').map(Number);
                    const meridian = hours >= 12 ? 'pm' : 'am';
                    const hour = hours % 12 || 12;
                    return `${hour}:${String(minutes).padStart(2, '0')} ${meridian}`;
                };

                const showError = (message) => {
                    errorBox.textContent = message;
                    errorBox.classList.remove('hidden');
                };

                const hideError = () => errorBox.classList.add('hidden');

                const clearSlot = () => {
                    dayInput.value = '';
                    startInput.value = '';
                    endInput.value = '';
                    summary.classList.add('hidden');
                };

                const selectSlot = (button, slot, pretty) => {
                    slotGrid.querySelectorAll('button').forEach((item) => {
                        item.classList.remove('border-[#0067FF]', 'bg-[#e8f2ff]', 'text-[#0067FF]');
                        item.classList.add('border-[#c9dcff]', 'text-[#181A1E]');
                    });
                    button.classList.add('border-[#0067FF]', 'bg-[#e8f2ff]', 'text-[#0067FF]');
                    button.classList.remove('border-[#c9dcff]', 'text-[#181A1E]');
                    dayInput.value = slot.day;
                    startInput.value = slot.startingTime;
                    endInput.value = slot.endingTime;
                    summary.classList.remove('hidden');
                    summaryDate.textContent = pretty;
                    summaryTime.textContent = button.textContent;
                    hideError();
                };

                const renderSlots = () => {
                    hideError();
                    clearSlot();
                    slotGrid.innerHTML = '';
                    selectedLabel.classList.add('hidden');
                    dateWarning.classList.add('hidden');
                    slotPanel.classList.add('hidden');
                    slotEmpty.classList.add('hidden');

                    if (!dateInput.value) return;

                    const picked = new Date(`${dateInput.value}T00:00:00`);
                    const weekday = picked.toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
                    const pretty = picked.toLocaleDateString('en-US', {
                        weekday: 'long',
                        month: 'long',
                        day: 'numeric',
                        year: 'numeric',
                    });

                    selectedLabel.classList.remove('hidden');
                    selectedLabel.querySelector('span').textContent = pretty;

                    if (!allowedDays.includes(weekday)) {
                        dateWarning.textContent = 'This doctor does not take appointments on that day. Choose one of the days listed above.';
                        dateWarning.classList.remove('hidden');
                        return;
                    }

                    const booked = blocked[dateInput.value];
                    slotPanel.classList.remove('hidden');

                    if (booked === 'fully booked') {
                        slotGrid.classList.add('hidden');
                        slotEmpty.classList.remove('hidden');
                        slotMessage.textContent = 'All time slots are booked for this date. Please try another date.';
                        return;
                    }

                    const taken = Array.isArray(booked) ? booked : [];
                    const available = slots.filter((slot) => {
                        return slot.day === weekday && !taken.includes(`${slot.startingTime}-${slot.endingTime}`);
                    });

                    if (available.length === 0) {
                        slotGrid.classList.add('hidden');
                        slotEmpty.classList.remove('hidden');
                        slotMessage.textContent = 'No open time slots for this date. Please try another date.';
                        return;
                    }

                    slotGrid.classList.remove('hidden');

                    available.forEach((slot) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'rounded-xl border-2 border-[#c9dcff] bg-white px-4 py-3 text-sm font-semibold text-[#181A1E] transition hover:border-[#0067FF]';
                        button.textContent = `${formatTime(slot.startingTime)} – ${formatTime(slot.endingTime)}`;
                        button.addEventListener('click', () => selectSlot(button, slot, pretty));
                        slotGrid.appendChild(button);

                        if (!restored && previous.start === slot.startingTime && previous.end === slot.endingTime) {
                            restored = true;
                            selectSlot(button, slot, pretty);
                        }
                    });
                };

                const renderFiles = (key) => {
                    const list = form.querySelector(`[data-file-list="${key}"]`);
                    const input = form.querySelector(`[data-file-input="${key}"]`);
                    list.innerHTML = '';

                    const transfer = new DataTransfer();
                    files[key].forEach((file) => transfer.items.add(file));
                    input.files = transfer.files;

                    if (key === 'other') {
                        const name = otherName.value.trim() || 'this group';
                        otherCount.textContent = files.other.length
                            ? `${files.other.length} file(s) selected for "${name}"`
                            : '';
                        otherCount.classList.toggle('hidden', files.other.length === 0);
                    }

                    files[key].forEach((file, index) => {
                        const card = document.createElement('div');
                        card.className = 'flex flex-col gap-2';
                        const preview = document.createElement('div');
                        preview.className = 'flex h-14 items-center justify-center overflow-hidden rounded-lg border border-[#c9dcff] bg-[#f7faff]';

                        if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                            preview.innerHTML = '<span class="px-2 text-center text-xs font-semibold text-[#0067FF]">PDF</span>';
                        } else {
                            const image = document.createElement('img');
                            image.src = URL.createObjectURL(file);
                            image.alt = file.name;
                            image.className = 'h-full w-full object-cover';
                            preview.appendChild(image);
                        }

                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'text-xs font-semibold text-red-600';
                        remove.textContent = 'Remove';
                        remove.addEventListener('click', () => {
                            files[key].splice(index, 1);
                            renderFiles(key);
                        });

                        card.append(preview, remove);
                        list.appendChild(card);
                    });
                };

                form.querySelectorAll('[data-file-input]').forEach((input) => {
                    input.addEventListener('change', () => {
                        const key = input.dataset.fileInput;
                        files[key].push(...Array.from(input.files || []));
                        renderFiles(key);
                    });
                });

                otherName?.addEventListener('input', () => renderFiles('other'));
                dateInput?.addEventListener('change', renderSlots);
                if (dateInput?.value) renderSlots();

                form.addEventListener('submit', (event) => {
                    if (!dateInput.value || !startInput.value || !endInput.value) {
                        event.preventDefault();
                        showError('Choose an appointment date and an open time slot.');
                        dateInput.focus();
                        return;
                    }

                    if (!loggingIn && files.other.length > 0 && !otherName.value.trim()) {
                        event.preventDefault();
                        showError('Name the other report group before confirming.');
                        otherName.focus();
                        return;
                    }

                    const button = form.querySelector('[data-submit]');
                    button.disabled = true;
                    button.textContent = loggingIn ? 'Opening sign in...' : 'Processing your booking...';
                });
            });
        </script>
    @endpush
@endonce
