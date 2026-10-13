@props(['doctor'])

@php
    $formatDate = function ($date) {
        return $date ? date('F j, Y', strtotime($date)) : '';
    };
@endphp

<div class="space-y-10">
    <div>
        <h2 class="text-xl font-bold text-heading">About {{ data_get($doctor, 'name') }}</h2>
        <p class="mt-3 text-base leading-7 text-text">
            {{ data_get($doctor, 'about') }}
        </p>
    </div>

    <div>
        <h2 class="text-xl font-bold text-heading">Education</h2>
        <ul class="mt-4 space-y-4">
            @forelse (data_get($doctor, 'qualifications') ?? [] as $item)
                <li class="surface flex flex-col gap-1 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="font-semibold text-heading">{{ data_get($item, 'degree') }}</p>
                        <p class="text-sm text-text">{{ data_get($item, 'institution') }}</p>
                    </div>
                    <p class="text-sm font-semibold text-iris">
                        {{ $formatDate(data_get($item, 'start_date')) }} – {{ $formatDate(data_get($item, 'end_date')) }}
                    </p>
                </li>
            @empty
                <li class="text-sm text-text">Education details will appear here once the doctor adds them.</li>
            @endforelse
        </ul>
    </div>

    <div>
        <h2 class="text-xl font-bold text-heading">Experience</h2>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2">
            @forelse (data_get($doctor, 'experiences') ?? [] as $item)
                <li class="rounded-2xl bg-[#fff8ea] px-5 py-4">
                    <p class="text-sm font-semibold text-[#b7791f]">
                        {{ $formatDate(data_get($item, 'start_date')) }} – {{ $formatDate(data_get($item, 'end_date')) }}
                    </p>
                    <p class="mt-1 font-semibold text-heading">{{ data_get($item, 'designation') }}</p>
                    <p class="text-sm text-text">{{ data_get($item, 'hospital_name') }}</p>
                </li>
            @empty
                <li class="text-sm text-text">Experience details will appear here once the doctor adds them.</li>
            @endforelse
        </ul>
    </div>
</div>
