@extends('layouts.dashboard')

@php
    $receptionist = $receptionist ?? auth()->user();
    $inputClass = 'h-12 w-full rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20';

    $name = old('name', data_get($receptionist, 'name', ''));
    $email = data_get($receptionist, 'email', '');
    $phone = old('phone', data_get($receptionist, 'phone', ''));
    $gender = old('gender', data_get($receptionist, 'gender', ''));
    $about = old('about', data_get($receptionist, 'about', ''));
    $genders = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

    $photo = data_get($receptionist, 'photo');
    $photoUrl = is_string($photo) && $photo !== ''
        ? (str_starts_with($photo, 'http') ? $photo : asset($photo))
        : null;

    $action = \Illuminate\Support\Facades\Route::has('receptionist.profile.update')
        ? route('receptionist.profile.update')
        : url('/receptionist/profile/edit');

    $showUrl = \Illuminate\Support\Facades\Route::has('receptionist.profile')
        ? route('receptionist.profile')
        : url('/receptionist/profile');
@endphp

@section('content')
    <div class="rounded-2xl bg-[#0067FF] p-6 text-white shadow-[0_12px_40px_rgba(0,103,255,0.22)] sm:p-8">
        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Receptionist profile</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight">Edit your details</h1>
        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/85">
            Update the name, phone, and note that appear on your reception account.
        </p>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <h2 class="text-xl font-bold text-[#181A1E]">Personal information</h2>
            <p class="mt-1 text-sm leading-6 text-[#4E545F]">Email stays as the login address and cannot be changed here.</p>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <figure data-photo-preview class="{{ $photoUrl ? 'flex' : 'hidden' }} h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-[#0067FF]">
                    <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-full w-full object-cover">
                </figure>
                <div class="relative h-12 w-[140px]">
                    <input type="file" name="photo" id="receptionist-photo" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0">
                    <label for="receptionist-photo" class="flex h-full w-full cursor-pointer items-center justify-center rounded-xl bg-[#0066ff46] text-sm font-semibold text-[#181A1E]">
                        Upload photo
                    </label>
                </div>
                @if ($photo)
                    <input type="hidden" name="existing_photo" value="{{ $photo }}">
                @endif
            </div>
            @error('photo')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-2 block text-sm font-semibold text-[#181A1E]">Full name</label>
                    <input id="name" type="text" name="name" value="{{ $name }}" required class="{{ $inputClass }}">
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-[#181A1E]">Email</label>
                    <input id="email" type="email" value="{{ $email }}" readonly class="{{ $inputClass }} cursor-not-allowed bg-[#eef3fb] text-[#4E545F]">
                </div>
                <div>
                    <label for="phone" class="mb-2 block text-sm font-semibold text-[#181A1E]">Phone</label>
                    <input id="phone" type="text" name="phone" value="{{ $phone }}" placeholder="Phone number" class="{{ $inputClass }}">
                    @error('phone')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="gender" class="mb-2 block text-sm font-semibold text-[#181A1E]">Gender</label>
                    <select id="gender" name="gender" class="{{ $inputClass }}">
                        <option value="">Select</option>
                        @foreach ($genders as $value => $label)
                            <option value="{{ $value }}" @selected($gender === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('gender')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-5">
                <label for="about" class="mb-2 block text-sm font-semibold text-[#181A1E]">About</label>
                <textarea id="about" name="about" rows="5" placeholder="A short note about your role at the desk." class="w-full resize-y rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 py-3 text-base leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20">{{ $about }}</textarea>
                @error('about')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ $showUrl }}" class="inline-flex h-12 items-center justify-center rounded-xl border border-[#c9dcff] bg-white px-6 text-base font-semibold text-[#181A1E] hover:bg-[#f7faff]">
                View profile
            </a>
            <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#0067FF] px-8 text-base font-semibold text-white hover:bg-[#0056d6]">
                Save profile
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        const photoInput = document.getElementById('receptionist-photo');
        const preview = document.querySelector('[data-photo-preview]');

        if (photoInput && preview) {
            photoInput.addEventListener('change', () => {
                const file = photoInput.files && photoInput.files[0];
                const image = preview.querySelector('img');

                if (!file || !image) {
                    return;
                }

                image.src = URL.createObjectURL(file);
                image.alt = file.name;
                preview.classList.remove('hidden');
                preview.classList.add('flex');
            });
        }
    </script>
@endpush
