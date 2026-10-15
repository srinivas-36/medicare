@props(['review'])

@php
$photo = data_get($review, 'user.photo', data_get($review, 'photo'));
$photoUrl = $photo && preg_match('/^https?:\/\//', $photo) ? $photo : ($photo ? asset($photo) : asset('images/avatar-icon.png'));
$name = data_get($review, 'user.name', data_get($review, 'name'));
$createdAt = data_get($review, 'createdAt', data_get($review, 'created_at'));
$rating = (int) data_get($review, 'rating', 0);
@endphp

<div class="mb-[30px] flex justify-between gap-10">
    <div class="flex gap-3">
        <figure class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-[#F7FAFF]">
            <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-full w-full object-contain object-center">
        </figure>
        <div>
            <h5 class="text-[16px] font-bold leading-6 text-[#1769FF]">{{ $name }}</h5>
            <p class="text-[14px] leading-6 text-[#53647D]">{{ $createdAt ? date('F j, Y', strtotime($createdAt)) : '' }}</p>
            <p class="mt-3 text-[15px] font-medium leading-7 text-[#53647D]">{{ data_get($review, 'reviewText') }}</p>
        </div>
    </div>
    <div class="flex gap-1 text-[#1769FF]">
        @for ($star = 0; $star
        < $rating; $star++)
            <x-doctors.star />
        @endfor
    </div>
</div>