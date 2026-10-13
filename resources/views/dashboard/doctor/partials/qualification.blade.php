@php
    $inputClass = 'h-12 w-full rounded-xl border border-[#E2EAF4] bg-white px-4 text-base text-[#0B1F3A] placeholder:text-[#53647D] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';
@endphp

<div data-row class="rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] p-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Starting date</label>
            <input type="date" name="qualifications[{{ $index }}][startingDate]" value="{{ data_get($item, 'startingDate') }}" class="{{ $inputClass }}">
            @error('qualifications.'.$index.'.startingDate')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Ending date</label>
            <input type="date" name="qualifications[{{ $index }}][endingDate]" value="{{ data_get($item, 'endingDate') }}" class="{{ $inputClass }}">
            @error('qualifications.'.$index.'.endingDate')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Degree</label>
            <input type="text" name="qualifications[{{ $index }}][degree]" value="{{ data_get($item, 'degree') }}" placeholder="MBBS" class="{{ $inputClass }}">
            @error('qualifications.'.$index.'.degree')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#0B1F3A]">University</label>
            <input type="text" name="qualifications[{{ $index }}][university]" value="{{ data_get($item, 'university') }}" placeholder="Medical college" class="{{ $inputClass }}">
            @error('qualifications.'.$index.'.university')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
    <button type="button" data-remove class="mt-4 text-sm font-semibold text-red-600 hover:text-red-700">Remove</button>
</div>
