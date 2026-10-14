@extends('layouts.dashboard')

@php
    $receptionist = $receptionist ?? auth()->user();
    $inputClass = 'h-12 w-full rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 text-base text-[#0B1F3A] placeholder:text-[#53647D] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';

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
    <x-ui.page-header title="Edit your details" description="Update the name, phone, and note that appear on your reception account." />

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-xl border border-line bg-white p-5 shadow-card">
            <h2 class="text-base font-semibold text-heading">Personal information</h2>
            <p class="mt-1 text-sm leading-6 text-[#53647D]">Email stays as the login address and cannot be changed here.</p>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <figure data-photo-preview class="{{ $photoUrl ? 'flex' : 'hidden' }} h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-[#1769FF]">
                    <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-full w-full object-cover">
                </figure>
                <div class="relative h-12 w-[140px] overflow-hidden">
                    <input type="file" name="photo" id="receptionist-photo" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0">
                    <label for="receptionist-photo" class="flex h-full w-full cursor-pointer items-center justify-center rounded-xl bg-[#1769FF46] text-sm font-semibold text-[#0B1F3A]">
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
                    <label for="name" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Full name</label>
                    <input id="name" type="text" name="name" value="{{ $name }}" required class="{{ $inputClass }}">
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="email" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Email</label>
                    <input id="email" type="email" value="{{ $email }}" readonly class="{{ $inputClass }} cursor-not-allowed bg-[#eef3fb] text-[#53647D]">
                </div>
                <div>
                    <label for="phone" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Phone</label>
                    <input id="phone" type="text" name="phone" value="{{ $phone }}" placeholder="Phone number" class="{{ $inputClass }}">
                    @error('phone')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="gender" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Gender</label>
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

           
        </section>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ $showUrl }}" class="inline-flex h-12 items-center justify-center rounded-xl border border-[#E2EAF4] bg-white px-6 text-base font-semibold text-[#0B1F3A] hover:bg-[#F4F7FB]">
                View profile
            </a>
            <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-[#1769FF] px-8 text-base font-semibold text-white hover:bg-[#0D57E5]">
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
