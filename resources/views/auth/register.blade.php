@extends('layouts.public')

@section('title', 'Register')

@section('content')
    <section class="px-5 py-16 lg:py-20">
        <div class="surface mx-auto grid max-w-5xl overflow-hidden lg:grid-cols-[0.9fr_1.1fr]">
            <div class="relative hidden bg-primary p-10 text-white lg:flex lg:flex-col lg:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-white/70">Medicare</p>
                    <h2 class="mt-4 text-4xl font-extrabold leading-tight">Your visits, in one place.</h2>
                    <p class="mt-4 max-w-sm text-base leading-7 text-white/85">
                        Create a patient or doctor account, then book, manage, and follow appointments from your dashboard.
                    </p>
                </div>
                <img src="{{ asset('images/signup.gif') }}" alt="" class="mt-10 w-full rounded-2xl bg-white/10 object-cover">
            </div>

            <div class="p-6 sm:p-10">
                <h1 class="text-3xl font-extrabold tracking-tight text-heading">Create an account</h1>
                <p class="mt-2 text-sm leading-6 text-text">Patients can book visits. Doctors can build a profile for approval.</p>

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-8">
                    @csrf

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="mb-2 block text-sm font-semibold text-heading">Full name</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Your name" autocomplete="name" required class="field">
                            @error('name')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="mb-2 block text-sm font-semibold text-heading">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="username" required class="field">
                            @error('email')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-semibold text-heading">Password</label>
                            <input id="password" type="password" name="password" placeholder="Create a password" autocomplete="new-password" required class="field">
                            @error('password')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-heading">Confirm password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repeat your password" autocomplete="new-password" required class="field">
                            @error('password_confirmation')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-2 block text-sm font-semibold text-heading">Phone</label>
                            <input id="phone" type="number" name="phone" value="{{ old('phone') }}" placeholder="Phone number" required class="field">
                            @error('phone')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="age" class="mb-2 block text-sm font-semibold text-heading">Age</label>
                            <input id="age" type="number" name="age" value="{{ old('age') }}" placeholder="Age" required class="field">
                            @error('age')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="role" class="mb-2 block text-sm font-semibold text-heading">I am a</label>
                            <select id="role" name="role" class="field">
                                <option value="patient" @selected(old('role', 'patient') === 'patient')>Patient</option>
                                <option value="doctor" @selected(old('role') === 'doctor')>Doctor</option>
                            </select>
                            @error('role')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="gender" class="mb-2 block text-sm font-semibold text-heading">Gender</label>
                            <select id="gender" name="gender" class="field">
                                <option value="" @selected(old('gender', '') === '')>Select</option>
                                <option value="male" @selected(old('gender') === 'male')>Male</option>
                                <option value="female" @selected(old('gender') === 'female')>Female</option>
                                <option value="other" @selected(old('gender') === 'other')>Other</option>
                            </select>
                            @error('gender')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-3">
                        <figure data-photo-preview class="hidden h-14 w-14 items-center justify-center overflow-hidden rounded-full ring-2 ring-primary">
                            <img src="" alt="" class="h-full w-full object-cover">
                        </figure>
                        <div class="relative">
                            <input type="file" name="photo" id="customfile" accept="image/*" class="absolute inset-0 h-full w-full cursor-pointer opacity-0">
                            <label for="customfile" class="btn-secondary h-11 cursor-pointer px-4">Upload photo</label>
                        </div>
                    </div>
                    @error('photo')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="btn-primary mt-8 w-full">Create account</button>

                    <p class="mt-5 text-center text-sm text-text">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-semibold text-primary no-underline">Log in</a>
                    </p>
                </form>
            </div>
        </div>
    </section>

    <script>
        const photoInput = document.getElementById('customfile');
        const preview = document.querySelector('[data-photo-preview]');

        if (photoInput && preview) {
            photoInput.addEventListener('change', () => {
                const file = photoInput.files && photoInput.files[0];
                const image = preview.querySelector('img');

                if (!file || !image) {
                    preview.classList.add('hidden');
                    preview.classList.remove('flex');
                    return;
                }

                image.src = URL.createObjectURL(file);
                preview.classList.remove('hidden');
                preview.classList.add('flex');
            });
        }
    </script>
@endsection
