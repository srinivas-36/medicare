@props(['doctor'])

@php
$photo = data_get($doctor, 'photo');
$photoUrl = $photo && preg_match('/^https?:\/\//', $photo) ? $photo : ($photo ? asset($photo) : asset('images/avatar-icon.png'));
$rating = round((float) data_get($doctor, 'averageRating', data_get($doctor, 'avgRating', 0)));
@endphp

<div class="flex items-center gap-5">
    <figure class="max-h-[200px] max-w-[200px]">
        <img src="{{ $photoUrl }}" alt="{{ data_get($doctor, 'name') }}" class="w-full">
    </figure>

    <div>
        <span class="rounded bg-[#CCF0F3] px-6 py-1 text-[12px] font-semibold leading-4 text-[#01B5C5] lg:px-6 lg:py-2 lg:text-[16px] lg:leading-10">
            {{ data_get($doctor, 'specialization') }}
        </span>
        <h3 class="text-[22px] text-[#181A1E]">{{ data_get($doctor, 'name') }}</h3>

        <div class="flex items-center gap-[6px]">
            <span class="flex items-center gap-[6px] text-[16px] font-semibold leading-5 text-[#181A1E] lg:leading-7">
                <img src="{{ asset('images/Star.png') }}" alt=""> {{ $rating }}
            </span>
            <span class="text-[16px] font-normal leading-5 text-[#4E545F] lg:leading-7">
                ({{ data_get($doctor, 'totalRating', 0) }})
            </span>
        </div>

        <p class="mt-3 text-[14px] font-normal leading-5 text-[#4E545F] md:mt-[18px] md:text-[15px] lg:max-w-[390px] lg:text-[18px] lg:leading-[30px]">
            {{ data_get($doctor, 'bio') }}.
        </p>
    </div>
</div>