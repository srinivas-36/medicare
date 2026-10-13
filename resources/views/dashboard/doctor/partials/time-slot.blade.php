@php
    $inputClass = 'h-12 w-full rounded-xl border border-[#E2EAF4] bg-white px-4 text-base text-[#0B1F3A] placeholder:text-[#53647D] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';
    $days = ['monday' => 'Monday', 'tuesday' => 'Tuesday', 'wednesday' => 'Wednesday', 'thursday' => 'Thursday', 'friday' => 'Friday', 'saturday' => 'Saturday', 'sunday' => 'Sunday'];
    $selectedDay = strtolower((string) data_get($item, 'day', ''));
    $isAvailable = filter_var(data_get($item, 'isAvailable', true), FILTER_VALIDATE_BOOLEAN);
@endphp

<div data-row class="rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] p-4">
    <div class="grid gap-4 sm:grid-cols-3">
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Day</label>
            <select name="timeSlots[{{ $index }}][day]" class="{{ $inputClass }}">
                <option value="">Select day</option>
                @foreach ($days as $value => $label)
                    <option value="{{ $value }}" @selected($selectedDay === $value)>{{ $label }}</option>
                @endforeach
                @if ($selectedDay !== '' && ! array_key_exists($selectedDay, $days))
                    <option value="{{ $selectedDay }}" selected>{{ ucfirst($selectedDay) }}</option>
                @endif
            </select>
            @error('timeSlots.'.$index.'.day')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Starting time</label>
            <input type="time" name="timeSlots[{{ $index }}][startingTime]" value="{{ data_get($item, 'startingTime') }}" class="{{ $inputClass }}">
            @error('timeSlots.'.$index.'.startingTime')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Ending time</label>
            <input type="time" name="timeSlots[{{ $index }}][endingTime]" value="{{ data_get($item, 'endingTime') }}" class="{{ $inputClass }}">
            @error('timeSlots.'.$index.'.endingTime')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <div class="mt-4 flex items-center justify-between gap-3">
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-[#0B1F3A]">
            <input type="hidden" name="timeSlots[{{ $index }}][isAvailable]" value="0">
            <input type="checkbox" name="timeSlots[{{ $index }}][isAvailable]" value="1" class="h-4 w-4 rounded border-[#E2EAF4] text-[#1769FF] focus:ring-[#1769FF]" @checked($isAvailable)>
            Open for booking
        </label>
        <button type="button" data-remove class="text-sm font-semibold text-red-600 hover:text-red-700">Remove</button>
    </div>
</div>
