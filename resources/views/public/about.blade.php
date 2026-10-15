@extends('layouts.public')

@section('title', 'About')
@section('description', 'Medicare connects patients, doctors, and reception so a visit can be requested and tracked.')

@section('content')
    <x-page-intro
        eyebrow="About Medicare"
        title="Care that is easy to start and easy to follow"
        subtitle="Medicare connects patients, doctors, and the front desk so a visit can be requested, confirmed, and tracked without phone tag."
    />

    <section class="py-16 lg:py-20">
        <div class="page grid gap-6 md:grid-cols-3">
            <article class="surface p-6">
                <p class="text-sm font-semibold text-primary">Patients</p>
                <h2 class="mt-2 text-xl font-bold text-heading">Book with a clear fee</h2>
                <p class="mt-3 text-sm leading-6 text-text">Search a doctor, pick an open time, and keep every upcoming or past visit on your dashboard.</p>
            </article>
            <article class="surface p-6">
                <p class="text-sm font-semibold text-primary">Doctors</p>
                <h2 class="mt-2 text-xl font-bold text-heading">A schedule you can trust</h2>
                <p class="mt-3 text-sm leading-6 text-text">Set your profile, qualifications, and time slots. Confirm or complete the visits that belong to you.</p>
            </article>
            <article class="surface p-6">
                <p class="text-sm font-semibold text-primary">Reception</p>
                <h2 class="mt-2 text-xl font-bold text-heading">The front desk, in one board</h2>
                <p class="mt-3 text-sm leading-6 text-text">See today’s appointments, update their status, and book a patient when they call the clinic.</p>
            </article>
        </div>
    </section>

    <section class="pb-20">
        <div class="page grid items-center gap-10 lg:grid-cols-2">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-heading">How a visit moves through Medicare</h2>
                <ol class="mt-6 space-y-4">
                    <li class="surface flex gap-4 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">1</span>
                        <div>
                            <p class="font-semibold text-heading">Choose a doctor</p>
                            <p class="mt-1 text-sm leading-6 text-text">Filter the directory by name or specialty and open a profile.</p>
                        </div>
                    </li>
                    <li class="surface flex gap-4 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">2</span>
                        <div>
                            <p class="font-semibold text-heading">Request a time</p>
                            <p class="mt-1 text-sm leading-6 text-text">Pick a date, an open slot, and the reason for the visit.</p>
                        </div>
                    </li>
                    <li class="surface flex gap-4 p-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">3</span>
                        <div>
                            <p class="font-semibold text-heading">Follow the status</p>
                            <p class="mt-1 text-sm leading-6 text-text">The clinic confirms, completes, or cancels the appointment. You see the same status.</p>
                        </div>
                    </li>
                </ol>
            </div>
            <div class="rounded-[32px] bg-navy p-8 text-white sm:p-10">
                <p class="text-sm font-semibold uppercase tracking-[0.16em] text-white/70">Ready when you are</p>
                <h2 class="mt-3 text-3xl font-extrabold leading-tight">Start with a doctor search, or create an account first.</h2>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('doctors.index') }}" class="inline-flex h-12 items-center rounded-xl bg-white px-5 text-sm font-semibold text-primary no-underline">Find a doctor</a>
                    <a href="{{ route('register') }}" class="inline-flex h-12 items-center rounded-xl border border-white/30 px-5 text-sm font-semibold text-white no-underline">Create an account</a>
                </div>
            </div>
        </div>
    </section>
@endsection
