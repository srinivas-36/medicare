@php
    $name = $doctor->user?->name ?? 'Doctor';
    $specialty = $doctor->specialization ?: 'Specialist';
    $degrees = $doctor->qualifications->pluck('degree')->filter()->take(3)->implode(' · ');
    $current = $doctor->experiences->firstWhere('is_current', true) ?? $doctor->experiences->first();
    $practice = $current?->designation
        ? trim($current->designation.($current->hospital_name ? ' · '.$current->hospital_name : ''))
        : '';
    $reviews = (int) $doctor->total_rating;
    $duration = max(5, (int) ($doctor->appointment_duration ?? 30));
    $fee = $doctor->ticketPrice;
    $reportGroups = [
        ['key' => 'blood', 'name' => 'Blood tests', 'hint' => 'Lab results and blood reports', 'icon' => 'file', 'tone' => 'bg-rose-50 text-rose-600'],
        ['key' => 'xray', 'name' => 'X-rays and scans', 'hint' => 'Imaging, MRI, and CT files', 'icon' => 'image', 'tone' => 'bg-emerald-50 text-emerald-700'],
        ['key' => 'other', 'name' => 'Other reports', 'hint' => 'Ultrasound, ECG, or anything else', 'icon' => 'file', 'tone' => 'bg-violet-50 text-violet-700'],
    ];
    $durations = ['Less than a day', '1–3 days', '4–7 days', '1–2 weeks', '2–4 weeks', 'More than a month'];
@endphp

<form
    data-visit-book
    method="POST"
    action="{{ route('doctors.book.store', $doctor) }}"
    enctype="multipart/form-data"
    class="space-y-4"
>
    @csrf

    @if ($errors->any())
        <div class="alert-error" role="alert">
            <p class="font-semibold">Check the booking details and try again.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-2xl border border-[#E2E8F0] bg-white p-5 shadow-card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <x-ui.avatar :name="$name" :photo="$doctor->user?->photo" size="lg" />
                <div class="min-w-0">
                    <h2 class="truncate text-base font-semibold text-heading">{{ $name }}</h2>
                    <p class="truncate text-sm font-medium text-[#0067FF]">{{ $specialty }}</p>
                    @if ($degrees !== '')
                        <p class="mt-0.5 truncate text-[13px] text-body">{{ $degrees }}</p>
                    @endif
                </div>
            </div>
            <dl class="grid grid-cols-2 gap-4 sm:flex sm:items-center sm:gap-6">
                @if ($reviews > 0)
                    <div>
                        <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">Rating</dt>
                        <dd class="mt-0.5 inline-flex items-center gap-1 text-sm font-semibold text-heading">
                            <x-ui.icon name="star" class="h-3.5 w-3.5 text-amber-400" />
                            {{ number_format((float) $doctor->average_rating, 1) }}
                        </dd>
                    </div>
                @endif
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">Visit</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-heading">{{ $duration }} minutes</dd>
                </div>
                <div>
                    <dt class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">Fee</dt>
                    <dd class="mt-0.5 text-sm font-semibold text-heading">{{ $fee }} USD</dd>
                </div>
            </dl>
        </div>
        @if ($practice !== '')
            <p class="mt-4 border-t border-[#E2E8F0] pt-3 text-sm text-body">{{ $practice }}</p>
        @endif
    </section>

    <section class="rounded-2xl border border-[#E2E8F0] bg-white p-5 shadow-card">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-heading">Choose a time</h2>
            <p class="mt-0.5 text-[13px] text-muted">Open times come from this doctor’s saved schedule.</p>
        </div>

        <div class="grid gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
            <div>
                <p class="label">Date</p>
                <p data-picked-date class="text-sm font-semibold text-heading">Choose an open day</p>
                <input id="book-date" data-book-date type="hidden" name="appointment_date" value="{{ old('appointment_date') }}" data-min="{{ $minDate }}" data-max="{{ $maxDate }}">
                <div data-book-calendar class="mt-3"></div>
                <p class="mt-2 text-xs leading-5 text-muted">Highlighted days are open. Days this doctor does not work are unavailable.</p>
            </div>
            <div>
                <p class="label">Available time slots</p>
                <select id="book-slot" data-book-slot class="sr-only" tabindex="-1" aria-hidden="true">
                    <option value="">Select a time</option>
                </select>
                <div data-book-slots class="grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                <p data-book-note class="mt-2 text-sm text-body">Choose a date to see open times.</p>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-[#E2E8F0] bg-white p-5 shadow-card">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-heading">Visit notes</h2>
            <p class="mt-0.5 text-[13px] text-muted">Tell the doctor what you want reviewed before the visit.</p>
        </div>

        <div class="space-y-4">
            <div>
                <label for="book-issues" class="label">Health issues</label>
                <textarea id="book-issues" name="health_issues" rows="4" maxlength="2000" placeholder="Describe symptoms or the reason for this visit" class="field">{{ old('health_issues') }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="symptom-duration" class="label">How long have you had them?</label>
                    <select id="symptom-duration" name="symptom_duration" class="field">
                        <option value="">Optional</option>
                        @foreach ($durations as $choice)
                            <option value="{{ $choice }}" @selected(old('symptom_duration') === $choice)>{{ $choice }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="current-medicines" class="label">Current medicines</label>
                    <input id="current-medicines" name="current_medicines" type="text" value="{{ old('current_medicines') }}" maxlength="500" placeholder="Optional" class="field">
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-2xl border border-[#E2E8F0] bg-white p-5 shadow-card">
        <div class="mb-4">
            <h2 class="text-sm font-semibold text-heading">Medical reports</h2>
            <p class="mt-0.5 text-[13px] text-muted">Optional. PDF or image files, up to 8 per group and 10 MB each.</p>
        </div>

        <!-- <div
            data-drop-zone
            class="mb-4 flex flex-col items-center justify-center rounded-xl border border-dashed border-[#b9d4ff] bg-[#F7FAFF] px-4 py-6 text-center"
        >
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#0067FF] shadow-sm">
                <x-ui.icon name="upload" class="h-5 w-5" />
            </span>
            <p class="mt-3 text-sm font-semibold text-heading">
                Drop other reports here or
                <label for="other-report-files" class="cursor-pointer text-[#0067FF]">browse</label>
            </p>
            <p class="mt-1 text-xs text-muted">They are saved with this booking when you submit.</p>
        </div> -->

        <div class="grid gap-3 md:grid-cols-3">
            @foreach ($reportGroups as $group)
                <div class="flex min-h-[168px] flex-col rounded-xl border border-[#E2E8F0] p-4">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg {{ $group['tone'] }}">
                        <x-ui.icon :name="$group['icon']" class="h-4 w-4" />
                    </span>
                    <h3 class="mt-3 text-sm font-semibold text-heading">{{ $group['name'] }}</h3>
                    <p class="mt-1 text-xs leading-5 text-muted">{{ $group['hint'] }}</p>
                    <label class="mt-3 block">
                        <span class="text-xs font-semibold text-heading">Report name</span>
                        <input
                            type="text"
                            name="{{ $group['key'] }}_report_name"
                            value="{{ old($group['key'].'_report_name') }}"
                            maxlength="120"
                            placeholder="{{ $group['key'] === 'other' ? 'MRI, ultrasound, prescription' : $group['name'] }}"
                            class="field mt-1.5 text-xs"
                        >
                    </label>
                    <div data-file-list="{{ $group['key'] }}" class="mt-3 space-y-1 empty:hidden"></div>
                    <label class="btn-secondary mt-auto w-full cursor-pointer" @if ($group['key'] === 'other') for="other-report-files" @endif>
                        Add files
                        @if ($group['key'] !== 'other')
                            <input
                                data-file-input="{{ $group['key'] }}"
                                type="file"
                                name="{{ $group['key'] }}_reports[]"
                                accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*"
                                multiple
                                class="file-picker"
                            >
                        @endif
                    </label>
                </div>
            @endforeach
        </div>
        <input
            id="other-report-files"
            data-file-input="other"
            type="file"
            name="other_reports[]"
            accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*"
            multiple
            class="file-picker"
        >
    </section>

    <input type="hidden" name="slot_day" data-slot-day value="{{ old('slot_day') }}">
    <input type="hidden" name="starting_time" data-starting-time value="{{ old('starting_time') }}">
    <input type="hidden" name="ending_time" data-ending-time value="{{ old('ending_time') }}">

    <div class="sticky bottom-0 z-10 rounded-2xl border border-[#E2E8F0] bg-white px-4 py-4 shadow-card sm:px-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="grid flex-1 gap-3 sm:grid-cols-3">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">Date</p>
                    <p data-summary-date class="mt-0.5 text-sm font-semibold text-heading">Not selected</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">Time</p>
                    <p data-summary-time class="mt-0.5 text-sm font-semibold text-heading">Not selected</p>
                </div>
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.12em] text-muted">Fee</p>
                    <p class="mt-0.5 text-sm font-semibold text-heading">{{ $fee }} USD</p>
                </div>
            </div>
            <button type="submit" class="btn-primary lg:min-w-[200px]">Submit booking</button>
        </div>
    </div>
</form>

@once
    @push('scripts')
        <script type="application/json" id="visit-open-slots">@json($openSlots)</script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-visit-book]');
                const payload = document.getElementById('visit-open-slots');
                if (!form || !payload) return;

                let open = {};
                try {
                    open = JSON.parse(payload.textContent || '{}');
                } catch (error) {
                    open = {};
                }

                const dateInput = form.querySelector('[data-book-date]');
                const slotSelect = form.querySelector('[data-book-slot]');
                const slotList = form.querySelector('[data-book-slots]');
                const calendar = form.querySelector('[data-book-calendar]');
                const pickedDate = form.querySelector('[data-picked-date]');
                const dayInput = form.querySelector('[data-slot-day]');
                const startInput = form.querySelector('[data-starting-time]');
                const endInput = form.querySelector('[data-ending-time]');
                const note = form.querySelector('[data-book-note]');
                const summaryDate = form.querySelector('[data-summary-date]');
                const summaryTime = form.querySelector('[data-summary-time]');
                const minDate = dateInput.dataset.min || '';
                const maxDate = dateInput.dataset.max || '';
                let monthCursor = dateInput.value ? new Date(`${dateInput.value}T00:00:00`) : new Date();
                monthCursor.setDate(1);

                const formatTime = (time) => {
                    const [hours, minutes] = String(time).split(':').map(Number);
                    const meridian = hours >= 12 ? 'PM' : 'AM';
                    const hour = hours % 12 || 12;
                    return `${hour}:${String(minutes).padStart(2, '0')} ${meridian}`;
                };

                const isoDate = (date) => {
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${date.getFullYear()}-${month}-${day}`;
                };

                const formatDate = (value) => {
                    if (!value) return 'Not selected';
                    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric',
                    });
                };

                const applySlot = () => {
                    const [day, start, end] = String(slotSelect.value || '').split('|');
                    dayInput.value = day || '';
                    startInput.value = start || '';
                    endInput.value = end || '';
                };

                const isOpenDate = (value) => {
                    if (!value) return false;
                    if ((minDate && value < minDate) || (maxDate && value > maxDate)) return false;
                    return Array.isArray(open[value]) && open[value].length > 0;
                };

                const paintFiles = (input) => {
                    const list = form.querySelector(`[data-file-list="${input.dataset.fileInput}"]`);
                    if (!list) return;
                    list.replaceChildren();

                    const chosen = [...input.files];
                    if (chosen.length > 8) {
                        const warning = document.createElement('p');
                        warning.className = 'text-xs font-medium text-[#B91C1C]';
                        warning.textContent = 'Choose 8 files or fewer in this group.';
                        list.appendChild(warning);
                        return;
                    }

                    chosen.forEach((file) => {
                        const item = document.createElement('p');
                        item.className = 'truncate text-xs font-medium text-[#181A1E]';
                        item.textContent = file.name;
                        list.appendChild(item);
                    });
                };

                const paintCalendar = () => {
                    if (!calendar) return;
                    const year = monthCursor.getFullYear();
                    const month = monthCursor.getMonth();
                    const first = new Date(year, month, 1);
                    const startPad = (first.getDay() + 6) % 7;
                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                    const label = first.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                    const cells = [];

                    for (let i = 0; i < startPad; i += 1) cells.push('<span></span>');
                    for (let day = 1; day <= daysInMonth; day += 1) {
                        const date = new Date(year, month, day);
                        const value = isoDate(date);
                        const available = isOpenDate(value);
                        const selected = available && dateInput.value === value;
                        const classes = [
                            'h-8 rounded-md text-xs font-semibold',
                            selected ? 'bg-[#0067FF] text-white' : (available ? 'bg-[#E8F2FF] text-[#0067FF] hover:ring-1 hover:ring-[#0067FF]' : 'cursor-not-allowed text-[#CBD5E1] line-through'),
                        ].join(' ');
                        cells.push(`<button type="button" class="${classes}" data-day="${value}" ${available ? '' : 'disabled aria-disabled="true"'} title="${available ? 'Open for booking' : 'Doctor is not available'}">${day}</button>`);
                    }

                    calendar.innerHTML = `
                        <div class="rounded-xl border border-[#E2E8F0] bg-[#F8FAFC] p-2">
                            <div class="mb-2 flex items-center justify-between">
                                <button type="button" data-month="-1" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-[#181A1E] hover:bg-white" aria-label="Previous month">‹</button>
                                <p class="text-xs font-semibold text-[#181A1E]">${label}</p>
                                <button type="button" data-month="1" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-[#181A1E] hover:bg-white" aria-label="Next month">›</button>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-semibold uppercase text-[#94A3B8]">
                                <span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span><span>Su</span>
                            </div>
                            <div class="mt-1 grid grid-cols-7 gap-1">${cells.join('')}</div>
                        </div>
                    `;
                };

                const render = () => {
                    if (dateInput.value && !isOpenDate(dateInput.value)) {
                        dateInput.value = '';
                        dayInput.value = '';
                        startInput.value = '';
                        endInput.value = '';
                    }

                    paintCalendar();
                    slotSelect.innerHTML = '<option value="">Select a time</option>';
                    slotList.innerHTML = '';
                    summaryDate.textContent = formatDate(dateInput.value);
                    if (pickedDate) pickedDate.textContent = dateInput.value ? formatDate(dateInput.value) : 'Choose an open day';

                    if (!dateInput.value) {
                        note.textContent = 'Choose an open day to see times.';
                        summaryTime.textContent = 'Not selected';
                        return;
                    }

                    const weekday = new Date(`${dateInput.value}T00:00:00`).toLocaleDateString('en-US', { weekday: 'long' }).toLowerCase();
                    const available = (open[dateInput.value] || []).map((slot) => ({
                        day: weekday,
                        startingTime: slot.start,
                        endingTime: slot.end,
                    }));

                    if (available.length === 0) {
                        dayInput.value = '';
                        startInput.value = '';
                        endInput.value = '';
                        note.textContent = 'This doctor has no open time on that date.';
                        summaryTime.textContent = 'Not selected';
                        return;
                    }

                    note.textContent = `${available.length} open ${available.length === 1 ? 'time' : 'times'} on this date.`;
                    available.forEach((slot) => {
                        const value = `${slot.day}|${slot.startingTime}|${slot.endingTime}`;
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = `${formatTime(slot.startingTime)} – ${formatTime(slot.endingTime)}`;
                        option.selected = startInput.value === slot.startingTime && endInput.value === slot.endingTime;
                        slotSelect.appendChild(option);

                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = `${formatTime(slot.startingTime)} – ${formatTime(slot.endingTime)}`;
                        button.className = option.selected
                            ? 'rounded-lg border border-[#0067FF] bg-[#0067FF] px-3 py-2 text-left text-sm font-semibold text-white'
                            : 'rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-left text-sm font-semibold text-[#181A1E] hover:border-[#0067FF]';
                        button.addEventListener('click', () => {
                            slotSelect.value = value;
                            applySlot();
                            render();
                        });
                        slotList.appendChild(button);
                    });

                    if (slotSelect.value) {
                        applySlot();
                        summaryTime.textContent = slotSelect.options[slotSelect.selectedIndex].textContent;
                    } else {
                        summaryTime.textContent = 'Not selected';
                    }
                };

                calendar?.addEventListener('click', (event) => {
                    const monthButton = event.target.closest('[data-month]');
                    const dayButton = event.target.closest('[data-day]');
                    if (monthButton) {
                        monthCursor.setMonth(monthCursor.getMonth() + Number(monthButton.dataset.month));
                        paintCalendar();
                    }
                    if (dayButton && !dayButton.disabled && isOpenDate(dayButton.dataset.day)) {
                        dateInput.value = dayButton.dataset.day;
                        startInput.value = '';
                        endInput.value = '';
                        dayInput.value = '';
                        render();
                    }
                });

                form.querySelectorAll('[data-file-input]').forEach((input) => {
                    input.addEventListener('change', () => paintFiles(input));
                });

                const zone = form.querySelector('[data-drop-zone]');
                const otherInput = form.querySelector('[data-file-input="other"]');
                if (zone && otherInput) {
                    ['dragenter', 'dragover'].forEach((name) => {
                        zone.addEventListener(name, (event) => {
                            event.preventDefault();
                            zone.classList.add('ring-2', 'ring-[#0067FF]');
                        });
                    });
                    ['dragleave', 'drop'].forEach((name) => {
                        zone.addEventListener(name, (event) => {
                            event.preventDefault();
                            zone.classList.remove('ring-2', 'ring-[#0067FF]');
                        });
                    });
                    zone.addEventListener('drop', (event) => {
                        if (!event.dataTransfer?.files?.length || typeof DataTransfer === 'undefined') return;
                        const transfer = new DataTransfer();
                        [...event.dataTransfer.files].slice(0, 8).forEach((file) => transfer.items.add(file));
                        otherInput.files = transfer.files;
                        paintFiles(otherInput);
                    });
                }

                form.addEventListener('submit', (event) => {
                    applySlot();
                    const tooManyFiles = [...form.querySelectorAll('[data-file-input]')].some((input) => input.files.length > 8);
                    if (tooManyFiles) {
                        event.preventDefault();
                        note.textContent = 'Each report group can include up to 8 files.';
                        return;
                    }
                    if (!isOpenDate(dateInput.value) || !startInput.value || !endInput.value) {
                        event.preventDefault();
                        note.textContent = 'Choose an open day and a time before submitting.';
                    }
                });

                render();
            });
        </script>
    @endpush
@endonce
