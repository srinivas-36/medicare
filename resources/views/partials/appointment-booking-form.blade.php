@php
    $doctor = $doctor ?? [];
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
    $reviews = (int) data_get($doctor, 'totalRating', 0);
    $fee = data_get($doctor, 'ticketPrice', 0);
    $duration = max(5, (int) data_get($doctor, 'appointmentDuration', 30));
    $timezoneLabel = \App\Services\AppointmentBooking::clinicNow()->getTimezone()->getName();
    $specialty = data_get($doctor, 'specialization');

    $experiences = collect(data_get($doctor, 'experiences', []));
    $clinicName = $experiences->pluck('hospital')->filter()->first();
    $clinicLocation = $experiences->pluck('location')->filter()->first();
    $clinicLine = collect([$clinicName, $clinicLocation])->filter()->implode(', ');
    $earliestStart = $experiences->pluck('startingDate')->filter()->sort()->first();
    $experienceYears = $earliestStart
        ? max(0, (int) \Illuminate\Support\Carbon::parse($earliestStart)->diffInYears(now()))
        : 0;
    $experienceLine = $specialty
        ? ($experienceYears > 0
            ? $specialty.' specialist with '.$experienceYears.'+ years of experience'
            : $specialty.' specialist')
        : null;

    $slots = collect(data_get($doctor, 'timeSlots', []))
        ->filter(fn ($slot) => (bool) data_get($slot, 'isAvailable', true))
        ->map(fn ($slot) => [
            'day' => strtolower((string) data_get($slot, 'day')),
            'startingTime' => substr((string) data_get($slot, 'startingTime'), 0, 5),
            'endingTime' => substr((string) data_get($slot, 'endingTime'), 0, 5),
        ])
        ->values();
    $openSlots = $openSlots ?? [];
    $draftReports = $draftReports ?? [];
    $horizonStart = now()->toDateString();
    $horizonEnd = \App\Services\AppointmentBooking::bookableUntil()->toDateString();
    $remembered = session('booking_input');
    $rememberedInput = is_array($remembered) && (int) data_get($remembered, 'doctor_id') === (int) $doctorId
        ? (array) data_get($remembered, 'input', [])
        : [];
    $field = function (string $key, mixed $default = '') use ($rememberedInput) {
        return old($key, $rememberedInput[$key] ?? $default);
    };

    $reportGroups = [
        ['key' => 'blood', 'name' => 'Blood tests', 'hint' => 'Lab results, blood reports', 'icon' => 'file', 'tone' => 'bg-rose-50 text-rose-500'],
        ['key' => 'xray', 'name' => 'X-rays & scans', 'hint' => 'Imaging files, MRI, CT scans', 'icon' => 'image', 'tone' => 'bg-emerald-50 text-emerald-600'],
        ['key' => 'other', 'name' => 'Other reports', 'hint' => 'Ultrasound, ECG, or any other reports', 'icon' => 'file', 'tone' => 'bg-violet-50 text-violet-500'],
    ];
@endphp

@if (session('status'))
    <p class="mb-4 rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-4 py-3 text-sm leading-6 text-ink">{{ session('status') }}</p>
@endif

<section class="rounded-2xl border border-line bg-white px-5 py-5 shadow-card sm:px-6">
    <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-center gap-4">
            <img src="{{ $photoUrl }}" alt="{{ data_get($doctor, 'name') }}" class="h-[72px] w-[72px] shrink-0 rounded-full object-cover ring-1 ring-line">
            <div class="min-w-0">
                <h2 class="truncate text-2xl font-bold tracking-tight text-navy">{{ data_get($doctor, 'name') }}</h2>
                @if ($specialty)
                    <p class="mt-1 inline-flex items-center gap-1.5 text-sm text-body">
                        <x-ui.icon name="heart" class="h-4 w-4 text-primary" />
                        {{ $specialty }}
                    </p>
                @endif
                <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-body">
                    <span class="inline-flex items-center gap-1.5 font-semibold text-navy">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        <x-ui.icon name="star" class="h-4 w-4 text-amber-400" />
                        {{ $rating }} ({{ $reviews }} {{ $reviews === 1 ? 'review' : 'reviews' }})
                    </span>
                    @if ($experienceLine)
                        <span class="text-muted" aria-hidden="true">·</span>
                        <span>{{ $experienceLine }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 sm:gap-6 lg:shrink-0">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-page text-primary">
                    <x-ui.icon name="pin" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-navy">Clinic visit</p>
                    <p class="text-xs text-muted">{{ $clinicLine !== '' ? $clinicLine : 'In-person consultation' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-page text-primary">
                    <x-ui.icon name="clock" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-navy">{{ $duration }}-minute</p>
                    <p class="text-xs text-muted">consultation</p>
                </div>
            </div>
            <div class="hidden h-12 w-px bg-line lg:block"></div>
            <div class="lg:text-right">
                <p class="text-xs text-muted">Consultation fee</p>
                <p class="text-2xl font-bold tracking-tight text-navy">{{ $fee }} USD</p>
            </div>
        </div>
    </div>
</section>

@if ($role === 'doctor')
    <div class="mt-4 rounded-2xl border border-line bg-white p-6 shadow-card">
        <p class="rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-4 py-3 text-sm leading-6 text-ink">
            Doctors can’t book appointments. Patients use this page to reserve a slot.
        </p>
    </div>
@else
    <form
        data-booking-form
        data-intent="{{ $canBook ? 'book' : 'login' }}"
        data-no-lock="true"
        data-duration="{{ $duration }}"
        data-horizon-start="{{ $horizonStart }}"
        data-horizon-end="{{ $horizonEnd }}"
        method="POST"
        action="{{ $canBook ? $storeUrl : $rememberUrl }}"
        enctype="multipart/form-data"
        class="mt-4 space-y-4"
    >
        @csrf

        @if ($errors->any())
            <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm leading-6 text-red-700">
                {{ $errors->first() }}
            </div>
        @endif

        <p data-booking-error class="hidden rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm leading-6 text-red-700"></p>

        <div class="grid items-stretch gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.85fr)]">
            <section class="rounded-2xl border border-line bg-white p-5 shadow-card sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex gap-3">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">1</span>
                        <h2 class="pt-1 text-base font-bold text-navy">Choose date &amp; time</h2>
                    </div>
                    <p class="inline-flex h-9 shrink-0 items-center gap-2 rounded-lg border border-line bg-white px-3 text-xs font-medium text-body">
                        <x-ui.icon name="globe" class="h-4 w-4 text-muted" />
                        {{ $timezoneLabel }}
                        <x-ui.icon name="chevron" class="h-4 w-4 text-muted" />
                    </p>
                </div>
                <p class="mt-1 pl-11 text-sm text-body">Select a date and an available time slot for your appointment.</p>

                <div class="mt-5 grid gap-4 lg:grid-cols-[268px_minmax(0,1fr)]">
                    <div class="rounded-2xl border border-line bg-white p-3">
                        <div class="flex items-center justify-between px-1">
                            <button type="button" data-cal-prev class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-body hover:bg-page" aria-label="Previous month">
                                <x-ui.icon name="chevron" class="h-4 w-4 rotate-90" />
                            </button>
                            <p data-cal-label class="text-sm font-semibold text-navy"></p>
                            <button type="button" data-cal-next class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-body hover:bg-page" aria-label="Next month">
                                <x-ui.icon name="chevron" class="h-4 w-4 -rotate-90" />
                            </button>
                        </div>
                        <div class="mt-3 grid grid-cols-7 gap-y-1 text-center text-[11px] font-medium text-muted">
                            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>
                        <div data-cal-grid class="mt-1 grid grid-cols-7 gap-y-1"></div>
                        <div class="mt-3 flex items-center gap-3 whitespace-nowrap px-1 text-[11px] text-body">
                            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-primary"></span> Available date</span>
                            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-[#d5deea]"></span> Unavailable date</span>
                        </div>
                    </div>

                    <div class="min-w-0">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <p class="text-sm font-semibold text-navy">Available time slots</p>
                            <p data-slot-date class="text-sm font-medium text-body"></p>
                        </div>
                        <p data-slot-hint class="flex min-h-24 items-center justify-center rounded-xl border border-dashed border-line bg-page px-4 py-6 text-center text-sm text-body">
                            Select a date to see the time slots this doctor saved.
                        </p>
                        <div data-slot-grid class="hidden max-h-[320px] grid-cols-1 gap-2.5 overflow-y-auto pr-1 sm:grid-cols-2"></div>
                        <p data-slot-empty class="hidden rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-4 py-3 text-sm leading-6 text-ink"></p>
                    </div>
                </div>

                <input id="appointment-date" data-booking-date type="hidden" name="appointment_date" value="{{ $field('appointment_date') }}">
                <p data-date-warning class="mt-3 hidden rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-4 py-3 text-sm leading-6 text-ink"></p>
            </section>

            <section class="rounded-2xl border border-line bg-white p-5 shadow-card sm:p-6">
                <div class="flex gap-3">
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">2</span>
                    <div>
                        <h2 class="text-base font-bold text-navy">Tell us about your visit</h2>
                        <p class="mt-0.5 text-sm text-body">Help the doctor understand your health concern.</p>
                    </div>
                </div>

                <div class="mt-5 space-y-4">
                    <div>
                        <label for="health-issues" class="mb-2 block text-sm font-semibold text-navy">What are your symptoms or reason for visit?</label>
                        <div class="relative">
                            <textarea
                                id="health-issues"
                                name="health_issues"
                                data-symptoms
                                rows="5"
                                maxlength="500"
                                placeholder="Briefly describe your symptoms or reason for visit..."
                                class="field min-h-[148px] resize-none pb-8"
                            >{{ $field('health_issues') }}</textarea>
                            <span data-char-count class="pointer-events-none absolute bottom-3 right-3 text-xs text-muted">0/500</span>
                        </div>
                    </div>

                    <div>
                        <label for="symptom-duration" class="mb-2 block text-sm font-semibold text-navy">How long have you had these symptoms?</label>
                        <select id="symptom-duration" name="symptom_duration" data-symptom-duration class="field">
                            <option value="">Select duration</option>
                            @foreach (['Less than a day', '1–3 days', '4–7 days', '1–2 weeks', '2–4 weeks', 'More than a month'] as $durationChoice)
                                <option value="{{ $durationChoice }}" @selected($field('symptom_duration') === $durationChoice)>{{ $durationChoice }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="current-medicines" class="mb-2 block text-sm font-semibold text-navy">Any current medicines? <span class="font-medium text-muted">(Optional)</span></label>
                        <input
                            id="current-medicines"
                            name="current_medicines"
                            data-medicines
                            type="text"
                            value="{{ $field('current_medicines') }}"
                            placeholder="E.g. Metformin 500mg, Amlodipine 5mg..."
                            class="field"
                        >
                        <p class="mt-2 text-xs text-muted">Mention any medicines you are currently taking.</p>
                    </div>
                </div>
            </section>
        </div>

        <input type="hidden" name="slot_day" data-slot-day value="{{ $field('slot_day') }}">
        <input type="hidden" name="starting_time" data-starting-time value="{{ $field('starting_time') }}">
        <input type="hidden" name="ending_time" data-ending-time value="{{ $field('ending_time') }}">

        <section class="rounded-2xl border border-line bg-white p-5 shadow-card sm:p-6">
            <div class="flex gap-3">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">3</span>
                <div>
                    <h2 class="text-base font-bold text-navy">Add medical reports (optional)</h2>
                    <p class="mt-0.5 text-sm text-body">Upload recent reports to help the doctor prepare before your visit.</p>
                    @if ($draftReports !== [])
                        <p class="mt-2 text-sm text-navy">Saved with this booking: {{ implode(', ', $draftReports) }}</p>
                    @endif
                </div>
            </div>

            <div class="mt-5 grid items-stretch gap-4 lg:grid-cols-[minmax(240px,0.85fr)_minmax(0,1.7fr)]">
                <div
                    data-drop-zone
                    class="flex min-h-[220px] flex-col items-center justify-center rounded-2xl border border-dashed border-[#c9daf8] bg-[#f7faff] px-6 py-8 text-center"
                >
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-white text-primary shadow-sm">
                        <x-ui.icon name="upload" class="h-6 w-6" />
                    </span>
                    <p class="mt-4 text-sm font-semibold text-navy">
                        Drop reports here or
                        <label for="other-report-files" class="cursor-pointer text-primary">browse</label>
                    </p>
                    <p class="mt-1 text-xs text-muted">Support PDF, JPG, PNG · Max 10 MB per file</p>
                    <div data-file-list="other" class="mt-4 w-full space-y-2 empty:hidden"></div>
                </div>

                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($reportGroups as $group)
                        <div class="flex min-h-[220px] flex-col rounded-2xl border border-line bg-white p-4">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl {{ $group['tone'] }}">
                                <x-ui.icon :name="$group['icon']" class="h-5 w-5" />
                            </span>
                            <h3 class="mt-3 text-sm font-semibold text-navy">{{ $group['name'] }}</h3>
                            <p class="mt-1 text-xs leading-5 text-muted">{{ $group['hint'] }}</p>
                            <label class="mt-3 block">
                                <span class="text-xs font-semibold text-navy">Report name</span>
                                <input
                                    type="text"
                                    name="{{ $group['key'] }}_report_name"
                                    value="{{ $field($group['key'].'_report_name') }}"
                                    maxlength="120"
                                    placeholder="{{ $group['key'] === 'other' ? 'MRI, ultrasound, prescription' : $group['name'] }}"
                                    class="field mt-1.5 text-xs"
                                >
                            </label>
                            @if ($group['key'] !== 'other')
                                <div data-file-list="{{ $group['key'] }}" class="mt-3 space-y-2 empty:hidden"></div>
                            @endif
                            <label class="mt-auto inline-flex h-10 w-full cursor-pointer items-center justify-center gap-1.5 rounded-xl border border-line bg-white text-sm font-semibold text-navy hover:bg-page" @if ($group['key'] === 'other') for="other-report-files" @endif>
                                <x-ui.icon name="plus" class="h-4 w-4 text-primary" />
                                Add files
                                @if ($group['key'] !== 'other')
                                    <input
                                        data-file-input="{{ $group['key'] }}"
                                        type="file"
                                        name="{{ $group['key'] }}_reports[]"
                                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                        multiple
                                        class="file-picker"
                                    >
                                @endif
                            </label>
                        </div>
                    @endforeach
                    <input
                        id="other-report-files"
                        data-file-input="other"
                        type="file"
                        name="other_reports[]"
                        accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                        multiple
                        class="file-picker"
                    >
                </div>
            </div>
        </section>

        <div class="rounded-2xl border border-line bg-white px-4 py-4 shadow-card sm:px-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-1 flex-col gap-4 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-light text-primary">
                            <x-ui.icon name="calendar" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-xs text-muted">Selected date</p>
                            <p data-summary-date class="text-sm font-bold text-navy">—</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-light text-primary">
                            <x-ui.icon name="clock" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-xs text-muted">Selected time</p>
                            <p data-summary-time class="text-sm font-bold text-navy">—</p>
                        </div>
                    </div>
                    <div class="hidden h-10 w-px bg-line sm:block"></div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-light text-primary">
                            <x-ui.icon name="file" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-xs text-muted">Consultation fee</p>
                            <p class="text-sm font-bold text-navy">{{ $fee }} USD</p>
                            <p class="text-xs text-muted">for {{ $duration }}-minute consultation</p>
                        </div>
                    </div>
                </div>

                <div class="lg:text-right">
                    <button type="submit" data-submit class="btn-primary w-full lg:w-auto lg:min-w-[230px]">
                        {{ $canBook ? 'Confirm appointment' : 'Continue to sign in' }}
                        <x-ui.icon name="arrow" class="h-4 w-4" />
                    </button>
                    @unless ($canBook)
                        <p class="mt-2 text-[11px] leading-4 text-muted lg:whitespace-nowrap">Your details will be saved so you can finish booking after signing in.</p>
                    @endunless
                </div>
            </div>
        </div>
    </form>
@endif

@once
    @push('scripts')
        <script type="application/json" id="booking-open-slots">@json($openSlots)</script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const form = document.querySelector('[data-booking-form]');
                if (!form) return;

                const readJson = (id, fallback) => {
                    const node = document.getElementById(id);
                    if (!node) return fallback;
                    try {
                        return JSON.parse(node.textContent || '');
                    } catch (error) {
                        return fallback;
                    }
                };

                const openSlots = readJson('booking-open-slots', {});
                const loggingIn = form.dataset.intent === 'login';
                const horizonStart = form.dataset.horizonStart;
                const horizonEnd = form.dataset.horizonEnd;
                const dateInput = form.querySelector('[data-booking-date]');
                const dayInput = form.querySelector('[data-slot-day]');
                const startInput = form.querySelector('[data-starting-time]');
                const endInput = form.querySelector('[data-ending-time]');
                const calLabel = form.querySelector('[data-cal-label]');
                const calGrid = form.querySelector('[data-cal-grid]');
                const slotGrid = form.querySelector('[data-slot-grid]');
                const slotHint = form.querySelector('[data-slot-hint]');
                const slotEmpty = form.querySelector('[data-slot-empty]');
                const slotDate = form.querySelector('[data-slot-date]');
                const summaryDate = form.querySelector('[data-summary-date]');
                const summaryTime = form.querySelector('[data-summary-time]');
                const dateWarning = form.querySelector('[data-date-warning]');
                const errorBox = form.querySelector('[data-booking-error]');
                const symptoms = form.querySelector('[data-symptoms]');
                const charCount = form.querySelector('[data-char-count]');
                const dropZone = form.querySelector('[data-drop-zone]');
                const weekdays = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                const shortDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const previous = { start: startInput.value, end: endInput.value };
                let restored = false;
                const files = { blood: [], xray: [], other: [] };
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const bookableDates = [];
                let firstBookable = today;
                let lastBookable = today;
                let view = dateInput.value ? new Date(`${dateInput.value}T00:00:00`) : new Date(today);

                const iso = (date) => {
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${date.getFullYear()}-${month}-${day}`;
                };

                const parseIso = (value) => new Date(`${value}T00:00:00`);

                const formatTime = (time) => {
                    const [hours, minutes] = String(time).split(':').map(Number);
                    const meridian = hours >= 12 ? 'PM' : 'AM';
                    const hour = hours % 12 || 12;
                    return `${hour}:${String(minutes).padStart(2, '0')} ${meridian}`;
                };

                const slotsForDate = (value) => {
                    if (!value || value < horizonStart || value > horizonEnd) return [];

                    return (openSlots[value] || []).map((slot) => ({
                        day: weekdays[parseIso(value).getDay()],
                        startingTime: slot.start,
                        endingTime: slot.end,
                    }));
                };

                const cursor = parseIso(horizonStart);
                const lastDay = parseIso(horizonEnd);
                while (cursor <= lastDay) {
                    const value = iso(cursor);
                    if (slotsForDate(value).length > 0) bookableDates.push(value);
                    cursor.setDate(cursor.getDate() + 1);
                }
                if (bookableDates.length > 0) {
                    firstBookable = parseIso(bookableDates[0]);
                    lastBookable = parseIso(bookableDates[bookableDates.length - 1]);
                }

                const prettyDate = (value) => {
                    const date = parseIso(value);
                    return `${shortDays[date.getDay()]}, ${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
                };

                const longDate = (value) => {
                    const date = parseIso(value);
                    const name = date.toLocaleDateString('en-US', { weekday: 'long' });
                    return `${name}, ${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
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
                    summaryTime.textContent = '—';
                };

                const slotClass = (active) => active
                    ? 'rounded-xl border border-primary bg-primary px-2 py-3 text-center text-sm font-semibold text-white shadow-sm'
                    : 'rounded-xl border border-[#e4edf7] bg-white px-2 py-3 text-center text-sm font-semibold text-navy transition hover:border-primary';

                const selectSlot = (button, slot) => {
                    slotGrid.querySelectorAll('button').forEach((item) => {
                        item.className = slotClass(false);
                    });
                    button.className = slotClass(true);
                    const picked = parseIso(dateInput.value);
                    dayInput.value = weekdays[picked.getDay()];
                    startInput.value = slot.startingTime;
                    endInput.value = slot.endingTime;
                    summaryDate.textContent = prettyDate(dateInput.value);
                    summaryTime.textContent = `${formatTime(slot.startingTime)} – ${formatTime(slot.endingTime)} ({{ $timezoneLabel }})`;
                    hideError();
                };

                const renderSlots = () => {
                    slotGrid.innerHTML = '';
                    slotEmpty.classList.add('hidden');
                    dateWarning.classList.add('hidden');
                    clearSlot();

                    if (!dateInput.value) {
                        slotHint.classList.remove('hidden');
                        slotGrid.classList.add('hidden');
                        slotGrid.classList.remove('grid');
                        slotDate.textContent = '';
                        summaryDate.textContent = '—';
                        return;
                    }

                    slotHint.classList.add('hidden');
                    slotDate.textContent = longDate(dateInput.value);
                    summaryDate.textContent = prettyDate(dateInput.value);

                    const times = slotsForDate(dateInput.value);
                    if (times.length === 0) {
                        slotGrid.classList.add('hidden');
                        slotGrid.classList.remove('grid');
                        slotEmpty.textContent = 'No open times on this date. Please try another date.';
                        slotEmpty.classList.remove('hidden');
                        dateWarning.textContent = 'This doctor does not take appointments on that day. Choose a date marked in blue.';
                        dateWarning.classList.remove('hidden');
                        return;
                    }

                    slotGrid.classList.remove('hidden');
                    slotGrid.classList.add('grid');

                    times.forEach((slot) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = slotClass(false);
                        button.textContent = `${formatTime(slot.startingTime)} – ${formatTime(slot.endingTime)}`;
                        button.addEventListener('click', () => selectSlot(button, slot));
                        slotGrid.appendChild(button);

                        if (!restored && previous.start === slot.startingTime && previous.end === slot.endingTime) {
                            restored = true;
                            selectSlot(button, slot);
                        }
                    });
                };

                const dayButton = (label, muted, date, available) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    const selected = date && date === dateInput.value;
                    button.className = 'flex h-10 flex-col items-center justify-center';
                    button.disabled = !available;
                    const number = document.createElement('span');
                    number.className = 'flex h-8 w-8 items-center justify-center rounded-full text-sm';
                    if (selected) {
                        number.className += ' bg-primary font-semibold text-white';
                    } else if (available) {
                        number.className += ' font-medium text-navy';
                    } else {
                        number.className += muted ? ' text-[#d5deea]' : ' text-[#b7c3d4]';
                    }
                    number.textContent = label;
                    button.appendChild(number);
                    if (available && !selected) {
                        const dot = document.createElement('span');
                        dot.className = 'mt-0.5 h-1 w-1 rounded-full bg-primary';
                        button.appendChild(dot);
                    }
                    if (available) {
                        button.addEventListener('click', () => {
                            dateInput.value = date;
                            renderCalendar();
                            renderSlots();
                        });
                    }
                    return button;
                };

                const renderCalendar = () => {
                    const year = view.getFullYear();
                    const month = view.getMonth();
                    calLabel.textContent = view.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
                    calGrid.innerHTML = '';
                    const first = new Date(year, month, 1);
                    const startPad = first.getDay();
                    const daysInMonth = new Date(year, month + 1, 0).getDate();
                    const prevDays = new Date(year, month, 0).getDate();

                    for (let index = startPad - 1; index >= 0; index -= 1) {
                        calGrid.appendChild(dayButton(String(prevDays - index), true, null, false));
                    }

                    for (let day = 1; day <= daysInMonth; day += 1) {
                        const date = iso(new Date(year, month, day));
                        const available = slotsForDate(date).length > 0;
                        calGrid.appendChild(dayButton(String(day), false, date, available));
                    }

                    const used = startPad + daysInMonth;
                    const trailing = (7 - (used % 7)) % 7;
                    for (let day = 1; day <= trailing; day += 1) {
                        calGrid.appendChild(dayButton(String(day), true, null, false));
                    }

                    const prev = form.querySelector('[data-cal-prev]');
                    const next = form.querySelector('[data-cal-next]');
                    const monthIndex = (date) => date.getFullYear() * 12 + date.getMonth();
                    const viewKey = monthIndex(view);
                    prev.disabled = viewKey <= Math.min(monthIndex(today), monthIndex(firstBookable));
                    next.disabled = viewKey >= monthIndex(lastBookable);
                    prev.classList.toggle('opacity-40', prev.disabled);
                    next.classList.toggle('opacity-40', next.disabled);
                };

                form.querySelector('[data-cal-prev]').addEventListener('click', () => {
                    view = new Date(view.getFullYear(), view.getMonth() - 1, 1);
                    renderCalendar();
                });
                form.querySelector('[data-cal-next]').addEventListener('click', () => {
                    view = new Date(view.getFullYear(), view.getMonth() + 1, 1);
                    renderCalendar();
                });

                const renderFiles = (key) => {
                    const list = form.querySelector(`[data-file-list="${key}"]`);
                    const input = form.querySelector(`[data-file-input="${key}"]`);
                    if (!list || !input) return;
                    list.innerHTML = '';

                    files[key].forEach((file, index) => {
                        const row = document.createElement('div');
                        row.className = 'flex items-center justify-between gap-2 rounded-lg bg-white px-2 py-1.5 text-left text-xs';
                        const name = document.createElement('span');
                        name.className = 'truncate font-medium text-ink';
                        name.textContent = file.name;
                        const remove = document.createElement('button');
                        remove.type = 'button';
                        remove.className = 'shrink-0 font-semibold text-danger';
                        remove.textContent = 'Remove';
                        remove.addEventListener('click', () => {
                            files[key].splice(index, 1);
                            renderFiles(key);
                        });
                        row.append(name, remove);
                        list.appendChild(row);
                    });
                };

                const addFiles = (key, list) => {
                    const incoming = Array.from(list || []);
                    const tooLarge = incoming.find((file) => file.size > 10 * 1024 * 1024);
                    if (tooLarge) {
                        showError('Each report must be 10 MB or smaller.');
                        return;
                    }
                    files[key].push(...incoming);
                    if (files[key].length > 8) {
                        files[key] = files[key].slice(0, 8);
                        showError('You can attach up to 8 files in each group.');
                    } else {
                        hideError();
                    }
                    renderFiles(key);
                };

                const syncFiles = (key) => {
                    const input = form.querySelector(`[data-file-input="${key}"]`);
                    if (!input || typeof DataTransfer === 'undefined') return;
                    const transfer = new DataTransfer();
                    files[key].forEach((file) => transfer.items.add(file));
                    input.dataset.syncing = '1';
                    input.files = transfer.files;
                };

                form.querySelectorAll('[data-file-input]').forEach((input) => {
                    input.addEventListener('change', () => {
                        if (input.dataset.syncing === '1') {
                            input.dataset.syncing = '0';
                            return;
                        }
                        addFiles(input.dataset.fileInput, input.files);
                    });
                });

                if (dropZone) {
                    dropZone.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        dropZone.classList.add('border-primary', 'bg-light');
                    });
                    dropZone.addEventListener('dragleave', () => {
                        dropZone.classList.remove('border-primary', 'bg-light');
                    });
                    dropZone.addEventListener('drop', (event) => {
                        event.preventDefault();
                        dropZone.classList.remove('border-primary', 'bg-light');
                        addFiles('other', event.dataTransfer.files);
                    });
                }

                const updateCount = () => {
                    charCount.textContent = `${symptoms.value.length}/500`;
                };
                symptoms.addEventListener('input', updateCount);
                updateCount();

                renderCalendar();
                renderSlots();

                form.addEventListener('submit', (event) => {
                    if (!dateInput.value || !startInput.value || !endInput.value) {
                        event.preventDefault();
                        showError('Choose an appointment date and an open time slot.');
                        return;
                    }

                    Object.keys(files).forEach(syncFiles);

                    const button = form.querySelector('[data-submit]');
                    button.disabled = true;
                    button.textContent = loggingIn ? 'Opening sign in...' : 'Processing your booking...';
                });
            });
        </script>
    @endpush
@endonce
