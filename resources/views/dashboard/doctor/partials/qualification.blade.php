@php
    $inputClass = 'h-12 w-full rounded-xl border border-[#c9dcff] bg-white px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20';
@endphp

<div data-row class="rounded-xl border border-[#0066ff1f] bg-[#f7faff] p-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#181A1E]">Starting date</label>
            <input type="date" name="qualifications[{{ $index }}][startingDate]" value="{{ data_get($item, 'startingDate') }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#181A1E]">Ending date</label>
            <input type="date" name="qualifications[{{ $index }}][endingDate]" value="{{ data_get($item, 'endingDate') }}" class="{{ $inputClass }}">
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#181A1E]">Degree</label>
            <input type="text" name="qualifications[{{ $index }}][degree]" value="{{ data_get($item, 'degree') }}" placeholder="MBBS" class="{{ $inputClass }}">
        </div>
        <div>
            <label class="mb-2 block text-sm font-semibold text-[#181A1E]">University</label>
            <input type="text" name="qualifications[{{ $index }}][university]" value="{{ data_get($item, 'university') }}" placeholder="Medical college" class="{{ $inputClass }}">
        </div>
    </div>
    <button type="button" data-remove class="mt-4 text-sm font-semibold text-red-600 hover:text-red-700">Remove</button>
</div>
