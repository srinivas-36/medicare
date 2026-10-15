@php
    $openSlots = $openSlots ?? [];
    $doctorSlug = $appointment->doctor?->user?->slug;
    $doctorUrl = $doctorSlug ? route('doctors.show', $doctorSlug) : null;
    $age = $record['age'] ?? null;
    $ageLabel = ($age === null || $age === '') ? null : (is_numeric($age) ? $age.' years' : $age);
    $fee = $booking['ticket_price'];
    $feeLabel = $fee ? '$'.$fee : null;
    $summary = [
        ['icon' => 'user', 'label' => 'Doctor', 'value' => $booking['doctor_name']],
        ['icon' => 'stethoscope', 'label' => 'Specialization', 'value' => $booking['specialization']],
        ['icon' => 'user', 'label' => 'Patient', 'value' => $booking['patient_name']],
        ['icon' => 'calendar', 'label' => 'Date', 'value' => $booking['date']],
        ['icon' => 'calendar', 'label' => 'Day', 'value' => $booking['day']],
        ['icon' => 'check', 'label' => 'Status', 'value' => $booking['status']['label'], 'badge' => $booking['status']['class']],
        ['icon' => 'clock', 'label' => 'Time slot', 'value' => $booking['slot']],
        ['icon' => 'card', 'label' => 'Ticket price', 'value' => $feeLabel],
    ];
    $reportRows = collect($booking['reports'])->flatMap(function (array $group) {
        return collect($group['files'])->map(fn (array $file) => $file + ['group' => $group['name']]);
    });
    $fileInput = 'mt-2 block w-full cursor-pointer rounded-lg border border-[#C5D9FF] bg-white px-3 py-2 text-sm text-body file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-[#0067FF] file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-white';
@endphp

<header class="mb-4">
    <a href="{{ $backUrl }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary no-underline hover:text-primary-hover">
        <x-ui.icon name="chevron-left" class="h-4 w-4" />
        {{ $backLabel }}
    </a>
    <div class="mt-3 flex flex-wrap items-center gap-3">
        <h1 class="text-2xl font-semibold tracking-tight text-heading">Appointment details</h1>
    </div>
    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-body">
        <span>{{ $booking['date'] }}</span>
        <span aria-hidden="true">·</span>
        <span>{{ $booking['slot'] }}</span>
        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $booking['status']['class'] }}">
            @if (in_array($booking['status']['value'], ['confirmed', 'approved', 'completed'], true))
                <x-ui.icon name="check" class="h-3.5 w-3.5" />
            @endif
            {{ $booking['status']['label'] }}
        </span>
    </div>
</header>

<div class="grid items-start gap-5 lg:grid-cols-[minmax(280px,340px)_minmax(0,1fr)]">
    <aside class="flex flex-col gap-4 lg:sticky lg:top-6 lg:z-10 lg:self-start">
        <section class="rounded-2xl border border-line bg-white p-5 shadow-card">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-heading">Your doctor</h2>
                @if ($doctorUrl)
                    <a href="{{ $doctorUrl }}" class="text-muted hover:text-primary" aria-label="View {{ $person['name'] }}">
                        <x-ui.icon name="chevron-right" class="h-5 w-5" />
                    </a>
                @endif
            </div>
            <div class="mt-4 flex items-center gap-3">
                @if ($person['photo'])
                    <img src="{{ $person['photo'] }}" alt="{{ $person['name'] }}" class="h-12 w-12 rounded-full object-cover">
                @else
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-light text-sm font-bold text-primary">{{ $person['initials'] }}</span>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-heading">{{ $person['name'] ?: 'Doctor' }}</p>
                    <p class="truncate text-sm text-body">{{ $person['specialization'] ?: 'Specialist' }}</p>
                </div>
            </div>
            <dl class="mt-4 space-y-3">
                <div class="flex items-center gap-3 text-sm text-body">
                    <x-ui.icon name="mail" class="h-4 w-4 shrink-0 text-muted" />
                    <dd class="min-w-0 truncate">{{ $person['email'] ?: 'Not provided' }}</dd>
                </div>
                <div class="flex items-center gap-3 text-sm text-body">
                    <x-ui.icon name="phone" class="h-4 w-4 shrink-0 text-muted" />
                    <dd class="min-w-0 truncate">{{ $person['phone'] ?: 'Not provided' }}</dd>
                </div>
                <div class="flex items-center gap-3 text-sm text-body">
                    <x-ui.icon name="stethoscope" class="h-4 w-4 shrink-0 text-muted" />
                    <dd class="min-w-0 truncate">{{ $person['specialization'] ?: 'Specialist' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-line bg-white p-5 shadow-card">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-heading">Your details</h2>
                <a href="{{ route('patient.profile.edit') }}" class="text-muted hover:text-primary" aria-label="Edit your profile">
                    <x-ui.icon name="chevron-right" class="h-5 w-5" />
                </a>
            </div>
            <div class="mt-4 flex items-center gap-3">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-violet-100 text-sm font-bold text-violet-700">{{ \App\Support\AppointmentPage::initials((string) ($record['name'] ?? ''), 'P') }}</span>
                <p class="truncate text-sm font-semibold text-heading">{{ $record['name'] ?: 'Patient' }}</p>
            </div>
            <dl class="mt-4 space-y-3">
                <div class="flex items-center gap-3 text-sm">
                    <x-ui.icon name="calendar" class="h-4 w-4 shrink-0 text-muted" />
                    <dt class="text-body">Age</dt>
                    <dd class="ml-auto font-semibold text-heading">{{ $ageLabel ?: 'Not provided' }}</dd>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <x-ui.icon name="gender" class="h-4 w-4 shrink-0 text-muted" />
                    <dt class="text-body">Gender</dt>
                    <dd class="ml-auto font-semibold text-heading">{{ $record['gender'] ?: 'Not provided' }}</dd>
                </div>
                <div class="flex items-center gap-3 text-sm">
                    <x-ui.icon name="droplet" class="h-4 w-4 shrink-0 text-muted" />
                    <dt class="text-body">Blood type</dt>
                    <dd class="ml-auto font-semibold text-heading">{{ $record['blood_type'] ?: 'Not provided' }}</dd>
                </div>
            </dl>
        </section>

        <section class="rounded-2xl border border-line bg-white p-5 shadow-card">
            <div class="flex items-center gap-2">
                <x-ui.icon name="note" class="h-4 w-4 text-primary" />
                <h2 class="text-base font-semibold text-heading">Doctor notes</h2>
            </div>
            @if (count($booking['notes']) === 0)
                <p class="mt-4 text-sm leading-6 text-body">No notes from the doctor yet.</p>
            @else
                <div class="mt-4 space-y-3">
                    @foreach ($booking['notes'] as $note)
                        <div>
                            <p class="line-clamp-4 whitespace-pre-line text-sm leading-5 text-ink">{{ $note['text'] }}</p>
                            @if ($note['date'] !== '')
                                <p class="mt-1 text-xs text-muted">{{ $note['date'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </aside>

    <div class="space-y-5">
        <section class="rounded-2xl border border-line bg-white p-5 shadow-card">
            <div class="flex items-center gap-2">
                <x-ui.icon name="clipboard" class="h-4 w-4 text-primary" />
                <h2 class="text-base font-semibold text-heading">Appointment summary</h2>
            </div>
            <dl class="mt-5 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                @foreach ($summary as $item)
                    <div class="flex items-start gap-3">
                        <x-ui.icon :name="$item['icon']" class="mt-0.5 h-4 w-4 shrink-0 text-muted" />
                        <div class="min-w-0">
                            <dt class="text-xs text-muted">{{ $item['label'] }}</dt>
                            <dd class="mt-0.5 text-sm font-semibold text-heading">
                                @if (! empty($item['badge']))
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $item['badge'] }}">
                                        @if (in_array($booking['status']['value'], ['confirmed', 'approved', 'completed'], true))
                                            <x-ui.icon name="check" class="h-3.5 w-3.5" />
                                        @endif
                                        {{ $item['value'] ?: 'Not provided' }}
                                    </span>
                                @else
                                    {{ ($item['value'] === null || $item['value'] === '') ? 'Not provided' : $item['value'] }}
                                @endif
                            </dd>
                        </div>
                    </div>
                @endforeach
            </dl>
        </section>

        @if ($appointment->canPatientCancel())
            <section id="reschedule" class="rounded-2xl border border-line bg-white p-5 shadow-card">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-rose-50 text-rose-600">
                        <x-ui.icon name="calendar" class="h-4 w-4" />
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-heading">Manage appointment</h2>
                        <p class="text-sm text-body">Pick another open day and time, or cancel this visit.</p>
                    </div>
                </div>

                @if ($errors->hasAny(['date', 'time', 'slot', 'starting_time']))
                    <p class="mt-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-danger">{{ $errors->first('date') ?: $errors->first('time') ?: $errors->first('slot') ?: $errors->first('starting_time') }}</p>
                @endif

                <form
                    method="POST"
                    action="{{ route('patient.bookings.reschedule', $appointment) }}"
                    data-reschedule
                    data-booked-date="{{ $appointment->appointment_date?->toDateString() }}"
                    data-booked-label="{{ $booking['slot'] }}"
                    class="mt-5"
                >
                    @csrf
                    <input type="hidden" name="slot" data-reschedule-slot value="{{ old('slot') }}">
                    <div class="grid gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
                        <div>
                            <p class="text-sm font-semibold text-heading">Available dates</p>
                            <p data-reschedule-date class="mt-1 text-sm font-semibold text-primary">Current booking</p>
                            <div data-reschedule-calendar class="mt-3"></div>
                            <p class="mt-2 flex items-center gap-2 text-xs text-body">
                                <span class="inline-block h-3 w-3 rounded bg-[#16A34A]" aria-hidden="true"></span>
                                Current booking
                            </p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-heading">Available time slots</p>
                            <div data-reschedule-slots class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2"></div>
                            <p data-reschedule-note class="mt-2 text-sm text-body">Choose a day after your current booking.</p>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary mt-4">Reschedule</button>
                </form>
                <script type="application/json" id="reschedule-open-slots">@json($openSlots)</script>

                <form id="cancel" method="POST" action="{{ route('patient.bookings.cancel', $appointment) }}" class="mt-5 flex flex-col gap-3 border-t border-line pt-5 sm:flex-row sm:items-end" data-confirm="This visit will be cancelled and the time will open again." data-confirm-title="Cancel this appointment?" data-confirm-ok="Cancel appointment" data-confirm-tone="danger">
                    @csrf
                    <label class="block min-w-0 flex-1 text-sm font-semibold text-heading">
                        Cancellation reason
                        <input type="text" name="cancel_reason" maxlength="255" value="{{ old('cancel_reason') }}" required class="field mt-2" placeholder="Enter reason for cancellation...">
                        @error('cancel_reason')
                            <span class="mt-1 block text-sm font-medium text-danger">{{ $message }}</span>
                        @enderror
                    </label>
                    <button type="submit" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-[#F5C2C7] bg-white px-4 text-sm font-semibold text-[#DC3545] hover:bg-[#FFF5F5]">
                        <x-ui.icon name="trash" class="h-4 w-4" />
                        Cancel appointment
                    </button>
                </form>
            </section>
        @endif

        <section class="rounded-2xl border border-line bg-white p-5 shadow-card">
            <div class="flex items-start gap-2">
                <x-ui.icon name="file" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                <div>
                    <h2 class="text-base font-semibold text-heading">Reports shared with your doctor</h2>
                    <p class="mt-0.5 text-sm text-body">Files you share with your doctor for this visit. You can add more or remove one you uploaded.</p>
                </div>
            </div>

            @if ($reportRows->isEmpty())
                <p class="mt-5 text-sm leading-6 text-body">No reports were attached to this booking.</p>
            @else
                <ul class="mt-4 divide-y divide-line">
                    @foreach ($reportRows as $file)
                        <li class="flex items-center gap-3 py-3">
                            <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-light text-primary">
                                <x-ui.icon name="file" class="h-4 w-4" />
                            </span>
                            <a href="{{ $file['url'] }}" target="_blank" rel="noopener" class="min-w-0 flex-1 hover:underline">
                                <span class="block truncate text-sm font-semibold text-heading">{{ $file['name'] }}</span>
                                <span class="block truncate text-xs text-muted">{{ $file['group'] }}</span>
                            </a>
                            <p class="hidden shrink-0 text-xs text-muted sm:block">
                                {{ collect([$file['uploaded'], $file['size']])->filter()->implode(' · ') }}
                            </p>
                            @if ($canUpload)
                                <form method="POST" action="{{ route('patient.bookings.reports.destroy', [$appointment, $file['id']]) }}" class="shrink-0" data-confirm="It will be taken off this visit." data-confirm-title="Remove this file?" data-confirm-ok="Remove file" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 text-sm font-semibold text-[#DC3545] hover:underline">
                                        <x-ui.icon name="trash" class="h-4 w-4" />
                                        Remove
                                    </button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($canUpload)
                <form method="POST" action="{{ route('patient.bookings.reports.store', $appointment) }}" enctype="multipart/form-data" class="mt-4 rounded-2xl border border-[#C5D9FF] bg-[#F7FAFF] p-4">
                    @csrf
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h3 class="inline-flex items-center gap-2 text-sm font-semibold text-heading">
                            <x-ui.icon name="upload" class="h-4 w-4 text-primary" />
                            Add another report
                        </h3>
                        <p class="text-xs text-muted">PDF or image, up to 5 MB each.</p>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="block text-sm font-semibold text-heading">
                            Blood test reports
                            <input type="file" name="blood_reports[]" accept="image/*,application/pdf" multiple class="{{ $fileInput }}">
                        </label>
                        <label class="block text-sm font-semibold text-heading">
                            X-ray scans
                            <input type="file" name="xray_reports[]" accept="image/*,application/pdf" multiple class="{{ $fileInput }}">
                        </label>
                    </div>
                    <div class="mt-4 grid gap-4 sm:grid-cols-[1fr_1fr_auto] sm:items-end">
                        <label class="block text-sm font-semibold text-heading">
                            Other report name
                            <input type="text" name="other_report_name" value="{{ old('other_report_name') }}" placeholder="MRI, ultrasound, prescription" class="field mt-2 bg-white">
                        </label>
                        <label class="block text-sm font-semibold text-heading">
                            Other files
                            <input type="file" name="other_reports[]" accept="image/*,application/pdf" multiple class="{{ $fileInput }}">
                        </label>
                        <button type="submit" class="btn-primary">
                            <x-ui.icon name="upload" class="h-4 w-4" />
                            Upload reports
                        </button>
                    </div>
                    <x-input-error :messages="$errors->get('blood_reports')" class="mt-3" />
                    <x-input-error :messages="$errors->get('blood_reports.*')" class="mt-3" />
                    <x-input-error :messages="$errors->get('xray_reports')" class="mt-3" />
                    <x-input-error :messages="$errors->get('xray_reports.*')" class="mt-3" />
                    <x-input-error :messages="$errors->get('other_report_name')" class="mt-3" />
                    <x-input-error :messages="$errors->get('other_reports')" class="mt-3" />
                    <x-input-error :messages="$errors->get('other_reports.*')" class="mt-3" />
                </form>
            @endif
        </section>
    </div>
</div>

@if ($appointment->canPatientCancel())
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-reschedule]');
                const payload = document.getElementById('reschedule-open-slots');
                if (!form || !payload) return;

                let open = {};
                try {
                    open = JSON.parse(payload.textContent || '{}');
                } catch (error) {
                    open = {};
                }

                const slotInput = form.querySelector('[data-reschedule-slot]');
                const calendar = form.querySelector('[data-reschedule-calendar]');
                const slotList = form.querySelector('[data-reschedule-slots]');
                const note = form.querySelector('[data-reschedule-note]');
                const pickedDate = form.querySelector('[data-reschedule-date]');
                const bookedDate = form.dataset.bookedDate || '';
                const bookedLabel = form.dataset.bookedLabel || 'Current booking';
                const dates = Object.keys(open).filter((value) => !bookedDate || value > bookedDate).sort();
                const saved = String(slotInput.value || '').split('|');
                let selectedDate = dates.includes(saved[0]) ? saved[0] : '';
                let selectedStart = saved[1] || '';
                let monthCursor = new Date(`${(selectedDate || bookedDate || dates[0] || new Date().toISOString().slice(0, 10))}T00:00:00`);
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
                    if (!value) return 'Choose an open day';
                    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', {
                        weekday: 'short',
                        month: 'short',
                        day: 'numeric',
                    });
                };

                const paintCalendar = () => {
                    const year = monthCursor.getFullYear();
                    const month = monthCursor.getMonth();
                    const first = new Date(year, month, 1);
                    const startPad = (first.getDay() + 6) % 7;
                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                    const label = first.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                    const cells = [];

                    for (let i = 0; i < startPad; i += 1) cells.push('<span></span>');
                    for (let day = 1; day <= daysInMonth; day += 1) {
                        const value = isoDate(new Date(year, month, day));
                        const isBooked = bookedDate !== '' && value === bookedDate;
                        const available = !isBooked && dates.includes(value);
                        const selected = available && selectedDate === value;
                        const classes = [
                            'h-8 rounded-md text-xs font-semibold',
                            isBooked
                                ? 'bg-[#16A34A] text-white'
                                : (selected
                                    ? 'bg-[#0067FF] text-white'
                                    : (available ? 'bg-[#E8F2FF] text-[#0067FF] hover:ring-1 hover:ring-[#0067FF]' : 'cursor-not-allowed text-[#CBD5E1] line-through')),
                        ].join(' ');
                        const title = isBooked ? ' title="Current booking"' : '';
                        cells.push(`<button type="button" class="${classes}" data-day="${value}" data-booked="${isBooked ? '1' : '0'}" ${available || isBooked ? '' : 'disabled'}${title}>${day}</button>`);
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

                const paintCurrentBooking = () => {
                    slotList.innerHTML = '';
                    pickedDate.textContent = formatDate(bookedDate);
                    pickedDate.className = 'mt-1 text-sm font-semibold text-[#15803D]';
                    slotInput.value = '';
                    selectedStart = '';

                    if (!bookedDate) {
                        note.textContent = 'Choose a day after your current booking.';
                        return;
                    }

                    const current = document.createElement('button');
                    current.type = 'button';
                    current.disabled = true;
                    current.textContent = bookedLabel;
                    current.className = 'rounded-lg border border-[#16A34A] bg-[#16A34A] px-3 py-2 text-left text-sm font-semibold text-white';
                    slotList.appendChild(current);
                    note.textContent = 'This is your current booking. Choose a later day to reschedule.';
                };

                const paintSlots = () => {
                    slotList.innerHTML = '';
                    const times = (selectedDate && dates.includes(selectedDate) ? open[selectedDate] : null) || [];

                    if (!selectedDate || times.length === 0) {
                        paintCurrentBooking();
                        return;
                    }

                    pickedDate.textContent = formatDate(selectedDate);
                    pickedDate.className = 'mt-1 text-sm font-semibold text-primary';

                    note.textContent = `${times.length} open ${times.length === 1 ? 'time' : 'times'} on this day.`;
                    let matched = false;
                    times.forEach((slot) => {
                        const active = selectedStart === slot.start;
                        if (active) matched = true;
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.textContent = `${formatTime(slot.start)} – ${formatTime(slot.end)}`;
                        button.className = active
                            ? 'rounded-lg border border-[#0067FF] bg-[#0067FF] px-3 py-2 text-left text-sm font-semibold text-white'
                            : 'rounded-lg border border-[#E2E8F0] bg-white px-3 py-2 text-left text-sm font-semibold text-[#181A1E] hover:border-[#0067FF]';
                        button.addEventListener('click', () => {
                            selectedStart = slot.start;
                            slotInput.value = `${selectedDate}|${slot.start}`;
                            paintSlots();
                        });
                        slotList.appendChild(button);
                    });

                    slotInput.value = matched ? `${selectedDate}|${selectedStart}` : '';
                    if (!matched) selectedStart = '';
                };

                calendar.addEventListener('click', (event) => {
                    const monthButton = event.target.closest('[data-month]');
                    const dayButton = event.target.closest('[data-day]');
                    if (monthButton) {
                        monthCursor.setMonth(monthCursor.getMonth() + Number(monthButton.dataset.month));
                        paintCalendar();
                    }
                    if (dayButton?.dataset.booked === '1') {
                        selectedDate = '';
                        selectedStart = '';
                        slotInput.value = '';
                        paintCalendar();
                        paintCurrentBooking();
                        return;
                    }
                    if (dayButton && !dayButton.disabled && dates.includes(dayButton.dataset.day)) {
                        selectedDate = dayButton.dataset.day;
                        selectedStart = '';
                        slotInput.value = '';
                        paintCalendar();
                        paintSlots();
                    }
                });

                form.addEventListener('submit', (event) => {
                    if (!slotInput.value || (bookedDate && slotInput.value.slice(0, 10) <= bookedDate)) {
                        event.preventDefault();
                        note.textContent = 'Choose a day after your current booking, then pick a time.';
                    }
                });

                paintCalendar();
                paintSlots();
            });
        </script>
    @endpush
@endif
