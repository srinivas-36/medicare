@php
    $openSlots = $openSlots ?? [];
    $bookedDate = $bookedDate ?? '';
    $bookedLabel = $bookedLabel ?? 'Current booking';
@endphp

<form
    method="POST"
    action="{{ $action }}"
    data-reschedule
    data-booked-date="{{ $bookedDate }}"
    data-booked-label="{{ $bookedLabel }}"
    class="mt-4"
>
    @csrf
    <input type="hidden" name="slot" data-reschedule-slot value="{{ old('slot') }}">
    <p data-reschedule-date class="sr-only">Current booking</p>
    <div class="grid items-start gap-5 lg:grid-cols-[232px_minmax(0,1fr)]">
        <div>
            <div data-reschedule-calendar></div>
            <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-body">
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-[#0067FF]" aria-hidden="true"></span>
                    Selected date
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-block h-2.5 w-2.5 rounded-full bg-[#16A34A]" aria-hidden="true"></span>
                    Existing booking
                </span>
            </p>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-semibold text-heading">Available Time Slots</p>
            <div data-reschedule-slots class="mt-2 flex max-h-72 flex-col gap-2 overflow-y-auto"></div>
            <p data-reschedule-note class="mt-2 text-sm text-body">Choose a day after the booked date.</p>
        </div>
    </div>
    <button type="submit" class="mt-4 inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-[#0067FF] text-sm font-semibold text-white hover:bg-[#0056D6]">
        <x-ui.icon name="calendar" class="h-4 w-4" />
        Reschedule appointment
    </button>
</form>
<script type="application/json" id="reschedule-open-slots">@json($openSlots)</script>

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
                const startPad = first.getDay();
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
                        'mx-auto flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold',
                        isBooked
                            ? 'bg-[#16A34A] text-white'
                            : (selected
                                ? 'bg-[#0067FF] text-white'
                                : (available ? 'text-[#181A1E] hover:bg-[#E8F2FF]' : 'cursor-not-allowed text-[#CBD5E1]')),
                    ].join(' ');
                    const title = isBooked ? ' title="Existing booking"' : '';
                    cells.push(`<button type="button" class="${classes}" data-day="${value}" data-booked="${isBooked ? '1' : '0'}" ${available || isBooked ? '' : 'disabled'}${title}>${day}</button>`);
                }

                calendar.innerHTML = `
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <button type="button" data-month="-1" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-[#64748B] hover:bg-[#F8FAFC]" aria-label="Previous month">‹</button>
                            <p class="text-sm font-semibold text-[#181A1E]">${label}</p>
                            <button type="button" data-month="1" class="inline-flex h-7 w-7 items-center justify-center rounded-md text-[#64748B] hover:bg-[#F8FAFC]" aria-label="Next month">›</button>
                        </div>
                        <div class="grid grid-cols-7 text-center text-[10px] font-semibold text-[#94A3B8]">
                            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                        </div>
                        <div class="mt-1 grid grid-cols-7 gap-y-1">${cells.join('')}</div>
                    </div>
                `;
            };

            const slotCard = (label, caption, tone, disabled) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.disabled = disabled;
                const tones = {
                    current: 'border-[#BFDBFE] bg-[#EFF6FF]',
                    available: 'border-[#BBF7D0] bg-white hover:border-[#16A34A]',
                    selected: 'border-[#0067FF] bg-[#0067FF]',
                };
                const captions = {
                    current: 'text-[#0067FF]',
                    available: 'text-[#16A34A]',
                    selected: 'text-white/90',
                };
                const titles = {
                    current: 'text-[#181A1E]',
                    available: 'text-[#181A1E]',
                    selected: 'text-white',
                };
                button.className = `flex w-full items-center justify-between gap-3 rounded-lg border px-3 py-2.5 text-left ${tones[tone]} ${disabled ? 'cursor-default' : ''}`;
                const time = document.createElement('span');
                time.className = `whitespace-nowrap text-sm font-semibold ${titles[tone]}`;
                time.textContent = label;
                const detail = document.createElement('span');
                detail.className = `shrink-0 text-xs font-semibold ${captions[tone]}`;
                detail.textContent = caption;
                button.append(time, detail);
                return button;
            };

            const paintCurrentBooking = () => {
                slotList.innerHTML = '';
                if (pickedDate) {
                    pickedDate.textContent = formatDate(bookedDate);
                }
                slotInput.value = '';
                selectedStart = '';

                if (!bookedDate) {
                    note.textContent = 'Choose a day after the booked date.';
                    return;
                }

                slotList.appendChild(slotCard(bookedLabel, 'Current booking', 'current', true));
                note.textContent = 'This is the current booking. Choose a later day to reschedule.';
            };

            const paintSlots = () => {
                slotList.innerHTML = '';
                const times = (selectedDate && dates.includes(selectedDate) ? open[selectedDate] : null) || [];

                if (!selectedDate || times.length === 0) {
                    paintCurrentBooking();
                    return;
                }

                if (pickedDate) {
                    pickedDate.textContent = formatDate(selectedDate);
                }
                note.textContent = `${times.length} open ${times.length === 1 ? 'time' : 'times'} on this day.`;
                let matched = false;
                times.forEach((slot) => {
                    const active = selectedStart === slot.start;
                    if (active) matched = true;
                    const button = slotCard(
                        `${formatTime(slot.start)} – ${formatTime(slot.end)}`,
                        active ? 'Selected' : 'Available',
                        active ? 'selected' : 'available',
                        false,
                    );
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
                    note.textContent = 'Choose a day after the booked date, then pick a time.';
                }
            });

            paintCalendar();
            paintSlots();
        });
    </script>
@endpush
