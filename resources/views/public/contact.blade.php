@extends('layouts.public')

@section('title', 'Contact')
@section('description', 'Send Medicare a message about a booking, your account, or a question. We reply by email.')

@section('content')
    <section class="px-5 py-8 sm:px-8 sm:py-12 lg:px-12 lg:py-16">
        <div class="surface mx-auto grid w-full max-w-6xl overflow-hidden shadow-lift lg:grid-cols-2">
            <div class="relative min-h-[280px] min-w-0 sm:min-h-[340px] lg:min-h-full">
                <img
                    src="{{ asset('images/login.png') }}"
                    alt="A doctor sitting with a patient"
                    class="absolute inset-0 h-full w-full object-cover object-[center_28%]"
                >
                <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(14,28,51,0.08)_0%,rgba(14,28,51,0.02)_38%,rgba(14,28,51,0.55)_72%,rgba(14,28,51,0.88)_100%)]"></div>
                <div class="relative flex h-full w-full min-w-0 flex-col justify-end p-6 text-white sm:p-8 lg:p-10">
                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/70">Support</p>
                    <h1 class="mt-2 max-w-xs text-3xl font-extrabold tracking-tight sm:text-4xl">We're here to help</h1>
                    <p class="mt-3 max-w-sm text-sm leading-6 text-white/85 sm:text-[15px]">
                        Questions about a booking, your account, or a fee. Write to us and we reply by email.
                    </p>
                </div>
            </div>

            <div class="min-w-0 px-5 py-7 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                <h2 class="text-2xl font-extrabold tracking-tight text-heading">Send a message</h2>
                <p class="mt-2 text-sm leading-6 text-text">Your note stays private. It is used only to reply, and it is not published on the site.</p>

                @if (session('status'))
                    <div class="mt-6 flex gap-3 rounded-xl border border-[#BBF7D0] bg-mint px-4 py-3 text-sm text-success" role="status">
                        <x-ui.icon name="check" class="mt-0.5 h-5 w-5 shrink-0" />
                        <p class="font-medium leading-6">{{ session('status') }}</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.store') }}" class="mt-7">
                    @csrf

                    <div class="sr-only" aria-hidden="true">
                        <label for="company">Company</label>
                        <input id="company" type="text" name="company" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid min-w-0 gap-5 sm:grid-cols-2">
                        <div>
                            <label for="name" class="label">Name</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                                    <x-ui.icon name="user" class="h-4 w-4" />
                                </span>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ old('name', auth()->user()?->name) }}"
                                    placeholder="Your full name"
                                    class="field h-12 rounded-xl bg-[#F8FAFC] pl-11"
                                    required
                                    maxlength="120"
                                    autocomplete="name"
                                    @error('name') aria-invalid="true" @enderror
                                >
                            </div>
                            @error('name')
                                <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="label">Email</label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                                    <x-ui.icon name="mail" class="h-4 w-4" />
                                </span>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ old('email', auth()->user()?->email) }}"
                                    placeholder="you@example.com"
                                    class="field h-12 rounded-xl bg-[#F8FAFC] pl-11"
                                    required
                                    maxlength="255"
                                    autocomplete="email"
                                    @error('email') aria-invalid="true" @enderror
                                >
                            </div>
                            @error('email')
                                <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="label">Phone <span class="font-normal text-muted">(optional)</span></label>
                            <div class="relative">
                                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-muted">
                                    <x-ui.icon name="phone" class="h-4 w-4" />
                                </span>
                                <input
                                    id="phone"
                                    type="tel"
                                    name="phone"
                                    value="{{ old('phone', auth()->user()?->phone) }}"
                                    placeholder="Mobile number"
                                    class="field h-12 rounded-xl bg-[#F8FAFC] pl-11"
                                    maxlength="30"
                                    autocomplete="tel"
                                    @error('phone') aria-invalid="true" @enderror
                                >
                            </div>
                            @error('phone')
                                <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="subject" class="label">Topic</label>
                            <div class="relative">
                                <select
                                    id="subject"
                                    name="subject"
                                    required
                                    class="field h-12 appearance-none rounded-xl bg-[#F8FAFC] bg-none pl-4 pr-10"
                                    @error('subject') aria-invalid="true" @enderror
                                >
                                    <option value="" disabled @selected(old('subject') === null || old('subject') === '')>Choose a topic</option>
                                    @foreach ($subjects as $value => $label)
                                        <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <span class="pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-muted">
                                    <x-ui.icon name="chevron" class="h-4 w-4" />
                                </span>
                            </div>
                            @error('subject')
                                <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-5">
                        <label for="message" class="label">Message</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="5"
                            required
                            maxlength="2000"
                            placeholder="How can we help?"
                            class="field rounded-xl bg-[#F8FAFC] px-4"
                            @error('message') aria-invalid="true" @enderror
                        >{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <ul class="mt-6 grid gap-3 sm:grid-cols-3">
                        <li class="flex items-center gap-2.5 text-sm text-text">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-light text-primary">
                                <x-ui.icon name="mail" class="h-4 w-4" />
                            </span>
                            Reply by email
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-text">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-light text-primary">
                                <x-ui.icon name="shield" class="h-4 w-4" />
                            </span>
                            Kept private
                        </li>
                        <li class="flex items-center gap-2.5 text-sm text-text">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-light text-primary">
                                <x-ui.icon name="file" class="h-4 w-4" />
                            </span>
                            <a href="{{ route('faq') }}" class="font-semibold text-primary no-underline hover:text-primary-hover">Read the FAQ</a>
                        </li>
                    </ul>

                    <button type="submit" class="btn-primary mt-6 h-12 w-full rounded-xl text-[15px]">
                        <x-ui.icon name="send" class="h-4 w-4" />
                        Send message
                    </button>
                </form>
            </div>
        </div>
    </section>
@endsection
