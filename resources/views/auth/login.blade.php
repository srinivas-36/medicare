@extends('layouts.public')

@section('title', 'Log in')

@section('description', 'Sign in to manage appointments and stay connected with your care team.')

@section('content')
    <div class="relative bg-[#F3F8FD]">
        <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -left-24 -top-28 h-[420px] w-[420px] rounded-full bg-[#D7E9FF] blur-3xl"></div>
            <div class="absolute bottom-[-140px] left-[6%] h-[380px] w-[520px] rounded-full bg-[#E7F2FF] blur-2xl"></div>
            <div class="absolute -right-24 top-[-80px] h-[340px] w-[340px] rounded-full bg-[#EAF3FF] blur-3xl"></div>
            <div class="absolute left-[38%] top-16 hidden h-20 w-28 lg:block" style="background-image: radial-gradient(circle, #C5D7EE 1.35px, transparent 1.45px); background-size: 13px 13px;"></div>
        </div>

        <div class="relative mx-auto flex w-full max-w-[1180px] items-center px-5 py-12 sm:px-8 sm:py-16 lg:px-6">
            <div class="grid w-full items-center gap-10 lg:grid-cols-[minmax(0,1fr)_440px] lg:gap-14">
                <section class="min-w-0">
                    <!-- <a href="{{ route('home') }}" class="inline-flex">
                        <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="h-11 w-auto">
                    </a> -->

                    <h1 class="mt-8 max-w-[520px] text-[42px] font-extrabold leading-[1.05] tracking-[-0.03em] text-[#121826] sm:text-[56px]">
                        Your care,<br>
                        <span class="text-primary">connected.</span>
                    </h1>
                    <p class="mt-4 max-w-[420px] text-base leading-7 text-[#5E6876] sm:text-[17px]">
                        Sign in to manage appointments and stay connected with your care team.
                    </p>

                    <div class="relative mt-8 max-w-[540px] sm:mt-10">
                        <img
                            src="{{ asset('images/login.png') }}"
                            alt="A doctor sitting with a patient"
                            class="w-full rounded-[28px] object-cover shadow-[0_24px_50px_rgba(0,103,255,0.10)]"
                        >

                        <div class="absolute -left-1 top-8 flex h-16 w-16 items-center justify-center rounded-full bg-white text-[#5B9BFF] shadow-[0_10px_24px_rgba(0,103,255,0.16)] ring-[6px] ring-[#E7F2FF] sm:-left-3 sm:top-12 sm:h-[72px] sm:w-[72px]" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19.5 12.6c1.5-1.5 2.5-3 2.5-4.8A4.8 4.8 0 0 0 17.2 3c-1.6 0-2.8.6-4.2 2.2C11.6 3.6 10.4 3 8.8 3A4.8 4.8 0 0 0 4 7.8c0 1.8 1 3.3 2.5 4.8l6.5 6.4 6.5-6.4Z"/>
                                <path d="M3.2 12h4.6l1.1-2.2 2.1 4.4 1.5-2.2h8.3"/>
                            </svg>
                        </div>

                        <div class="absolute -right-1 top-14 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#2F7DFF] text-white shadow-[0_12px_28px_rgba(0,103,255,0.28)] sm:right-1 sm:top-[4.5rem] sm:h-16 sm:w-16" aria-hidden="true">
                            <svg viewBox="0 0 24 24" class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 3 5.5 6v6.2c0 4.3 2.7 7.2 6.5 8.5 3.8-1.3 6.5-4.2 6.5-8.5V6L12 3Z"/>
                                <path d="M12 9v6M9 12h6"/>
                            </svg>
                        </div>
                    </div>
                </section>

                <section class="w-full justify-self-end">
                    <div class="rounded-[28px] bg-white px-6 py-8 shadow-[0_20px_60px_rgba(16,36,74,0.08)] sm:px-10 sm:py-10">
                        <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="h-9 w-auto">

                        <p class="mt-6 text-[13px] font-bold uppercase tracking-[0.16em] text-primary">Welcome back</p>
                        <h2 class="mt-2 text-[30px] font-extrabold leading-tight tracking-tight text-[#121826]">Log in to Medicare</h2>
                        <p class="mt-2 text-sm leading-6 text-[#667085]">Use the email and password from your account to open your dashboard.</p>

                        <form method="POST" action="{{ route('login') }}" class="mt-7">
                            @csrf

                            @if (session('status'))
                                <p class="mb-5 rounded-xl bg-[#EAF3FF] px-4 py-3 text-sm font-medium text-primary">{{ session('status') }}</p>
                            @endif

                            <div>
                                <label for="email" class="mb-2 block text-sm font-semibold text-[#1A1D23]">Email</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[#98A2B3]">
                                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="3" y="5" width="18" height="14" rx="2"/>
                                            <path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        placeholder="you@example.com"
                                        value="{{ old('email') }}"
                                        @class([
                                            'field h-12 rounded-xl pl-11',
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/15' => $errors->has('email'),
                                        ])
                                        required
                                        autofocus
                                        autocomplete="username"
                                    >
                                </div>
                                @error('email')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="mt-5" x-data="{ show: false }">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <label for="password" class="text-sm font-semibold text-[#1A1D23]">Password</label>
                                    @if (Route::has('password.request'))
                                        <a href="{{ route('password.request') }}" class="text-sm font-semibold text-primary no-underline hover:text-primary-hover">Forgot password?</a>
                                    @endif
                                </div>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-[#98A2B3]">
                                        <svg viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <rect x="5" y="11" width="14" height="10" rx="2"/>
                                            <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke-linecap="round"/>
                                        </svg>
                                    </span>
                                    <input
                                        id="password"
                                        :type="show ? 'text' : 'password'"
                                        type="password"
                                        name="password"
                                        placeholder="Enter your password"
                                        @class([
                                            'field h-12 rounded-xl pl-11 pr-11',
                                            'border-red-500 focus:border-red-500 focus:ring-red-500/15' => $errors->has('password'),
                                        ])
                                        required
                                        autocomplete="current-password"
                                    >
                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-[#98A2B3] transition hover:text-[#1A1D23]"
                                        @click="show = !show"
                                        :aria-label="show ? 'Hide password' : 'Show password'"
                                        :aria-pressed="show"
                                    >
                                        <svg x-show="!show" viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke-linecap="round" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <svg x-show="show" x-cloak viewBox="0 0 24 24" class="h-[18px] w-[18px]" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path d="M3 3l18 18" stroke-linecap="round"/>
                                            <path d="M10.6 10.6A3 3 0 0 0 12 15a3 3 0 0 0 2.2-.9" stroke-linecap="round"/>
                                            <path d="M9.9 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18.4 18.4 0 0 1-3.3 4.3" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M6.1 6.2C3.9 7.8 2 12 2 12s3.5 7 10 7c1.5 0 2.8-.3 4-.8" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('password')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <button type="submit" class="btn-primary mt-6 h-12 w-full rounded-xl text-[15px]">Log in</button>

                            <p class="mt-5 border-t border-[#EEF2F6] pt-5 text-center text-sm text-[#667085]">
                                New to Medicare?
                                <a href="{{ route('register') }}" class="font-semibold text-primary no-underline hover:text-primary-hover">Create an account</a>
                            </p>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </div>
@endsection
