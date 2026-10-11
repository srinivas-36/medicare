@props(['doctor'])

@php
$formatDate = function ($date) {
return $date ? date('F j, Y', strtotime($date)) : '';
};
@endphp

<div>
    <div>
        <h3 class="flex items-center gap-2 text-[20px] font-semibold leading-[30px] text-[#181A1E]">
            About of
            <span class="text-[24px] font-bold leading-9 text-[#01B5C5]">{{ data_get($doctor, 'name') }}</span>
        </h3>
        <p class="mt-3 text-base font-normal leading-7 text-[#4E545F] md:mt-[18px] md:text-[18px] md:leading-[30px]">
            {{ data_get($doctor, 'about') }}
        </p>
    </div>

    <div class="mt-12">
        <h3 class="text-[20px] font-semibold leading-[30px] text-[#181A1E]">Education</h3>
        <ul class="pt-4 md:p-5">
            @foreach (data_get($doctor, 'qualifications') ?? [] as $item)
            <li class="mb-[30px] flex flex-col sm:flex-row sm:items-end sm:justify-between md:gap-5">
                <div>
                    <span class="text-[15px] font-semibold leading-6 text-[#01B5C5]">
                        {{ $formatDate(data_get($item, 'start_date')) }} -{{ $formatDate(data_get($item, 'end_date')) }}
                    </span>
                    <p class="text-[16px] font-medium leading-6 text-[#4E545F]">{{ data_get($item, 'degree') }}</p>
                </div>
                <p class="text-[14px] font-medium leading-5 text-[#4E545F]">{{ data_get($item, 'institution') }}</p>
            </li>
            @endforeach
        </ul>
    </div>

    <div class="mt-12">
        <h3 class="text-[20px] font-semibold leading-[30px] text-[#181A1E]">Experience</h3>
        <ul class="grid gap-[30px] pt-4 sm:grid-cols-2 md:p-5">
            @foreach (data_get($doctor, 'experiences') ?? [] as $item)
            <li class="rounded bg-[#fff9ea] p-4">
                <span class="text-[15px] font-semibold leading-6 text-[#FEB60D]">
                    {{ $formatDate(data_get($item, 'start_date')) }} - {{ $formatDate(data_get($item, 'end_date')) }}
                </span>
                <p class="text-[16px] font-medium leading-6 text-[#4E545F]">{{ data_get($item, 'designation') }}</p>
                <p class="text-[14px] font-medium leading-5 text-[#4E545F]">{{ data_get($item, 'hospital_name') }}</p>
            </li>
            @endforeach
        </ul>
    </div>
</div>