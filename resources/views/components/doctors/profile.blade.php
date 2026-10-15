@props(['doctor', 'live' => false])

@php
    $photo = data_get($doctor, 'photo');
    $photoUrl = $photo && preg_match('/^https?:\/\//', $photo) ? $photo : ($photo ? asset($photo) : asset('images/avatar-icon.png'));
    $rating = number_format((float) data_get($doctor, 'averageRating', data_get($doctor, 'avgRating', 0)), 1);
    $totalRatings = (int) data_get($doctor, 'totalRating', 0);
@endphp

<div class="surface flex flex-col gap-6 p-6 sm:flex-row sm:items-start sm:p-8">
    <img src="{{ $photoUrl }}" alt="{{ data_get($doctor, 'name') }}" class="h-48 w-48 shrink-0 rounded-3xl bg-[#F7FAFF] object-contain object-center ring-4 ring-[#EAF3FF]">

    <div class="min-w-0">
        <span class="inline-flex rounded-full bg-[#DDF8F1] px-3 py-1 text-sm font-semibold text-iris">
            {{ data_get($doctor, 'specialization') }}
        </span>
        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-heading">{{ data_get($doctor, 'name') }}</h1>

        @if ($live)
            <p class="mt-3 text-sm text-heading" x-show="total > 0" @if ($totalRatings === 0) x-cloak @endif>
                <span class="font-semibold text-[#E99A17]" x-text="average">{{ $rating }}</span>
                <span class="text-text">
                    (<span x-text="total">{{ $totalRatings }}</span>
                    <span x-text="total === 1 ? 'rating' : 'ratings'">{{ $totalRatings === 1 ? 'rating' : 'ratings' }}</span>)
                </span>
            </p>
        @elseif ($totalRatings > 0)
            <p class="mt-3 text-sm text-heading">
                {{ $rating }}
                <span class="text-text">({{ $totalRatings }} {{ $totalRatings === 1 ? 'rating' : 'ratings' }})</span>
            </p>
        @endif

        <p class="mt-4 max-w-xl text-base leading-7 text-text">
            {{ data_get($doctor, 'bio') }}
        </p>
    </div>
</div>
