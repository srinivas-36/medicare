<div class="mt-5 grid gap-4 lg:grid-cols-[268px_minmax(0,1fr)]">
    <div class="rounded-2xl border border-line bg-white p-3">
        <div class="flex items-center justify-between px-1">
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-body hover:bg-page disabled:text-[#d5deea]" @click="shiftMonth(-1)" :disabled="!canShift(-1)" aria-label="Previous month">
                <x-ui.icon name="chevron" class="h-4 w-4 rotate-90" />
            </button>
            <p class="text-sm font-semibold text-navy" x-text="monthTitle()"></p>
            <button type="button" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-body hover:bg-page disabled:text-[#d5deea]" @click="shiftMonth(1)" :disabled="!canShift(1)" aria-label="Next month">
                <x-ui.icon name="chevron" class="h-4 w-4 -rotate-90" />
            </button>
        </div>
        <div class="mt-3 grid grid-cols-7 gap-y-1 text-center text-[11px] font-medium text-muted">
            <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
        </div>
        <div class="mt-1 grid grid-cols-7 gap-y-1">
            <template x-for="(day, index) in calendarCells()" :key="day ? day.date : 'pad-' + index">
                <button
                    type="button"
                    class="mx-auto flex h-10 w-10 items-center justify-center rounded-full text-sm font-medium disabled:cursor-default"
                    :disabled="!day || !day.open"
                    @click="selectDate(day.date)"
                    :class="!day ? 'invisible' : (date === day.date ? 'bg-primary font-semibold text-white' : (day.open ? 'text-navy hover:bg-light' : 'text-[#b7c3d4]'))"
                    x-text="day ? day.label : ''"
                ></button>
            </template>
        </div>
        <div class="mt-3 flex items-center gap-3 px-1 text-[11px] text-body">
            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-primary"></span> Open</span>
            <span class="inline-flex items-center gap-1.5"><span class="h-1.5 w-1.5 rounded-full bg-[#d5deea]"></span> Closed</span>
        </div>
    </div>

    <div class="min-w-0">
        <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-sm font-semibold text-navy">Available time slots</p>
            <p class="text-sm font-medium text-text" x-text="date === '' ? '' : prettyDate(date)"></p>
        </div>
        <p x-show="date === ''" class="flex min-h-[220px] items-center justify-center rounded-xl border border-dashed border-line bg-page px-4 text-center text-sm text-text">
            This doctor has no saved time slots in the booking window.
        </p>
        <p x-show="date !== '' && exactSlots().length === 0" class="rounded-xl border border-[#f3e2b3] bg-[#fff9ea] px-4 py-3 text-sm leading-6 text-ink">
            No saved time slot is open on this date. Choose another open day.
        </p>
        <div x-show="exactSlots().length > 0" class="grid gap-2 sm:grid-cols-2">
            <template x-for="item in exactSlots()" :key="item.value">
                <button
                    type="button"
                    @click="slot = item.value"
                    class="rounded-xl border px-4 py-3 text-left transition"
                    :class="slot === item.value ? 'border-primary bg-primary text-white' : 'border-line bg-white text-navy hover:border-primary'"
                    :aria-pressed="slot === item.value ? 'true' : 'false'"
                >
                    <span class="block text-[11px] font-semibold uppercase tracking-[0.12em]" :class="slot === item.value ? 'text-white/80' : 'text-primary'" x-text="item.period"></span>
                    <span class="mt-1 block text-sm font-semibold" x-text="item.range"></span>
                </button>
            </template>
        </div>
        <p class="mt-3 text-xs leading-5 text-muted">Only this doctor’s saved weekly hours are shown. A slot disappears after it is booked.</p>
    </div>
</div>
