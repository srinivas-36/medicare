@php
    $dayOrder = [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ];

    $grouped = [];

    foreach (array_keys($dayOrder) as $key) {
        $grouped[$key] = [];
    }

    foreach ($timeSlots as $index => $slot) {
        $day = strtolower((string) data_get($slot, 'day'));

        if (! array_key_exists($day, $grouped)) {
            continue;
        }

        $start = substr((string) data_get($slot, 'startingTime'), 0, 5);
        $end = substr((string) data_get($slot, 'endingTime'), 0, 5);

        if (! preg_match('/^\d{2}:\d{2}$/', $start) || ! preg_match('/^\d{2}:\d{2}$/', $end)) {
            continue;
        }

        $grouped[$day][] = [
            'start' => $start,
            'end' => $end,
            'available' => filter_var(data_get($slot, 'isAvailable', true), FILTER_VALIDATE_BOOLEAN),
            'startError' => $errors->first('timeSlots.'.$index.'.startingTime'),
            'endError' => $errors->first('timeSlots.'.$index.'.endingTime'),
        ];
    }

    $days = [];
    $fallbackSlots = [];

    foreach ($dayOrder as $key => $label) {
        $rows = $grouped[$key];
        $openRows = array_values(array_filter($rows, fn (array $row) => $row['available']));
        $closedRows = array_values(array_filter($rows, fn (array $row) => ! $row['available']));
        $enabled = $openRows !== [];
        $ranges = $enabled ? $openRows : ($closedRows !== [] ? $closedRows : [[
            'start' => '09:00',
            'end' => '13:00',
            'available' => false,
            'startError' => '',
            'endError' => '',
        ]]);

        $days[] = [
            'key' => $key,
            'label' => $label,
            'enabled' => $enabled,
            'ranges' => array_map(fn (array $row) => [
                'start' => $row['start'],
                'end' => $row['end'],
                'startError' => $row['startError'],
                'endError' => $row['endError'],
            ], $ranges),
        ];

        if ($enabled) {
            foreach ($ranges as $row) {
                $fallbackSlots[] = [
                    'day' => $key,
                    'start' => $row['start'],
                    'end' => $row['end'],
                ];
            }
        }
    }

    $scheduleConfig = [
        'days' => $days,
    ];

    $durationChoices = [15, 20, 30, 45, 60, 90];
    $bufferChoices = [0, 5, 10, 15, 20, 30];

    if (! in_array($appointmentDuration, $durationChoices, true)) {
        $durationChoices[] = $appointmentDuration;
        sort($durationChoices);
    }

    if (! in_array($appointmentBuffer, $bufferChoices, true)) {
        $bufferChoices[] = $appointmentBuffer;
        sort($bufferChoices);
    }

    $selectClass = 'h-11 w-full appearance-none rounded-lg border border-[#E6EDF5] bg-white px-3 pr-9 text-sm font-medium text-[#0B1F3A] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';
    $timezoneLabel = \App\Services\AppointmentBooking::clinicNow()->getTimezone()->getName();
@endphp

<div class="space-y-4">
    <section
        x-data="weeklySchedule(@js($scheduleConfig))"
        x-cloak
        @class([
            'rounded-xl border border-line bg-white p-4 shadow-card sm:p-5' => ! ($asPage ?? false),
        ])
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                @if ($asPage ?? false)
                    <h1 class="text-xl font-semibold tracking-tight text-[#181A1E] sm:text-[22px]">Availability</h1>
                    <p class="mt-1 text-sm text-[#4E545F]">Set your working hours and manage time slots.</p>
                    <h2 class="sr-only">Weekly schedule</h2>
                @else
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-muted">Availability &amp; working hours</p>
                    <h2 class="mt-1 text-base font-semibold text-heading">Weekly schedule</h2>
                    <p class="mt-1 text-sm text-body">Turn a day on and set the periods patients can book. Each saved period is one time slot. Copy a day when the hours match.</p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex h-10 items-center gap-2 rounded-lg border border-[#E6EDF5] bg-white px-3 text-sm font-medium text-[#53647D]">
                    <svg class="h-4 w-4 text-[#7C8BA1]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M3 12h18M12 3c2.5 2.8 3.8 5.8 3.8 9s-1.3 6.2-3.8 9c-2.5-2.8-3.8-5.8-3.8-9S9.5 5.8 12 3Z" stroke-linejoin="round" />
                    </svg>
                    Time zone: {{ $timezoneLabel }}
                </span>
                <button
                    type="button"
                    x-on:click="copyAll()"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-[#E6EDF5] bg-white px-3 text-sm font-semibold text-[#0B1F3A] hover:bg-[#F4F7FB]"
                >
                    <svg class="h-4 w-4 text-[#53647D]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <rect x="8" y="8" width="12" height="12" rx="2" />
                        <path d="M4 16V6a2 2 0 0 1 2-2h10" stroke-linecap="round" />
                    </svg>
                    {{ ($asPage ?? false) ? 'Copy to all days' : 'Copy hours to all days' }}
                </button>
            </div>
        </div>
        <p x-show="notice" x-text="notice" x-cloak class="mt-3 text-sm font-medium text-[#1769FF]"></p>

        <div @class(['mt-4 rounded-xl border border-[#E2E8F0]', 'bg-white shadow-card' => $asPage ?? false])>
            <div class="hidden grid-cols-[11.5rem_minmax(0,1fr)] border-b border-[#E2E8F0] bg-[#F8FAFC] px-4 py-2.5 text-xs font-semibold uppercase tracking-[0.08em] text-[#64748B] sm:grid">
                <span>Day</span>
                <span>Working hours</span>
            </div>
            <template x-for="day in days" :key="day.key">
                <div class="border-b border-[#E2E8F0] last:border-b-0">
                    <div class="grid gap-3 px-4 py-3.5 sm:grid-cols-[11.5rem_minmax(0,1fr)] sm:items-start sm:gap-4">
                        <div class="flex items-center justify-between gap-3 sm:pt-1.5">
                            <span class="text-sm font-semibold" :class="day.enabled ? 'text-[#0B1F3A]' : 'text-[#94A3B8]'" x-text="day.label"></span>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="day.enabled ? 'true' : 'false'"
                                :aria-label="'Toggle ' + day.label"
                                x-on:click="toggle(day)"
                                class="relative h-6 w-11 shrink-0 rounded-full transition"
                                :class="day.enabled ? 'bg-[#1769FF]' : 'bg-[#D7DEE8]'"
                            >
                                <span class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white shadow transition" :class="day.enabled ? 'translate-x-5' : 'translate-x-0'"></span>
                            </button>
                        </div>

                        <div class="min-w-0 space-y-2" x-show="day.enabled">
                            <template x-for="(range, rangeIndex) in day.ranges" :key="day.key + '-' + rangeIndex">
                                <div class="flex flex-wrap items-center gap-2">
                                    <label class="block w-full shrink-0 sm:w-48">
                                        <span class="sr-only" x-text="day.label + ' starting time'"></span>
                                        <input
                                            type="time"
                                            step="60"
                                            x-model="range.start"
                                            x-on:change="touch(range)"
                                            class="schedule-time h-11 w-full rounded-lg border bg-white px-3 text-sm font-medium text-[#0B1F3A] focus:outline-none focus:ring-2"
                                            :class="range.startError ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-[#E6EDF5] focus:border-[#1769FF] focus:ring-[#1769FF]/20'"
                                            :aria-label="day.label + ' starting time'"
                                        >
                                    </label>
                                    <span class="shrink-0 text-sm text-[#94A3B8]">to</span>
                                    <label class="block w-full shrink-0 sm:w-48">
                                        <span class="sr-only" x-text="day.label + ' ending time'"></span>
                                        <input
                                            type="time"
                                            step="60"
                                            x-model="range.end"
                                            x-on:change="touch(range)"
                                            class="schedule-time h-11 w-full rounded-lg border bg-white px-3 text-sm font-medium text-[#0B1F3A] focus:outline-none focus:ring-2"
                                            :class="range.endError ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20' : 'border-[#E6EDF5] focus:border-[#1769FF] focus:ring-[#1769FF]/20'"
                                            :aria-label="day.label + ' ending time'"
                                        >
                                    </label>
                                    <button
                                        type="button"
                                        x-show="day.ranges.length > 1"
                                        x-on:click="removeRange(day, rangeIndex)"
                                        class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-transparent text-[#94A3B8] hover:border-[#E6EDF5] hover:bg-[#F7F9FC] hover:text-red-600"
                                        :aria-label="'Remove ' + day.label + ' hours'"
                                    >
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M6 6l12 12M18 6 6 18" stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <button
                                type="button"
                                x-on:click="addHours(day)"
                                class="inline-flex items-center gap-1.5 px-0.5 pt-0.5 text-sm font-semibold text-[#1769FF] hover:text-[#0D57E5]"
                            >
                                <span class="inline-flex h-[18px] w-[18px] items-center justify-center rounded-full border border-current">
                                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                        <path d="M12 5v14M5 12h14" stroke-linecap="round" />
                                    </svg>
                                </span>
                                Add hours
                            </button>
                        </div>

                        <div class="flex h-11 items-center" x-show="!day.enabled" x-cloak>
                            <span class="text-sm text-[#94A3B8]">Unavailable</span>
                        </div>
                    </div>
                    <p
                        x-show="dayError(day)"
                        x-text="dayError(day)"
                        data-day-error
                        class="px-4 pb-3 text-xs font-medium text-red-600 sm:pl-[13.5rem]"
                    ></p>
                </div>
            </template>
        </div>

        <div data-schedule-fields></div>
        <div data-schedule-fallback>
            @foreach ($fallbackSlots as $index => $slot)
                <input type="hidden" name="timeSlots[{{ $index }}][day]" value="{{ $slot['day'] }}">
                <input type="hidden" name="timeSlots[{{ $index }}][startingTime]" value="{{ $slot['start'] }}">
                <input type="hidden" name="timeSlots[{{ $index }}][endingTime]" value="{{ $slot['end'] }}">
                <input type="hidden" name="timeSlots[{{ $index }}][isAvailable]" value="1">
            @endforeach
        </div>
    </section>

    <section class="rounded-xl border border-line bg-white p-4 shadow-card sm:p-5">
        <h3 class="text-lg font-bold text-[#0B1F3A]">Appointment settings</h3>
        <p class="mt-1 text-sm leading-6 text-[#7C8BA1]">Configure appointment duration and buffer time between appointments.</p>

        <div class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.15fr)] lg:items-end">
            <div>
                <label for="appointment-duration" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Appointment duration</label>
                <div class="relative">
                    <select id="appointment-duration" name="appointmentDuration" class="{{ $selectClass }}" style="background-image: none">
                        @foreach ($durationChoices as $minutes)
                            <option value="{{ $minutes }}" @selected($appointmentDuration === $minutes)>{{ $minutes }} minutes</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                @error('appointmentDuration')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="appointment-buffer" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Buffer between appointments</label>
                <div class="relative">
                    <select id="appointment-buffer" name="appointmentBuffer" class="{{ $selectClass }}" style="background-image: none">
                        @foreach ($bufferChoices as $minutes)
                            <option value="{{ $minutes }}" @selected($appointmentBuffer === $minutes)>{{ $minutes }} minutes</option>
                        @endforeach
                    </select>
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#94A3B8]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </span>
                </div>
                @error('appointmentBuffer')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-start gap-3 rounded-xl bg-[#F3F8FF] px-4 py-3 text-sm leading-5 text-[#3D5270]">
                <span class="mt-0.5 inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-[#1769FF] text-[11px] font-bold text-white" aria-hidden="true">i</span>
                <p>Patients can book only within your available hours.</p>
            </div>
        </div>
    </section>
</div>

@push('scripts')
    <script>
        window.weeklySchedule = function (config) {
            return {
                days: config.days,
                notice: '',
                init() {
                    this.$root.querySelector('[data-schedule-fallback]')?.remove();
                    this.writeFields();
                    this.$root.closest('form')?.addEventListener('submit', (event) => {
                        if (!this.validate()) {
                            event.preventDefault();
                            event.stopPropagation();
                            this.$nextTick(() => {
                                this.$root.querySelector('[data-day-error]')?.scrollIntoView({ block: 'center' });
                            });
                            return;
                        }

                        this.writeFields();
                    }, true);
                },
                toggle(day) {
                    day.enabled = !day.enabled;
                    this.notice = '';
                },
                addHours(day) {
                    const last = day.ranges[day.ranges.length - 1];
                    const lastStart = this.toMin(last.start);
                    const lastEnd = this.toMin(last.end);

                    if (lastStart === null || lastEnd === null || lastEnd <= lastStart) {
                        this.notice = 'Set this period before adding another.';
                        return;
                    }

                    const length = Math.max(60, lastEnd - lastStart);
                    const startMin = lastEnd + 60;
                    const latest = (23 * 60) + 59;

                    if (startMin >= latest) {
                        this.notice = 'No later hours left on this day.';
                        return;
                    }

                    const start = this.fromMin(startMin);
                    const end = this.fromMin(Math.min(startMin + length, latest));

                    if (this.toMin(end) <= this.toMin(start)) {
                        this.notice = 'No later hours left on this day.';
                        return;
                    }

                    day.ranges.push({ start, end, startError: '', endError: '' });
                    this.notice = '';
                },
                removeRange(day, index) {
                    if (day.ranges.length < 2) {
                        return;
                    }

                    day.ranges.splice(index, 1);
                    this.notice = '';
                },
                copyAll() {
                    const source = this.days.find((day) => day.enabled && day.ranges.length);

                    if (!source) {
                        this.notice = 'Turn on a day and set its hours first.';
                        return;
                    }

                    this.days.forEach((day) => {
                        if (day.key === source.key) {
                            return;
                        }

                        day.enabled = true;
                        day.ranges = source.ranges.map((range) => ({
                            start: range.start,
                            end: range.end,
                            startError: '',
                            endError: '',
                        }));
                    });
                    this.notice = source.label + "'s hours were copied to every day.";
                },
                touch(range) {
                    range.startError = '';
                    range.endError = '';
                },
                dayError(day) {
                    for (const range of day.ranges) {
                        if (range.startError) {
                            return range.startError;
                        }

                        if (range.endError) {
                            return range.endError;
                        }
                    }

                    return '';
                },
                validate() {
                    let ok = true;

                    this.days.forEach((day) => {
                        day.ranges.forEach((range) => {
                            range.startError = '';
                            range.endError = '';
                        });

                        if (!day.enabled) {
                            return;
                        }

                        const ordered = day.ranges
                            .map((range) => ({ range, start: this.toMin(range.start), end: this.toMin(range.end) }))
                            .sort((a, b) => a.start - b.start);
                        let previousEnd = null;

                        ordered.forEach((item) => {
                            if (item.start === null || item.end === null || item.end <= item.start) {
                                item.range.endError = 'The ending time must be after the starting time.';
                                ok = false;
                                return;
                            }

                            if (previousEnd !== null && item.start < previousEnd) {
                                item.range.startError = 'This range overlaps another range on the same day.';
                                ok = false;
                            }

                            if (previousEnd === null || item.end > previousEnd) {
                                previousEnd = item.end;
                            }
                        });
                    });

                    return ok;
                },
                slots() {
                    const rows = [];

                    this.days.forEach((day) => {
                        if (!day.enabled) {
                            return;
                        }

                        day.ranges.forEach((range) => {
                            rows.push({ day: day.key, start: range.start, end: range.end });
                        });
                    });

                    return rows;
                },
                writeFields() {
                    const holder = this.$root.querySelector('[data-schedule-fields]');

                    if (!holder) {
                        return;
                    }

                    holder.replaceChildren();

                    this.slots().forEach((slot, index) => {
                        const values = {
                            day: slot.day,
                            startingTime: String(slot.start).slice(0, 5),
                            endingTime: String(slot.end).slice(0, 5),
                            isAvailable: '1',
                        };

                        Object.entries(values).forEach(([name, value]) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `timeSlots[${index}][${name}]`;
                            input.value = value;
                            holder.appendChild(input);
                        });
                    });
                },
                toMin(value) {
                    const match = String(value || '').match(/^(\d{2}):(\d{2})/);

                    if (!match) {
                        return null;
                    }

                    return (Number(match[1]) * 60) + Number(match[2]);
                },
                fromMin(mins) {
                    const clamped = Math.max(0, Math.min(mins, (23 * 60) + 59));
                    const hour = String(Math.floor(clamped / 60)).padStart(2, '0');
                    const minute = String(clamped % 60).padStart(2, '0');

                    return hour + ':' + minute;
                },
            };
        };
    </script>
@endpush
