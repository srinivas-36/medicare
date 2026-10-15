@extends('layouts.public')

@section('title', 'Log in')

@section('content')
    <section class="px-5 py-16 sm:px-8 lg:px-12 lg:py-24">
        <div class="surface mx-auto w-full max-w-[480px] p-6 sm:p-10">
            <p class="text-sm font-semibold uppercase tracking-[0.14em] text-primary">Welcome back</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-heading">Log in to Medicare</h1>
            <p class="mt-2 text-sm leading-6 text-text">Use the email and password from your account to open your dashboard.</p>

            <form method="POST" action="{{ route('login') }}" class="mt-8">
                @csrf

                @if (session('status'))
                    <p class="mb-5 rounded-xl bg-[#EAF3FF] px-4 py-3 text-sm font-medium text-primary">{{ session('status') }}</p>
                @endif

                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-heading">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        placeholder="you@example.com"
                        value="{{ old('email') }}"
                        class="field"
                        required
                        autofocus
                        autocomplete="username"
                    >
                    @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-5">
                    <div class="mb-2 flex items-center justify-between gap-3">
                        <label for="password" class="text-sm font-semibold text-heading">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-primary no-underline hover:text-[#0D57E5]">Forgot password?</a>
                        @endif
                    </div>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter your password"
                        class="field"
                        required
                        autocomplete="current-password"
                    >
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="btn-primary mt-8 w-full">Log in</button>

                <p class="mt-5 text-center text-sm text-text">
                    New to Medicare?
                    <a href="{{ route('register') }}" class="font-semibold text-primary no-underline">Create an account</a>
                </p>
            </form>
        </div>
    </section>
@endsection
