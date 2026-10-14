@extends('layouts.dashboard')

@php
    $genders = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];
@endphp

@section('title', 'Add a receptionist')

@section('content')
    <x-ui.page-header title="Add a receptionist" description="Create their sign-in. They will see only your schedule, book visits in open times, and confirm or cancel those visits. Share the password with them directly.">
        <a href="{{ route('doctor.receptionists') }}" class="btn-secondary">Back</a>
    </x-ui.page-header>

    @if ($errors->any())
        <p class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-600">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('doctor.receptionists.store') }}" class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
        @csrf

        <section class="dash-panel p-6 sm:p-8">
            <h2 class="text-lg font-bold text-navy">Account</h2>
            <p class="mt-1 text-sm text-text">This is how they sign in to the front desk.</p>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="receptionist-name" class="label">Full name</label>
                    <input id="receptionist-name" name="name" value="{{ old('name') }}" required class="field" autocomplete="name">
                </div>
                <div>
                    <label for="receptionist-email" class="label">Email</label>
                    <input id="receptionist-email" type="email" name="email" value="{{ old('email') }}" required class="field" autocomplete="off">
                </div>
                <div>
                    <label for="receptionist-phone" class="label">Phone</label>
                    <input id="receptionist-phone" name="phone" value="{{ old('phone') }}" required class="field" autocomplete="tel">
                </div>
                <div>
                    <label for="receptionist-gender" class="label">Gender</label>
                    <select id="receptionist-gender" name="gender" class="field">
                        <option value="">Not specified</option>
                        @foreach ($genders as $value => $label)
                            <option value="{{ $value }}" @selected(old('gender') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="receptionist-password" class="label">Temporary password</label>
                    <input id="receptionist-password" type="password" name="password" required class="field" autocomplete="new-password">
                    <p class="mt-2 text-xs leading-5 text-muted">At least 8 characters. They can change it after signing in.</p>
                </div>
                <div>
                    <label for="receptionist-password-confirmation" class="label">Confirm password</label>
                    <input id="receptionist-password-confirmation" type="password" name="password_confirmation" required class="field" autocomplete="new-password">
                </div>
            </div>

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <button type="submit" class="btn-primary">Add receptionist</button>
                <a href="{{ route('doctor.receptionists') }}" class="btn-ghost">Cancel</a>
            </div>
        </section>

        <aside class="dash-panel h-fit p-6">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-primary">What they can do</p>
            <ul class="mt-4 space-y-3 text-sm leading-6 text-text">
                <li class="rounded-xl bg-page px-4 py-3">See your open hours for the next 14 days.</li>
                <li class="rounded-xl bg-page px-4 py-3">Book a patient into a free time. Taken times stay hidden.</li>
                <li class="rounded-xl bg-page px-4 py-3">Confirm, complete, or cancel visits on your schedule.</li>
                <li class="rounded-xl bg-page px-4 py-3">They cannot edit your profile, fee, or clinic hours.</li>
            </ul>
        </aside>
    </form>
@endsection
