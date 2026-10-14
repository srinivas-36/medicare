@extends('layouts.dashboard')

@section('title', 'Security')

@section('content')
    <x-ui.page-header title="Security" description="Enter your current password before choosing a new one." />

    @if (session('status'))
        <p class="alert-success">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ route('account.password.update') }}" class="max-w-lg space-y-4 rounded-xl border border-line bg-white p-4 shadow-card sm:p-5">
        @csrf
        @method('PUT')
        <label class="block text-sm font-medium text-heading">Current password
            <input type="password" name="current_password" required autocomplete="current-password" class="field mt-1.5">
        </label>
        <x-input-error :messages="$errors->get('current_password')" />
        <label class="block text-sm font-medium text-heading">New password
            <input type="password" name="password" required autocomplete="new-password" class="field mt-1.5">
        </label>
        <x-input-error :messages="$errors->get('password')" />
        <p class="text-xs text-muted">Use at least 8 characters.</p>
        <label class="block text-sm font-medium text-heading">Confirm new password
            <input type="password" name="password_confirmation" required autocomplete="new-password" class="field mt-1.5">
        </label>
        <button type="submit" class="btn-primary">Update password</button>
    </form>
@endsection
