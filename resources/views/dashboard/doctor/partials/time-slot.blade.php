@php
    $inputClass = 'h-12 w-full rounded-xl border border-[#c9dcff] bg-white px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20';
    $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'];
    $selectedDay = strtolower((string) data_get($item, 'day', ''));
    $isAvailable = filter_var(data_get($item, 'isAvailable', true), FILTER_VALIDATE_BOOLEAN);
@endphp

<div data-row class="rounded-xl border border-[#0066ff1f] bg-[#f7faff] p-4">
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#181A1E]">Day</label>
            <select name="timeSlots[{{ $index }}][day]" class="{{ $inputClass }}">
                <option value="">Select day</option>
                @foreach ($days as $value => $label)
                    <option value="{{ $value }}" @selected($selectedDay === $value)>{{ $label }}</option>
                @endforeach
                @if ($selectedDay !== '' && ! array_key_exists($selectedDay, $days))
                    <option value="{{ $selectedDay }}" selected>{{ ucfirst($selectedDay) }}</option>
                @endif
            </select>
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#181A1E]">Starting time</label>
            <input type="time" name="timeSlots[{{ $index }}][startingTime]" value="{{ data_get($item, 'startingTime') }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#181A1E]">Ending time</label>
            <input type="time" name="timeSlots[{{ $index }}][endingTime]" value="{{ data_get($item, 'endingTime') }}" class="{{ $inputClass }}">
        </div>
    </div>
    <div class="mt-4 flex items-center justify-between gap-3">
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-[#181A1E]">
            <input type="hidden" name="timeSlots[{{ $index }}][isAvailable]" value="0">
            <input type="checkbox" name="timeSlots[{{ $index }}][isAvailable]" value="1" class="h-4 w-4 rounded border-[#c9dcff] text-[#0067FF] focus:ring-[#0067FF]" @checked($isAvailable)>
            Open for booking
        </label>
        <button type="button" data-remove class="text-sm font-semibold text-red-600 hover:text-red-700">Remove</button>
    </div>
</div>
