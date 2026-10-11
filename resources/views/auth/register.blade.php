@extends('layouts.public')

@section('content')

<section class="px-5 py-[76px] xl:px-0">
    <div class="mx-auto max-w-[1050px]">
        <div class="grid grid-cols-1 lg:grid-cols-2">
            <div class="hidden rounded-l-lg bg-[#0067FF] lg:block">
                <figure class="rounded-l-lg">
                    <img src="{{ asset('images/signup.gif') }}" alt="" class="w-full rounded-l-lg">
                </figure>
            </div>

            <div class="rounded-l-lg py-10 lg:pl-16">
                <h3 class="mb-10 text-[22px] font-bold leading-9 text-[#181A1E]">
                    Create an <span class="text-[#0067FF]">Account</span>
                </h3>

                <form method="POST" action="{{ route('register') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-5">
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="FullName"
                            autocomplete="name"
                            required
                            class="w-full cursor-pointer border-b border-solid border-[#0066ff61] py-3 text-[16px] leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-b-[#0067FF] focus:outline-none"
                        >
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter Your Email"
                            autocomplete="username"
                            required
                            class="w-full cursor-pointer border-b border-solid border-[#0066ff61] py-3 text-[16px] leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-b-[#0067FF] focus:outline-none"
                        >
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <input
                            type="password"
                            name="password"
                            placeholder="Password"
                            autocomplete="new-password"
                            required
                            class="w-full cursor-pointer border-b border-solid border-[#0066ff61] py-3 text-[16px] leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-b-[#0067FF] focus:outline-none"
                        >
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <input
                            type="password"
                            name="password_confirmation"
                            placeholder="Confirm Password"
                            autocomplete="new-password"
                            required
                            class="w-full cursor-pointer border-b border-solid border-[#0066ff61] py-3 text-[16px] leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-b-[#0067FF] focus:outline-none"
                        >
                        @error('password_confirmation')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-5">
                        <input
                            type="number"
                            name="phone"
                            value="{{ old('phone') }}"
                            placeholder="Phone Number"
                            required
                            class="w-full cursor-pointer border-b border-solid border-[#0066ff61] py-3 text-[16px] leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-b-[#0067FF] focus:outline-none"
                        >
                    </div>

                    <div class="mb-5">
                        <input
                            type="number"
                            name="age"
                            value="{{ old('age') }}"
                            placeholder="Age"
                            required
                            class="w-full cursor-pointer border-b border-solid border-[#0066ff61] py-3 text-[16px] leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-b-[#0067FF] focus:outline-none"
                        >
                    </div>

                    <div class="mb-5 flex items-center justify-between">
                        <label class="text-[16px] font-bold leading-7 text-[#181A1E]">
                            Are you a :
                            <select name="role" class="px-4 text-[15px] font-semibold leading-7 text-[#4E545F] focus:outline-none">
                                <option value="patient" @selected(old('role', 'patient') === 'patient')>Patient</option>
                                <option value="doctor" @selected(old('role') === 'doctor')>Doctor</option>
                            </select>
                        </label>
                        <label class="text-[16px] font-bold leading-7 text-[#181A1E]">
                            Gender :
                            <select name="gender" class="px-4 text-[15px] font-semibold leading-7 text-[#4E545F] focus:outline-none">
                                <option value="" @selected(old('gender', '') === '')>Select</option>
                                <option value="male" @selected(old('gender') === 'male')>Male</option>
                                <option value="female" @selected(old('gender') === 'female')>Female</option>
                                <option value="other" @selected(old('gender') === 'other')>Other</option>
                            </select>
                        </label>
                    </div>

                    <div class="mb-2 flex items-center gap-3">
                        <figure data-photo-preview class="hidden h-[60px] w-[60px] items-center justify-center overflow-hidden rounded-full border-2 border-solid border-[#0067FF]">
                            <img src="" alt="" class="w-full rounded-full">
                        </figure>

                        <div class="relative h-[50px] w-[130px]">
                            <input
                                type="file"
                                name="photo"
                                id="customfile"
                                accept="image/*"
                                class="absolute left-0 top-0 h-full w-full cursor-pointer opacity-0"
                            >
                            <label
                                for="customfile"
                                class="absolute left-0 top-0 flex h-full w-full cursor-pointer items-center truncate overflow-hidden rounded-lg bg-[#0066ff46] px-[0.75rem] py-[0.375rem] text-[15px] font-semibold leading-6 text-[#181A1E]"
                            >
                                Upload Photo
                            </label>
                        </div>
                    </div>

                    <div class="mt-2">
                        <button type="submit" class="mt-3 w-full rounded-md bg-[#0067FF] px-[35px] py-[15px] font-semibold text-white">
                            Register
                        </button>
                    </div>

                    <p class="mt-5 text-center text-[#4E545F]">
                        Already Have an Account?
                        <a href="{{ route('login') }}" class="text-[#0067FF]">Login</a>
                    </p>
                </form>
            </div>
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
