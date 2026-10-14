@extends('layouts.dashboard')

@php
    $patient = $patient ?? auth()->user();

    $name = data_get($patient, 'name', 'Patient');
    $email = data_get($patient, 'email', '');
    $phone = data_get($patient, 'phone', '');
    $age = data_get($patient, 'age', '');
    $gender = data_get($patient, 'gender', '');
    $bloodType = data_get($patient, 'bloodType', '');

    $photo = data_get($patient, 'photo');
    $photoUrl = is_string($photo) && $photo !== ''
        ? (str_starts_with($photo, 'http') ? $photo : asset($photo))
        : null;

    $initials = collect(preg_split('/\s+/', trim((string) $name)) ?: [])
        ->filter()
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->take(2)
        ->implode('');

    $healthLabel = function ($record, $yesNo = false) {
        $known = filter_var(data_get($record, 'known', true), FILTER_VALIDATE_BOOLEAN);
        $value = data_get($record, 'value', '');

        if (! $known) {
            return 'Not known';
        }

        if ($value === null || $value === '') {
            return null;
        }

        return $yesNo ? ucfirst((string) $value) : $value;
    };

    $editUrl = \Illuminate\Support\Facades\Route::has('patient.profile.edit')
        ? route('patient.profile.edit')
        : url('/patient/profile/edit');
@endphp

@section('content')
    <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div class="min-w-0">
            <h1 class="truncate text-2xl font-semibold tracking-tight text-heading sm:text-[26px]">{{ $name }}</h1>
            <p class="mt-1 text-sm text-body">Contact details and the health notes shared with a booking.</p>
        </div>
        <a href="{{ $editUrl }}" class="btn-primary">Edit profile</a>
    </header>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    <section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
        <h2 class="text-base font-semibold text-heading">Personal information</h2>

        <div class="mt-6 flex flex-wrap items-center gap-4">
            @if ($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-20 w-20 rounded-full border-2 border-[#1769FF] object-cover">
            @else
                <span class="flex h-20 w-20 items-center justify-center rounded-full border-2 border-[#1769FF] bg-[#EAF3FF] text-xl font-bold text-[#1769FF]">
                    {{ $initials !== '' ? $initials : 'P' }}
                </span>
            @endif
            <div>
                <p class="text-lg font-bold text-[#0B1F3A]">{{ $name }}</p>
                <p class="text-sm text-[#53647D]">{{ $bloodType !== '' ? 'Blood type '.$bloodType : 'Blood type not set' }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            @include('dashboard.partials.detail', ['label' => 'Full name', 'value' => $name])
            @include('dashboard.partials.detail', ['label' => 'Email', 'value' => $email])
            @include('dashboard.partials.detail', ['label' => 'Phone', 'value' => $phone])
            @include('dashboard.partials.detail', ['label' => 'Age', 'value' => $age])
            @include('dashboard.partials.detail', ['label' => 'Gender', 'value' => $gender !== '' ? ucfirst($gender) : null])
            @include('dashboard.partials.detail', ['label' => 'Blood type', 'value' => $bloodType])
        </div>
    </section>

    <section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-card">
        <h2 class="text-base font-semibold text-heading">Health notes</h2>
        <p class="mt-1 text-sm leading-6 text-[#53647D]">Share what you know. “Not known” is fine when you are unsure.</p>

        <div class="mt-6 grid gap-5 sm:grid-cols-3">
            @include('dashboard.partials.detail', ['label' => 'Blood pressure', 'value' => $healthLabel(data_get($patient, 'bp', []))])
            @include('dashboard.partials.detail', ['label' => 'Diabetic', 'value' => $healthLabel(data_get($patient, 'diabetic', []), true)])
            @include('dashboard.partials.detail', ['label' => 'Hyperthyroidism', 'value' => $healthLabel(data_get($patient, 'hyperthyroidism', []), true)])
        </div>
    </section>
@endsection
