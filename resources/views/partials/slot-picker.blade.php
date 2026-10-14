{{--
    Date, then the doctor's saved time_slots for that date.
    Submits a hidden "slot" value of Y-m-d|H:i.
--}}
@php
    $openSlots = $openSlots ?? [];
    $minDate = $minDate ?? \App\Services\AppointmentBooking::clinicNow()->toDateString();
    $maxDate = $maxDate ?? \App\Services\AppointmentBooking::bookableUntil()->toDateString();
    $selectedDate = old('date', '');
    $selectedSlot = old('slot', '');
@endphp

<div
    x-data="{
        date: @js($selectedDate),
        selected: @js($selectedSlot),
        slots: @js($openSlots),
        label(value) {
            const [hourText, minute] = String(value).split(':');
            const hour = Number(hourText);
            const suffix = hour >= 12 ? 'PM' : 'AM';
            const hour12 = hour % 12 || 12;
            return hour12 + ':' + String(minute).padStart(2, '0') + ' ' + suffix;
        },
        options() {
            return this.slots[this.date] || [];
        },
        choose(slot) {
            this.selected = this.date + '|' + slot.start;
        },
    }"
    class="space-y-3"
>
    @if ($errors->hasAny(['date', 'time', 'slot', 'starting_time']))
        <p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">{{ $errors->first('date') ?: $errors->first('time') ?: $errors->first('starting_time') ?: $errors->first('slot') }}</p>
    @endif

    <div>
        <label class="label" for="slot-date">Date</label>
        <input
            id="slot-date"
            type="date"
            x-model="date"
            min="{{ $minDate }}"
            max="{{ $maxDate }}"
            @change="selected = ''"
            class="field sm:max-w-xs"
            required
        >
    </div>

    <div>
        <p class="label">Time slot</p>
        <p x-show="date === ''" class="rounded-lg border border-dashed border-line bg-page px-4 py-4 text-sm text-body">
            Choose a date to see this doctor’s saved time slots.
        </p>
        <p x-show="date !== '' && options().length === 0" x-cloak class="rounded-lg border border-[#FDE68A] bg-[#FFFBEB] px-4 py-3 text-sm text-warning">
            This doctor has no open saved time on that date.
        </p>
        <div x-show="options().length > 0" x-cloak class="grid gap-2 sm:grid-cols-2">
            <template x-for="slot in options()" :key="slot.start + '-' + slot.end">
                <button
                    type="button"
                    @click="choose(slot)"
                    class="rounded-lg border px-3 py-2 text-sm font-semibold"
                    :class="selected === (date + '|' + slot.start) ? 'border-primary bg-primary text-white' : 'border-line bg-white text-heading hover:border-primary'"
                    x-text="label(slot.start) + ' – ' + label(slot.end)"
                ></button>
            </template>
        </div>
    </div>

    <input type="hidden" name="slot" :value="selected" required>
</div>
