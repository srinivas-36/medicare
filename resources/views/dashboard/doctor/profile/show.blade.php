@extends('layouts.dashboard')

@php
$doctor = $doctor ?? auth()->user();

$name = data_get($doctor, 'name', 'Doctor');
$email = data_get($doctor, 'email', '');
$phone = data_get($doctor, 'phone', '');
$gender = data_get($doctor, 'gender', '');
$bio = data_get($doctor, 'bio', '');
$about = data_get($doctor, 'about', '');
$specialization = data_get($doctor, 'specialization', '');
$ticketPrice = data_get($doctor, 'ticketPrice');
$approval = data_get($doctor, 'isApproved');

$photo = data_get($doctor, 'photo');
$photoUrl = is_string($photo) && $photo !== ''
? (str_starts_with($photo, 'http') ? $photo : asset($photo))
: null;

$initials = collect(preg_split('/\s+/', trim((string) $name)) ?: [])
->filter()
->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
->take(2)
->implode('');

$priceLabel = ($ticketPrice === null || $ticketPrice === '')
? null
: '$'.number_format((float) $ticketPrice);

$approvalLabel = match ($approval) {
'approved' => 'Approved',
'cancelled' => 'Cancelled',
'pending' => 'Pending approval',
default => null,
};

$approvalClass = match ($approval) {
'approved' => 'badge-success',
'cancelled' => 'badge-danger',
default => 'badge-warning',
};

$hasValue = function ($items, array $keys) {
return collect(is_array($items) ? $items : [])
->filter(function ($item) use ($keys) {
foreach ($keys as $key) {
if (filled(data_get($item, $key))) {
return true;
}
}

return false;
})
->values();
};

$qualifications = $hasValue(data_get($doctor, 'qualifications', []), ['degree', 'university', 'startingDate', 'endingDate']);
$experiences = $hasValue(data_get($doctor, 'experiences', []), ['position', 'hospital', 'startingDate', 'endingDate']);

$dayOrder = [
'monday' => 'Monday',
'tuesday' => 'Tuesday',
'wednesday' => 'Wednesday',
'thursday' => 'Thursday',
'friday' => 'Friday',
'saturday' => 'Saturday',
'sunday' => 'Sunday',
];
$formatClock = function ($value) {
if (! is_string($value) || ! preg_match('/^(\d{2}):(\d{2})/', $value, $matches)) {
return $value ?: '—';
}

$hour = (int) $matches[1];
$suffix = $hour >= 12 ? 'PM' : 'AM';
$hour12 = $hour % 12 ?: 12;

return sprintf('%02d:%02d %s', $hour12, (int) $matches[2], $suffix);
};
$timeSlots = $hasValue(data_get($doctor, 'timeSlots', []), ['day', 'startingTime', 'endingTime'])
->filter(fn ($slot) => filter_var(data_get($slot, 'isAvailable', true), FILTER_VALIDATE_BOOLEAN))
->groupBy(fn ($slot) => strtolower((string) data_get($slot, 'day')));
$appointmentDuration = (int) data_get($doctor, 'appointmentDuration', 30);
$appointmentBuffer = (int) data_get($doctor, 'appointmentBuffer', 0);

$dateLabel = function ($value) {
if (! is_string($value) || trim($value) === '') {
return 'Not specified';
}

try {
return \Illuminate\Support\Carbon::parse($value)->format('d M Y');
} catch (\Throwable $e) {
return $value;
}
};

$editUrl = \Illuminate\Support\Facades\Route::has('doctor.profile.edit')
? route('doctor.profile.edit')
: (\Illuminate\Support\Facades\Route::has('doctor.profile')
? route('doctor.profile')
: url('/doctors/profile/edit'));
@endphp

@section('content')
<header class="mb-4 flex flex-wrap items-start justify-between gap-3">
    <div class="flex min-w-0 items-center gap-4">
        @if ($photoUrl)
        <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-16 w-16 rounded-full border-2 border-[#0067FF] object-cover">
        @else
        <span class="flex h-16 w-16 items-center justify-center rounded-full border-2 border-[#0067FF] bg-[#E8F2FF] text-lg font-bold text-[#0067FF]">
            {{ $initials !== '' ? $initials : 'D' }}
        </span>
        @endif
        <div class="min-w-0">
            <h1 class="truncate text-xl font-semibold tracking-tight text-[#181A1E] sm:text-[22px]">{{ $name }}</h1>
            <p class="mt-0.5 text-sm text-[#64748B]">{{ $specialization !== '' ? ucfirst($specialization) : 'Specialization not set' }}</p>
            @if ($approvalLabel)
            <span class="mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $approvalClass }}">{{ $approvalLabel }}</span>
            @endif
        </div>
    </div>
    <a href="{{ $editUrl }}" class="btn-primary">Edit profile</a>
</header>

@if (session('status'))
<p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
@endif

@if ($approval === 'pending')
<div class="mt-6 rounded-2xl border border-[#f3d7a1] bg-[#fff7e8] px-5 py-4 text-sm leading-6 text-[#8a5a12]">
    Your profile is waiting for approval. Complete the details and we’ll review it within 3 days.
</div>
@endif

<section class="mt-4 grid gap-3 sm:grid-cols-3">
    <article class="rounded-xl border border-[#E2E8F0] bg-white px-4 py-3 shadow-card">
        <p class="text-[13px] text-[#64748B]">Email</p>
        <p class="mt-1 truncate text-sm font-semibold text-[#181A1E]">{{ $email !== '' ? $email : 'Not provided' }}</p>
    </article>
    <article class="rounded-xl border border-[#E2E8F0] bg-white px-4 py-3 shadow-card">
        <p class="text-[13px] text-[#64748B]">Phone</p>
        <p class="mt-1 truncate text-sm font-semibold text-[#181A1E]">{{ $phone !== '' ? $phone : 'Not provided' }}</p>
    </article>
    <article class="rounded-xl border border-[#E2E8F0] bg-white px-4 py-3 shadow-card">
        <p class="text-[13px] text-[#64748B]">Consultation Fee</p>
        <p class="mt-1 truncate text-sm font-semibold text-[#181A1E]">{{ $priceLabel ?: 'Not provided' }}</p>
    </article>
</section>

<section class="mt-4 rounded-xl border border-line bg-white p-5 shadow-card">
    <h2 class="text-base font-semibold text-heading">About</h2>
    <p class="mt-3 whitespace-pre-wrap text-sm leading-7 text-[#181A1E]">{{ $about !== '' ? $about : ($bio !== '' ? $bio : 'Not provided') }}</p>
</section>

<section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
    <h2 class="text-base font-semibold text-heading">Qualifications</h2>
    @if ($qualifications->isEmpty())
    <p class="mt-4 text-sm leading-6 text-[#53647D]">No qualifications added yet.</p>
    @else
    <div class="mt-5 space-y-4">
        @foreach ($qualifications as $item)
        <article class="rounded-xl bg-[#F4F7FB] p-4">
            <div class="grid gap-4 sm:grid-cols-2">
                @include('dashboard.partials.detail', ['label' => 'Degree', 'value' => data_get($item, 'degree')])
                @include('dashboard.partials.detail', ['label' => 'University', 'value' => data_get($item, 'university')])
                @include('dashboard.partials.detail', ['label' => 'Start date', 'value' => $dateLabel(data_get($item, 'startingDate'))])
                @include('dashboard.partials.detail', ['label' => 'End date', 'value' => $dateLabel(data_get($item, 'endingDate'))])
            </div>
        </article>
        @endforeach
    </div>
    @endif
</section>

<section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
    <h2 class="text-base font-semibold text-heading">Experience</h2>
    @if ($experiences->isEmpty())
    <p class="mt-4 text-sm leading-6 text-[#53647D]">No experience added yet.</p>
    @else
    <div class="mt-5 space-y-4">
        @foreach ($experiences as $item)
        <article class="rounded-xl bg-[#F4F7FB] p-4">
            <div class="grid gap-4 sm:grid-cols-2">
                @include('dashboard.partials.detail', ['label' => 'Position', 'value' => data_get($item, 'position')])
                @include('dashboard.partials.detail', ['label' => 'Hospital', 'value' => data_get($item, 'hospital')])
                @include('dashboard.partials.detail', ['label' => 'Start date', 'value' => $dateLabel(data_get($item, 'startingDate'))])
                @include('dashboard.partials.detail', ['label' => 'End date', 'value' => $dateLabel(data_get($item, 'endingDate'))])
            </div>
        </article>
        @endforeach
    </div>
    @endif
</section>

<section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
    <h2 class="text-base font-semibold text-heading">Weekly schedule</h2>
    <p class="mt-1 text-sm leading-6 text-[#53647D]">{{ $appointmentDuration }} minute appointments, {{ $appointmentBuffer }} minute buffer.</p>
    @if ($timeSlots->isEmpty())
    <p class="mt-4 text-sm leading-6 text-[#53647D]">No working hours added yet.</p>
    @else
    <div class="mt-5 overflow-hidden rounded-xl border border-[#E2E8F0]">
        @foreach ($dayOrder as $key => $label)
        <div class="flex flex-col gap-2 border-b border-[#E2E8F0] px-4 py-3 last:border-b-0 sm:flex-row sm:items-center sm:gap-4">
            <p class="w-28 shrink-0 text-sm font-semibold {{ $timeSlots->has($key) ? 'text-[#0B1F3A]' : 'text-[#94A3B8]' }}">{{ $label }}</p>
            @if ($timeSlots->has($key))
            <div class="flex min-w-0 flex-wrap gap-2">
                @foreach ($timeSlots->get($key) as $slot)
                <span class="inline-flex items-center rounded-lg border border-[#D6E6FF] bg-[#F3F8FF] px-3 py-1.5 text-sm font-medium text-[#0B1F3A]">
                    {{ $formatClock(data_get($slot, 'startingTime')) }} – {{ $formatClock(data_get($slot, 'endingTime')) }}
                </span>
                @endforeach
            </div>
            @else
            <span class="text-sm text-[#94A3B8]">Unavailable</span>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</section>
@endsection