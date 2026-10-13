{{--
    One doctor card. Used on the homepage and the directory.
    Expects a Doctor model with user, and optionally qualifications and timeSlots.
    Ratings are shown only when total_rating is greater than zero.
--}}
@props(['doctor'])

@php
    $user = $doctor->user;
    $name = $user->name ?? 'Doctor';
    $slug = $user->slug ?? null;
    $photo = $user->photo ?? null;
    $photoUrl = is_string($photo) && $photo !== ''
        ? (str_starts_with($photo, 'http') ? $photo : asset($photo))
        : asset('images/avatar-icon.png');
    $profileUrl = $slug ? route('doctors.show', $slug) : route('doctors.index');
    $degree = $doctor->relationLoaded('qualifications')
        ? $doctor->qualifications->first()?->degree
        : null;
    $reviewCount = (int) ($doctor->total_rating ?? 0);
    $hasOpenTimes = $doctor->relationLoaded('timeSlots')
        && $doctor->timeSlots->contains(fn ($slot) => (bool) $slot->is_available);
    $role = auth()->user()->role ?? null;
    $canBook = $role === null || $role === 'patient';
@endphp

<article class="card flex h-full flex-col overflow-hidden !p-0">
    <a href="{{ $profileUrl }}" class="block bg-light">
        <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-52 w-full object-cover object-top" loading="lazy" width="480" height="320">
    </a>
    <div class="flex flex-1 flex-col p-5">
        <h3 class="text-lg font-semibold text-navy">
            <a href="{{ $profileUrl }}" class="no-underline hover:text-primary">{{ $name }}</a>
        </h3>
        <p class="mt-1 text-sm font-semibold text-primary">{{ $doctor->specialization ?: 'Specialty not listed' }}</p>
        @if ($degree)
            <p class="mt-1 text-sm text-muted">{{ $degree }}</p>
        @endif
        @if ($reviewCount > 0)
            <p class="mt-2 text-sm text-navy">
                {{ number_format((float) $doctor->average_rating, 1) }}
                <span class="text-muted">({{ $reviewCount }} {{ $reviewCount === 1 ? 'rating' : 'ratings' }})</span>
            </p>
        @endif
        @if ($hasOpenTimes)
            <p class="mt-2 text-sm text-success">Open times are listed</p>
        @endif
        <p class="mt-4 text-lg font-bold text-navy">{{ $doctor->ticketPrice }} USD</p>
        <div class="mt-auto flex flex-col gap-2 pt-4 sm:flex-row">
            <a href="{{ $profileUrl }}" class="btn-secondary h-11 flex-1 px-3 text-sm">View profile</a>
            @if ($canBook)
                <a href="{{ route('doctors.book', $doctor) }}" class="btn-primary h-11 flex-1 px-3 text-sm">Book</a>
            @endif
        </div>
    </div>
</article>
