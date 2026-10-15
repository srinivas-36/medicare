@extends('layouts.public')

@section('title', 'Register')

@section('content')
    <section class="px-5 py-10 sm:px-8 sm:py-14 lg:px-12 lg:py-16">
        <div class="surface mx-auto grid w-full max-w-7xl overflow-hidden lg:grid-cols-[minmax(380px,480px)_minmax(0,1fr)]">
            <div class="hidden h-full flex-col bg-primary px-8 py-8 text-white lg:flex">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-white/70">Medicare</p>
                <h2 class="mt-3 text-[32px] font-extrabold leading-tight">Your visits, in one place.</h2>
                <p class="mt-3 text-sm leading-6 text-white/85">
                    Create a patient or doctor account, then book and follow appointments from your dashboard.
                </p>
                <ul class="mt-5 space-y-2 text-sm leading-5 text-white/90">
                    <li>Patients book approved doctors.</li>
                    <li>Doctors submit a profile for review.</li>
                    <li>Each role opens its own dashboard.</li>
                </ul>
                <img src="{{ asset('images/signup.gif') }}" alt="" class="mt-6 min-h-[280px] w-full flex-1 rounded-xl bg-white/10 object-contain object-center">
            </div>

            <div class="px-5 py-7 sm:px-8">
                <h1 class="text-2xl font-extrabold tracking-tight text-heading">Create an account</h1>
                <p class="mt-1 text-sm leading-5 text-text">All fields are required except the photo.</p>

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data" class="mt-5" novalidate data-register-form>
                    @csrf

                    <div class="grid gap-x-4 gap-y-3 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="name" class="mb-1.5 block text-sm font-semibold text-heading">Full name</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Your name" autocomplete="name" required maxlength="255" pattern="[A-Za-z]+(?:[ .'\-][A-Za-z]+)*" class="field" data-rule="name" @error('name') aria-invalid="true" @enderror>
                            <p class="mt-1 text-xs text-red-600 {{ $errors->has('name') ? '' : 'hidden' }}" data-error-for="name">{{ $errors->first('name') }}</p>
                        </div>

                        <div class="sm:col-span-2">
                            <label for="email" class="mb-1.5 block text-sm font-semibold text-heading">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" autocomplete="username" required maxlength="255" pattern="[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,}" class="field" data-rule="email" @error('email') aria-invalid="true" @enderror>
                            <p class="mt-1 text-xs text-red-600 {{ $errors->has('email') ? '' : 'hidden' }}" data-error-for="email">{{ $errors->first('email') }}</p>
                        </div>

                        <div>
                            <label for="password" class="mb-1.5 block text-sm font-semibold text-heading">Password</label>
                            <input id="password" type="password" name="password" placeholder="At least 8 characters" autocomplete="new-password" required minlength="8" pattern="(?=.*[A-Za-z])(?=.*\d).{8,}" class="field" data-rule="password" @error('password') aria-invalid="true" @enderror>
                            <p class="mt-1 text-xs text-red-600 {{ $errors->has('password') ? '' : 'hidden' }}" data-error-for="password">{{ $errors->first('password') }}</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-heading">Confirm password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Repeat your password" autocomplete="new-password" required class="field" data-rule="password_confirmation" @error('password') aria-invalid="true" @enderror>
                            <p class="mt-1 text-xs text-red-600 hidden" data-error-for="password_confirmation"></p>
                        </div>

                        <div>
                            <label for="phone" class="mb-1.5 block text-sm font-semibold text-heading">Phone</label>
                            <input id="phone" type="text" name="phone" value="{{ old('phone') }}" placeholder="Enter your phone number" inputmode="numeric" autocomplete="tel" required maxlength="10" pattern="[0-9]{10}" class="field" data-rule="phone" @error('phone') aria-invalid="true" @enderror>
                            <p class="mt-1 text-xs text-red-600 {{ $errors->has('phone') ? '' : 'hidden' }}" data-error-for="phone">{{ $errors->first('phone') }}</p>
                        </div>

                        <div>
                            <label for="age" class="mb-1.5 block text-sm font-semibold text-heading">Age</label>
                            <input id="age" type="text" name="age" value="{{ old('age') }}" placeholder="Enter your age" inputmode="numeric" required maxlength="2" pattern="[0-9]{2}" class="field" data-rule="age" @error('age') aria-invalid="true" @enderror>
                            <p class="mt-1 text-xs text-red-600 {{ $errors->has('age') ? '' : 'hidden' }}" data-error-for="age">{{ $errors->first('age') }}</p>
                        </div>

                        <div>
                            <label for="role" class="mb-1.5 block text-sm font-semibold text-heading">I am a</label>
                            <select id="role" name="role" required class="field" data-rule="role" @error('role') aria-invalid="true" @enderror>
                                <option value="patient" @selected(old('role', 'patient') === 'patient')>Patient</option>
                                <option value="doctor" @selected(old('role') === 'doctor')>Doctor</option>
                            </select>
                            <p class="mt-1 text-xs text-red-600 {{ $errors->has('role') ? '' : 'hidden' }}" data-error-for="role">{{ $errors->first('role') }}</p>
                        </div>

                        <div>
                            <label for="gender" class="mb-1.5 block text-sm font-semibold text-heading">Gender</label>
                            <select id="gender" name="gender" required class="field" data-rule="gender" @error('gender') aria-invalid="true" @enderror>
                                <option value="" @selected(old('gender', '') === '')>Select</option>
                                <option value="male" @selected(old('gender') === 'male')>Male</option>
                                <option value="female" @selected(old('gender') === 'female')>Female</option>
                                <option value="other" @selected(old('gender') === 'other')>Other</option>
                            </select>
                            <p class="mt-1 text-xs text-red-600 {{ $errors->has('gender') ? '' : 'hidden' }}" data-error-for="gender">{{ $errors->first('gender') }}</p>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center gap-3">
                        <figure data-photo-preview class="hidden h-11 w-11 items-center justify-center overflow-hidden rounded-full ring-2 ring-primary">
                            <img src="" alt="" class="h-full w-full object-cover">
                        </figure>
                        <div class="relative h-10 overflow-hidden">
                            <input type="file" name="photo" id="customfile" accept="image/png,image/jpeg,image/jpg,image/webp" class="absolute inset-0 h-full w-full cursor-pointer opacity-0" data-rule="photo">
                            <label for="customfile" class="btn-secondary h-10 cursor-pointer px-4">Upload photo</label>
                        </div>
                        <p class="text-xs text-text">Optional. JPG or PNG, up to 2 MB.</p>
                    </div>
                    <p class="mt-1 text-xs text-red-600 {{ $errors->has('photo') ? '' : 'hidden' }}" data-error-for="photo">{{ $errors->first('photo') }}</p>

                    <button type="submit" class="btn-primary mt-5 w-full">Create account</button>

                    <p class="mt-3 text-center text-sm text-text">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-semibold text-primary no-underline">Log in</a>
                    </p>
                </form>
            </div>
        </div>
    </section>

    <script>
        const form = document.querySelector('[data-register-form]');
        const photoInput = document.getElementById('customfile');
        const preview = document.querySelector('[data-photo-preview]');
        const messages = {
            name: 'Use letters, spaces, apostrophes, or hyphens only.',
            email: 'Enter a valid email address.',
            password: 'Use at least 8 characters, including a letter and a number.',
            password_confirmation: 'Passwords do not match.',
            phone: 'Enter a 10-digit phone number using numbers only.',
            age: 'Enter a 2-digit age using numbers only.',
            role: 'Choose patient or doctor.',
            gender: 'Select a gender.',
            photo: 'Upload a JPG or PNG photo up to 2 MB.',
        };

        const showError = (name, message) => {
            const field = form.querySelector(`[data-error-for="${name}"]`);
            const input = form.querySelector(`[data-rule="${name}"]`);

            if (!field) {
                return;
            }

            field.textContent = message;
            field.classList.toggle('hidden', message === '');

            if (input) {
                input.setAttribute('aria-invalid', message === '' ? 'false' : 'true');
            }
        };

        const checkField = (input) => {
            const name = input.dataset.rule;

            if (name === 'photo') {
                const file = input.files && input.files[0];

                if (!file) {
                    showError(name, '');
                    return true;
                }

                const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                const message = !allowed.includes(file.type) || file.size > 2 * 1024 * 1024
                    ? messages.photo
                    : '';
                showError(name, message);
                return message === '';
            }

            if (name === 'password_confirmation') {
                const password = form.querySelector('[data-rule="password"]');
                const message = input.value === '' || input.value !== password.value
                    ? messages.password_confirmation
                    : '';
                showError(name, message);
                return message === '';
            }

            const message = input.checkValidity() ? '' : messages[name];
            showError(name, message);
            return message === '';
        };

        if (form) {
            form.querySelectorAll('[data-rule]').forEach((input) => {
                input.addEventListener('blur', () => checkField(input));
                input.addEventListener('input', () => {
                    if (input.dataset.rule === 'phone' || input.dataset.rule === 'age') {
                        const digits = input.value.replace(/\D/g, '').slice(0, input.maxLength);
                        if (input.value !== digits) {
                            input.value = digits;
                        }
                    }

                    if (input.getAttribute('aria-invalid') === 'true') {
                        checkField(input);
                    }
                });
            });

            form.addEventListener('submit', (event) => {
                let valid = true;

                form.querySelectorAll('[data-rule]').forEach((input) => {
                    if (!checkField(input)) {
                        valid = false;
                    }
                });

                if (!valid) {
                    event.preventDefault();
                }
            });
        }

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
