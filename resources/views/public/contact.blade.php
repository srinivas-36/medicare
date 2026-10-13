@extends('layouts.public')

@section('title', 'Contact')
@section('description', 'How to reach Medicare. Booking questions are handled from your account.')

@section('content')
    <x-page-intro
        eyebrow="We're here to help"
        title="Contact"
        subtitle="This site does not publish a phone number or public inbox. Use your account for bookings, or the links below for the screens that exist today."
    />

    <section class="py-14 lg:py-20">
        <div class="page grid gap-6 md:grid-cols-3">
            <article class="card">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mint text-primary">
                    <x-ui.icon name="calendar" />
                </span>
                <h2 class="mt-4 text-lg font-semibold text-navy">Book a visit</h2>
                <p class="mt-2 text-sm leading-6 text-text">Search the directory, open a doctor, and request an open time.</p>
                <a href="{{ route('doctors.index') }}" class="mt-4 inline-flex text-sm font-semibold text-primary no-underline">Find a doctor</a>
            </article>
            <article class="card">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mint text-primary">
                    <x-ui.icon name="user" />
                </span>
                <h2 class="mt-4 text-lg font-semibold text-navy">Your account</h2>
                <p class="mt-2 text-sm leading-6 text-text">Patients see their bookings. Doctors and reception use their own dashboards.</p>
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="mt-4 inline-flex text-sm font-semibold text-primary no-underline">
                    {{ auth()->check() ? 'Open dashboard' : 'Log in' }}
                </a>
            </article>
            <article class="card">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mint text-primary">
                    <x-ui.icon name="file" />
                </span>
                <h2 class="mt-4 text-lg font-semibold text-navy">Common questions</h2>
                <p class="mt-2 text-sm leading-6 text-text">Fees, cancellation, and what each role can see are explained in the FAQ.</p>
                <a href="{{ route('faq') }}" class="mt-4 inline-flex text-sm font-semibold text-primary no-underline">Read the FAQ</a>
            </article>
        </div>
    </section>
@endsection
