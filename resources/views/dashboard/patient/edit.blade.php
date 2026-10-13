@extends('layouts.dashboard')

@php
    $patient = $patient ?? auth()->user();
    $inputClass = 'h-12 w-full rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 text-base text-[#0B1F3A] placeholder:text-[#53647D] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';

    $name = old('name', data_get($patient, 'name', ''));
    $email = data_get($patient, 'email', '');
    $phone = old('phone', data_get($patient, 'phone', ''));
    $age = old('age', data_get($patient, 'age', ''));
    $gender = old('gender', data_get($patient, 'gender', ''));
    $bloodType = old('bloodType', data_get($patient, 'bloodType', ''));
    $genders = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

    $known = function ($key) use ($patient) {
        $value = old($key.'.known', data_get($patient, $key.'.known', true));

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    };

    $bpValue = old('bp.value', data_get($patient, 'bp.value', ''));
    $diabeticValue = old('diabetic.value', data_get($patient, 'diabetic.value', ''));
    $thyroidValue = old('hyperthyroidism.value', data_get($patient, 'hyperthyroidism.value', ''));

    $photo = data_get($patient, 'photo');
    $photoUrl = is_string($photo) && $photo !== ''
        ? (str_starts_with($photo, 'http') ? $photo : asset($photo))
        : null;

    $action = \Illuminate\Support\Facades\Route::has('patient.profile.update')
        ? route('patient.profile.update')
        : url('/patient/profile/edit');

    $showUrl = \Illuminate\Support\Facades\Route::has('patient.profile.show')
        ? route('patient.profile.show')
        : url('/patient/profile');
@endphp

@section('content')
    <div class="dash-banner">
        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Patient profile</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight">Edit your details</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            Keep your contact details and health notes current so bookings stay accurate.
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">Settings</span>
            <span class="dash-chip">{{ $email !== '' ? $email : 'Patient account' }}</span>
        </div>
    </div>

    @if (session('status'))
        <p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
    @endif

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-6 space-y-6">
        @csrf
        @method('PUT')

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <h2 class="text-xl font-bold text-[#0B1F3A]">Personal information</h2>
            <p class="mt-1 text-sm leading-6 text-[#53647D]">Email stays as the login address and cannot be changed here.</p>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <figure data-photo-preview class="{{ $photoUrl ? 'flex' : 'hidden' }} h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-[#1769FF]">
                    <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-full w-full object-cover">
                </figure>
                <div class="relative h-12 w-[140px]">
                    <input type="file" name="photo" id="patient-photo" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0">
                    <label for="patient-photo" class="flex h-full w-full cursor-pointer items-center justify-center rounded-xl bg-[#1769FF46] text-sm font-semibold text-[#0B1F3A]">
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
                    <input id="phone" type="text" name="phone" value="{{ $phone }}" placeholder="Phone number" required class="{{ $inputClass }}">
                    @error('phone')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="age" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Age</label>
                    <input id="age" type="number" min="0" name="age" value="{{ $age }}" placeholder="Age" class="{{ $inputClass }}">
                    @error('age')
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
                <div>
                    <label for="bloodType" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Blood type</label>
                    <input id="bloodType" type="text" name="bloodType" value="{{ $bloodType }}" placeholder="O+" class="{{ $inputClass }}">
                    @error('bloodType')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <h2 class="text-xl font-bold text-[#0B1F3A]">Health notes</h2>
            <p class="mt-1 text-sm leading-6 text-[#53647D]">Check “I don’t know” when you are unsure. Leave the value blank in that case.</p>

            <div class="mt-6 grid gap-5 lg:grid-cols-3">
                <div>
                    <label for="bp" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Blood pressure</label>
                    <input id="bp" type="text" name="bp[value]" value="{{ $bpValue }}" placeholder="120/80" class="{{ $inputClass }}">
                    <label class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-[#53647D]">
                        <input type="hidden" name="bp[known]" value="1">
                        <input type="checkbox" name="bp[known]" value="0" class="h-4 w-4 rounded border-[#E2EAF4] text-[#1769FF] focus:ring-[#1769FF]" @checked(! $known('bp'))>
                        I don’t know
                    </label>
                    @error('bp.value')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="diabetic" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Diabetic</label>
                    <select id="diabetic" name="diabetic[value]" class="{{ $inputClass }}">
                        <option value="">Select</option>
                        <option value="yes" @selected($diabeticValue === 'yes')>Yes</option>
                        <option value="no" @selected($diabeticValue === 'no')>No</option>
                    </select>
                    <label class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-[#53647D]">
                        <input type="hidden" name="diabetic[known]" value="1">
                        <input type="checkbox" name="diabetic[known]" value="0" class="h-4 w-4 rounded border-[#E2EAF4] text-[#1769FF] focus:ring-[#1769FF]" @checked(! $known('diabetic'))>
                        I don’t know
                    </label>
                    @error('diabetic.value')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="hyperthyroidism" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Hyperthyroidism</label>
                    <select id="hyperthyroidism" name="hyperthyroidism[value]" class="{{ $inputClass }}">
                        <option value="">Select</option>
                        <option value="yes" @selected($thyroidValue === 'yes')>Yes</option>
                        <option value="no" @selected($thyroidValue === 'no')>No</option>
                    </select>
                    <label class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-[#53647D]">
                        <input type="hidden" name="hyperthyroidism[known]" value="1">
                        <input type="checkbox" name="hyperthyroidism[known]" value="0" class="h-4 w-4 rounded border-[#E2EAF4] text-[#1769FF] focus:ring-[#1769FF]" @checked(! $known('hyperthyroidism'))>
                        I don’t know
                    </label>
                    @error('hyperthyroidism.value')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </section>

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <h2 class="text-xl font-bold text-[#0B1F3A]">Password</h2>
            <p class="mt-1 text-sm leading-6 text-[#53647D]">Leave both fields blank to keep your current password.</p>

            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="password" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">New password</label>
                    <input id="password" type="password" name="password" autocomplete="new-password" placeholder="New password" class="{{ $inputClass }}">
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Confirm password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" placeholder="Confirm password" class="{{ $inputClass }}">
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
        const photoInput = document.getElementById('patient-photo');
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
