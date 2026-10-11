@props(['doctor'])

@php
$photo = data_get($doctor, 'photo');
$photoUrl = $photo && preg_match('/^https?:\/\//', $photo) ? $photo : ($photo ? asset($photo) : asset('images/avatar-icon.png'));
$doctorId = data_get($doctor, 'id', data_get($doctor, '_id'));
$rating = round((float) data_get($doctor, 'averageRating', data_get($doctor, 'avgRating', data_get($doctor, 'rating', 0))));
$hospital = data_get($doctor, 'hospital', data_get($doctor, 'experiences.0.hospital'));
$profileUrl = $doctorId
? (\Illuminate\Support\Facades\Route::has('doctors.show') ? route('doctors.show', $doctorId) : url('/doctors/'.$doctorId))
: route('doctors.index');
@endphp

<article class="p-3 lg:p-5">
    <img src="{{ $photoUrl }}" alt="{{ data_get($doctor, 'name') }}" class="w-full">
    <h2 class="mt-3 text-[18px] font-bold leading-[30px] text-[#181A1E] lg:mt-5 lg:text-[26px] lg:leading-9">
        {{ data_get($doctor, 'name') }}
    </h2>

    <div class="mt-2 flex items-center justify-between lg:mt-4">
        <span class="rounded bg-[#CCF0F3] px-2 py-1 text-[12px] font-semibold leading-4 text-[#01B5C5] lg:px-6 lg:py-2 lg:text-base lg:leading-7">
            {{ data_get($doctor, 'specialization') }}
        </span>
        <div class="flex items-center gap-[6px]">
            <span class="flex items-center gap-[6px] text-sm font-semibold leading-6 text-[#181A1E] lg:text-base lg:leading-7">
                <img src="{{ asset('images/Star.png') }}" alt=""> {{ $rating }}
            </span>
            <span class="text-sm font-normal leading-6 text-[#4E545F] lg:text-base lg:leading-7">({{ data_get($doctor, 'totalRating', 0) }})</span>
        </div>
    </div>

    <div class="mt-[10px] flex items-center justify-between lg:mt-5">
        <p class="text-base font-normal leading-6 text-[#4E545F]">At {{ $hospital }}</p>
        <a href="{{ $profileUrl }}" class="group flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-full border border-[#181A1E] hover:border-none hover:bg-[#0067FF]">
            <svg class="h-5 w-6 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" />
            </svg>
        </a>
    </div>
</article>