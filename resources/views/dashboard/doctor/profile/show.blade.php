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
'approved' => 'bg-[#e8f8ef] text-[#0f9f6e]',
'cancelled' => 'bg-red-50 text-red-600',
default => 'bg-[#fff7e8] text-[#b7791f]',
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

$dayOrder = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
$timeSlots = $hasValue(data_get($doctor, 'timeSlots', []), ['day', 'startingTime', 'endingTime'])
->sortBy(function ($slot) use ($dayOrder) {
$index = array_search(strtolower((string) data_get($slot, 'day')), $dayOrder, true);

return $index === false ? 99 : $index;
})
->values();

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
<div class="rounded-2xl bg-[#0067FF] p-6 text-white shadow-[0_12px_40px_rgba(0,103,255,0.22)] sm:p-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Doctor profile</p>
            <h1 class="mt-2 text-3xl font-extrabold leading-tight">{{ $name }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-white/85">
                This is the profile patients see before they book.
            </p>
        </div>
        <a href="{{ $editUrl }}" class="inline-flex h-12 items-center justify-center rounded-xl bg-white px-6 text-base font-semibold text-[#0067FF] hover:bg-[#e8f2ff]">
            Edit profile
        </a>
    </div>
</div>

@if (session('status'))
<p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
@endif

@if ($approval === 'pending')
<div class="mt-6 rounded-2xl border border-[#f3d7a1] bg-[#fff7e8] px-5 py-4 text-sm leading-6 text-[#8a5a12]">
    Your profile is waiting for approval. Complete the details and we’ll review it within 3 days.
</div>
@endif

<section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="text-xl font-bold text-[#181A1E]">Personal information</h2>
        @if ($approvalLabel)
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $approvalClass }}">{{ $approvalLabel }}</span>
        @endif
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-4">
        @if ($photoUrl)
        <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-20 w-20 rounded-full border-2 border-[#0067FF] object-cover">
        @else
        <span class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-[#0067FF] bg-[#e8f2ff] text-xl font-bold text-[#0067FF]">
            {{ $initials !== '' ? $initials : 'D' }}
        </span>
        @endif
        <div>
            <p class="text-lg font-bold text-[#181A1E]">{{ $name }}</p>
            <p class="text-sm text-[#4E545F]">{{ $specialization !== '' ? ucfirst($specialization) : 'Specialization not set' }}</p>
        </div>
    </div>

    <div class="mt-6 grid gap-5 sm:grid-cols-2">
        @include('dashboard.partials.detail', ['label' => 'Full name', 'value' => $name])
        @include('dashboard.partials.detail', ['label' => 'Email', 'value' => $email])
        @include('dashboard.partials.detail', ['label' => 'Phone', 'value' => $phone])
        @include('dashboard.partials.detail', ['label' => 'Gender', 'value' => $gender !== '' ? ucfirst($gender) : null])
        @include('dashboard.partials.detail', ['label' => 'Specialization', 'value' => $specialization !== '' ? ucfirst($specialization) : null])
        @include('dashboard.partials.detail', ['label' => 'Ticket price', 'value' => $priceLabel])
    </div>
</section>

<section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
    <h2 class="text-xl font-bold text-[#181A1E]">About</h2>
    <div class="mt-5 space-y-5">
        <div>
            <p class="text-sm text-[#4E545F]">Short bio</p>
            <p class="mt-1 leading-7 text-[#181A1E]">{{ $bio !== '' ? $bio : 'Not provided' }}</p>
        </div>
        <div>
            <p class="text-sm text-[#4E545F]">About</p>
            <p class="mt-1 whitespace-pre-wrap leading-7 text-[#181A1E]">{{ $about !== '' ? $about : 'Not provided' }}</p>
        </div>
    </div>
</section>

<section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
    <h2 class="text-xl font-bold text-[#181A1E]">Qualifications</h2>
    @if ($qualifications->isEmpty())
    <p class="mt-4 text-sm leading-6 text-[#4E545F]">No qualifications added yet.</p>
    @else
    <div class="mt-5 space-y-4">
        @foreach ($qualifications as $item)
        <article class="rounded-xl bg-[#f7faff] p-4">
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

<section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
    <h2 class="text-xl font-bold text-[#181A1E]">Experience</h2>
    @if ($experiences->isEmpty())
    <p class="mt-4 text-sm leading-6 text-[#4E545F]">No experience added yet.</p>
    @else
    <div class="mt-5 space-y-4">
        @foreach ($experiences as $item)
        <article class="rounded-xl bg-[#f7faff] p-4">
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

<section class="mt-6 rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
    <h2 class="text-xl font-bold text-[#181A1E]">Time slots</h2>
    @if ($timeSlots->isEmpty())
    <p class="mt-4 text-sm leading-6 text-[#4E545F]">No time slots added yet.</p>
    @else
    <div class="mt-5 grid gap-4 sm:grid-cols-2">
        @foreach ($timeSlots as $slot)
        @php
        $open = filter_var(data_get($slot, 'isAvailable', true), FILTER_VALIDATE_BOOLEAN);
        @endphp
        <article class="rounded-xl border px-4 py-3 {{ $open ? 'border-[#b7ebc9] bg-[#e8f8ef]' : 'border-[#0066ff1f] bg-[#f7faff]' }}">
            <p class="font-semibold capitalize {{ $open ? 'text-[#0f9f6e]' : 'text-[#181A1E]' }}">{{ data_get($slot, 'day') ?: 'Day not set' }}</p>
            <p class="mt-1 text-sm {{ $open ? 'text-[#0f9f6e]' : 'text-[#4E545F]' }}">
                {{ data_get($slot, 'startingTime') ?: '—' }} – {{ data_get($slot, 'endingTime') ?: '—' }}
            </p>
            <p class="mt-1 text-xs font-semibold uppercase tracking-wide {{ $open ? 'text-[#0f9f6e]' : 'text-[#4E545F]' }}">
                {{ $open ? 'Open for booking' : 'Closed' }}
            </p>
        </article>
        @endforeach
    </div>
    @endif
</section>
@endsection