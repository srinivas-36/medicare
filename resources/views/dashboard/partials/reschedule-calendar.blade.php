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
    class="mt-3"
>
    @csrf
    <input type="hidden" name="slot" data-reschedule-slot value="{{ old('slot') }}">
    <div class="grid items-start gap-3 lg:grid-cols-[220px_minmax(0,1fr)]">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Available dates</p>
            <p data-reschedule-date class="mt-1 text-sm font-semibold text-[#15803D]">Current booking</p>
            <div data-reschedule-calendar class="mt-2"></div>
            <p class="mt-2 flex items-center gap-2 text-xs text-body">
                <span class="inline-block h-2.5 w-2.5 rounded-full bg-[#16A34A]" aria-hidden="true"></span>
                Existing booking
            </p>
        </div>
        <div class="rounded-xl border border-line bg-page px-3 py-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Available time slots</p>
            <div data-reschedule-slots class="mt-2 flex flex-wrap gap-2"></div>
            <p data-reschedule-note class="mt-2 text-sm text-body">Choose a day after the booked date.</p>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:items-end">
                <label class="block min-w-0 flex-1 text-sm font-medium text-heading">Reason
                    <input name="reason" value="{{ old('reason') }}" class="field mt-1.5" maxlength="255" placeholder="Optional note">
                </label>
                <button type="submit" class="btn-primary shrink-0">Reschedule</button>
            </div>
        </div>
    </div>
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
                        'h-8 rounded-md text-xs font-semibold',
                        isBooked
                            ? 'bg-[#16A34A] text-white'
                            : (selected
                                ? 'bg-[#0067FF] text-white'
                                : (available ? 'bg-[#E8F2FF] text-[#0067FF] hover:ring-1 hover:ring-[#0067FF]' : 'cursor-not-allowed text-[#CBD5E1] line-through')),
                    ].join(' ');
                    const title = isBooked ? ' title="Existing booking"' : '';
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
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
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
                    note.textContent = 'Choose a day after the booked date.';
                    return;
                }

                const current = document.createElement('button');
                current.type = 'button';
                current.disabled = true;
                current.textContent = bookedLabel;
                current.className = 'inline-flex items-center rounded-lg border border-[#16A34A] bg-[#16A34A] px-3 py-1.5 text-sm font-semibold text-white';
                slotList.appendChild(current);
                note.textContent = 'This is the current booking. Choose a later day to reschedule.';
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
                        ? 'inline-flex items-center rounded-lg border border-[#0067FF] bg-[#0067FF] px-3 py-1.5 text-sm font-semibold text-white'
                        : 'inline-flex items-center rounded-lg border border-[#E2E8F0] bg-white px-3 py-1.5 text-sm font-semibold text-[#181A1E] hover:border-[#0067FF]';
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
