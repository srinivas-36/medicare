@extends('layouts.dashboard')

@section('title', 'Clinic settings')

@section('content')
    <x-ui.page-header title="Clinic settings" description="These rules apply to every booking path: horizon, notice, cancellation, and the clinic clock." />

    @if (session('status'))
        <p class="alert-success">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <p class="alert-error">{{ $errors->first() }}</p>
    @endif

    <form method="POST" action="{{ route('admin.settings.update') }}" class="grid gap-4 lg:grid-cols-2">
        @csrf
        @method('PUT')

        <section class="rounded-xl border border-line bg-white p-4 shadow-card">
            <h2 class="text-sm font-semibold text-heading">Scheduling</h2>
            <p class="mt-1 text-[13px] text-muted">How far ahead a patient can book, and how soon a slot must start.</p>
            <label class="mt-4 block text-sm font-medium text-heading">Booking horizon (months)
                <input type="number" name="booking_horizon_months" min="1" max="24" value="{{ old('booking_horizon_months', $settings->booking_horizon_months) }}" class="field mt-1.5">
            </label>
            <label class="mt-4 block text-sm font-medium text-heading">Minimum notice (minutes)
                <input type="number" name="minimum_notice_minutes" min="0" value="{{ old('minimum_notice_minutes', $settings->minimum_notice_minutes) }}" class="field mt-1.5">
                <span class="mt-1 block text-xs font-normal text-muted">Slots that start before this notice are hidden.</span>
            </label>
            <label class="mt-4 block text-sm font-medium text-heading">Cancellation notice (hours)
                <input type="number" name="cancellation_notice_hours" min="0" value="{{ old('cancellation_notice_hours', $settings->cancellation_notice_hours) }}" class="field mt-1.5">
                <span class="mt-1 block text-xs font-normal text-muted">Patients can cancel only until this many hours before the visit.</span>
            </label>
        </section>

        <section class="rounded-xl border border-line bg-white p-4 shadow-card">
            <h2 class="text-sm font-semibold text-heading">Clinic clock and payment</h2>
            <p class="mt-1 text-[13px] text-muted">Slot times are calculated in this timezone. The payment label is shown on bookings.</p>
            <label class="mt-4 block text-sm font-medium text-heading">Timezone
                <input name="timezone" value="{{ old('timezone', $settings->timezone) }}" class="field mt-1.5" list="clinic-timezones">
                <datalist id="clinic-timezones">
                    @foreach (timezone_identifiers_list() as $zone)
                        <option value="{{ $zone }}"></option>
                    @endforeach
                </datalist>
            </label>
            <label class="mt-4 block text-sm font-medium text-heading">Payment label
                <select name="payment_policy" class="field mt-1.5">
                    <option value="none" @selected(old('payment_policy', $settings->payment_policy) === 'none')>No payment instruction</option>
                    <option value="clinic" @selected(old('payment_policy', $settings->payment_policy) === 'clinic')>Payment handled at clinic</option>
                </select>
            </label>
            <button class="btn-primary mt-5" type="submit">Save settings</button>
        </section>
    </form>
@endsection
