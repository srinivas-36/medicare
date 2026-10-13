@extends('layouts.dashboard')

@php
    $receptionist = $receptionist ?? auth()->user();

    $name = data_get($receptionist, 'name', 'Receptionist');
    $email = data_get($receptionist, 'email', '');
    $phone = data_get($receptionist, 'phone', '');
    $gender = data_get($receptionist, 'gender', '');
    $about = data_get($receptionist, 'about', '');

    $photo = data_get($receptionist, 'photo');
    $photoUrl = is_string($photo) && $photo !== ''
        ? (str_starts_with($photo, 'http') ? $photo : asset($photo))
        : null;

    $initials = collect(preg_split('/\s+/', trim((string) $name)) ?: [])
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');

    $editUrl = \Illuminate\Support\Facades\Route::has('receptionist.profile.edit')
        ? route('receptionist.profile.edit')
        : url('/receptionist/profile/edit');
@endphp

@section('content')
    <div class="dash-banner">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Receptionist profile</p>
                <h1 class="mt-2 text-3xl font-extrabold leading-tight">{{ $name }}</h1>
                <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
                    Your front-desk details. Update them whenever your contact information changes.
                </p>
                <div class="mt-6 flex flex-wrap gap-2">
                    @if ($email !== '')
                        <span class="dash-chip">{{ $email }}</span>
                    @endif
                    <span class="dash-chip">Front desk</span>
                </div>
            </div>
            <a href="{{ $editUrl }}" class="inline-flex h-12 items-center justify-center rounded-xl bg-white px-6 text-base font-semibold text-[#1769FF] hover:bg-[#EAF3FF]">
                Edit profile
            </a>
        </div>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    <section class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
        <h2 class="text-xl font-bold text-[#0B1F3A]">Personal information</h2>

        <div class="mt-6 flex flex-wrap items-center gap-4">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-20 w-20 rounded-full border-2 border-[#1769FF] object-cover">
            @else
                <span class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-[#1769FF] bg-[#EAF3FF] text-xl font-bold text-[#1769FF]">
                    {{ $initials !== '' ? $initials : 'R' }}
                </span>
            @endif
            <div>
                <p class="text-lg font-bold text-[#0B1F3A]">{{ $name }}</p>
                <p class="text-sm text-[#53647D]">{{ $email !== '' ? $email : 'Email not set' }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @include('dashboard.partials.detail', ['label' => 'Full name', 'value' => $name])
            @include('dashboard.partials.detail', ['label' => 'Email', 'value' => $email])
            @include('dashboard.partials.detail', ['label' => 'Phone', 'value' => $phone])
            @include('dashboard.partials.detail', ['label' => 'Gender', 'value' => $gender !== '' ? ucfirst($gender) : null])
        </div>
    </section>

    <section class="mt-6 rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
        <h2 class="text-xl font-bold text-[#0B1F3A]">About</h2>
        <p class="mt-4 whitespace-pre-wrap leading-7 text-[#0B1F3A]">{{ $about !== '' ? $about : 'Not provided' }}</p>
    </section>
@endsection
