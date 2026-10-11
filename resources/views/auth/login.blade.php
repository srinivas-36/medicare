@extends('layouts.public')

@section('content')

@php
$registerUrl = \Illuminate\Support\Facades\Route::has('register')
? route('register')
: url('/register');
@endphp

<main class="overflow-hidden">
    <section class="px-5 py-[35px] lg:px-0 lg:py-[76px]">
        <div class="mx-auto w-full max-w-[570px] rounded-lg shadow-md md:p-10">
            <h3 class="mb-10 text-[22px] font-bold leading-9 text-[#181A1E]">
                Hello ! <span class="text-[#0067FF]">Welcome</span> Back
            </h3>

            <form method="POST" action="{{ route('login') }}" class="py-4 md:py-0">
                @csrf

                @if (session('status'))
                <p class="mb-5 text-sm font-medium text-[#0067FF]">{{ session('status') }}</p>
                @endif

                <div class="mb-5">
                    <input
                        id="email"
                        type="email"
                        name="email"
                        placeholder="Enter Your Email"
                        value="{{ old('email') }}"
                        class="w-full cursor-pointer border-b border-solid border-[#0066ff61] py-3 leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-b-[#0067FF] focus:outline-none"
                        required
                        autofocus
                        autocomplete="username">
                    @error('email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        placeholder="Enter Your Password"
                        class="w-full cursor-pointer border-b border-solid border-[#0066ff61] py-3 leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-b-[#0067FF] focus:outline-none"
                        required
                        autocomplete="current-password">
                    @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mt-7">
                    <button type="submit" class="mt-[30px] w-full rounded-md bg-[#0067FF] px-[35px] py-[15px] font-semibold text-white">
                        login
                    </button>
                </div>

                <p class="mt-5 text-center text-[#4E545F]">
                    Don't Have an Account?
                    <a href="{{ $registerUrl }}" class="text-[#0067FF]">Register</a>
                </p>
            </form>
        </div>
    </section>
</main>

@endsection