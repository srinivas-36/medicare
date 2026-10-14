@extends('layouts.dashboard')

@php
$doctor = $doctor ?? auth()->user();
$inputClass = 'h-11 w-full rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm text-[#181A1E] placeholder:text-[#94A3B8] focus:border-[#0067FF] focus:outline-none focus:ring-4 focus:ring-[#0067FF]/15';

$name = old('name', data_get($doctor, 'name', ''));
$email = data_get($doctor, 'email', '');
$phone = old('phone', data_get($doctor, 'phone', ''));
$gender = old('gender', data_get($doctor, 'gender', ''));
$bio = old('bio', data_get($doctor, 'bio', ''));
$about = old('about', data_get($doctor, 'about', ''));
$ticketPrice = old('ticketPrice', data_get($doctor, 'ticketPrice', ''));
$specialization = old('specialization', data_get($doctor, 'specialization', ''));
$isPending = data_get($doctor, 'isApproved') === 'pending';

$specializations = ['Surgeon', 'Neurologist', 'Dermatologist'];
if ($specialization !== '' && ! in_array($specialization, $specializations, true)) {
$specializations[] = $specialization;
}

$blankQualification = ['startingDate' => '', 'endingDate' => '', 'degree' => '', 'university' => ''];
$blankExperience = ['startingDate' => '', 'endingDate' => '', 'position' => '', 'hospital' => ''];

$qualifications = old('qualifications', data_get($doctor, 'qualifications', []));
$experiences = old('experiences', data_get($doctor, 'experiences', []));
$timeSlots = old('timeSlots', data_get($doctor, 'timeSlots', []));

$qualifications = is_array($qualifications) && count($qualifications) ? array_values($qualifications) : [$blankQualification];
$experiences = is_array($experiences) && count($experiences) ? array_values($experiences) : [$blankExperience];
$timeSlots = is_array($timeSlots) ? array_values($timeSlots) : [];

$appointmentDuration = (int) old('appointmentDuration', data_get($doctor, 'appointmentDuration', 30));
$appointmentBuffer = (int) old('appointmentBuffer', data_get($doctor, 'appointmentBuffer', 0));

$photo = data_get($doctor, 'photo');
$photoUrl = is_string($photo) && $photo !== ''
? (str_starts_with($photo, 'http') ? $photo : asset($photo))
: null;

$genders = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

$action = \Illuminate\Support\Facades\Route::has('doctor.profile.update')
? route('doctor.profile.update')
: url('/doctors/profile/edit');

$showUrl = \Illuminate\Support\Facades\Route::has('doctor.profile.show')
? route('doctor.profile.show')
: url('/doctor/profile');
@endphp

@section('content')
<x-ui.page-header title="Edit Profile" description="Update your professional information.">
    <a href="{{ $showUrl }}" class="text-sm font-semibold text-[#0067FF] no-underline">View profile</a>
</x-ui.page-header>

@if (session('status'))
<p class="mt-6 rounded-2xl border border-[#b7ebc9] bg-[#e8f8ef] px-5 py-4 text-sm font-medium text-[#0f9f6e]">{{ session('status') }}</p>
@endif

@if ($isPending)
<div class="mt-6 rounded-2xl border border-[#f3d7a1] bg-[#fff7e8] px-5 py-4 text-sm leading-6 text-[#8a5a12]">
    Your profile is waiting for approval. Complete these details and we’ll review it within 3 days.
</div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="mt-6 space-y-6">
    @csrf
    @method('PUT')

    <section class="rounded-xl border border-line bg-white p-5 shadow-card">
        <div class="flex flex-wrap items-center gap-4">
            <figure data-photo-preview class="{{ $photoUrl ? 'flex' : 'hidden' }} h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-[#1769FF]">
                <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-full w-full object-cover">
            </figure>
            <div class="relative h-12 w-[140px] overflow-hidden">
                <input type="file" name="photo" id="doctor-photo" accept="image/*" class="absolute inset-0 cursor-pointer opacity-0">
                <label for="doctor-photo" class="flex h-full w-full cursor-pointer items-center justify-center rounded-xl bg-[#1769FF46] text-sm font-semibold text-[#0B1F3A]">
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
                <input
                    id="phone"
                    type="text"
                    name="phone"
                    value="{{ $phone }}"
                    placeholder="Enter 10-digit phone number"
                    inputmode="numeric"
                    pattern="[0-9]{10}"
                    maxlength="10"
                    minlength="10"
                    title="Please enter exactly 10 digits."
                    class="{{ $inputClass }}">

                @error('phone')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="gender" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Gender</label>
                <select id="gender" name="gender" class="{{ $inputClass }}">
                    <option value="">Select</option>
                    @foreach ($genders as $value => $label)
                    <option value="{{ $value }}" @selected($gender===$value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('gender')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="specialization" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Specialization</label>
                <select id="specialization" name="specialization" class="{{ $inputClass }}">
                    <option value="">Select</option>
                    @foreach ($specializations as $option)
                    <option value="{{ $option }}" @selected($specialization===$option)>{{ $option }}</option>
                    @endforeach
                </select>
                @error('specialization')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="ticketPrice" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Consultation Fee</label>

                <input
                    id="ticketPrice"
                    type="text"
                    inputmode="decimal"
                    name="ticketPrice"
                    value="{{ $ticketPrice }}"
                    placeholder="1500.00"
                    pattern="[0-9]+(\.[0-9]{1,2})?"
                    maxlength="10"
                    title="Enter a valid fee, e.g. 50.00 (up to 2 decimal places)."
                    class="{{ $inputClass }}">


                @error('ticketPrice')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="sm:col-span-2">
                <label for="bio" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Short bio</label>
                <textarea id="bio" name="bio" rows="3" placeholder="One line about your practice" class="{{ $inputClass }} h-auto min-h-24 resize-y py-3">{{ $bio }}</textarea>
                @error('bio')
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-5">
            <label for="about" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">About</label>
            <textarea id="about" name="about" rows="5" placeholder="Write about your care, procedures, and follow-up." class="w-full resize-y rounded-lg border border-[#E2E8F0] bg-white px-3 py-3 text-sm leading-6 text-[#181A1E] placeholder:text-[#94A3B8] focus:border-[#0067FF] focus:outline-none focus:ring-4 focus:ring-[#0067FF]/15">{{ $about }}</textarea>
            @error('about')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </section>

    <section class="rounded-xl border border-line bg-white p-5 shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-heading">Qualifications</h2>
                <p class="mt-1 text-sm leading-6 text-[#53647D]">Degrees and the colleges where you earned them.</p>
            </div>
            <button type="button" data-add="qualifications" class="inline-flex h-11 items-center rounded-xl bg-[#0B1F3A] px-4 text-sm font-semibold text-white hover:bg-[#2a2d33]">
                Add qualification
            </button>
        </div>
        <div data-list="qualifications" class="mt-5 space-y-4">
            @foreach ($qualifications as $index => $item)
            @include('dashboard.doctor.partials.qualification', ['index' => $index, 'item' => $item])
            @endforeach
        </div>
        <template id="qualifications-template">
            @include('dashboard.doctor.partials.qualification', ['index' => '__INDEX__', 'item' => $blankQualification])
        </template>
    </section>

    <section class="rounded-xl border border-line bg-white p-5 shadow-card">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-heading">Experience</h2>
                <p class="mt-1 text-sm leading-6 text-[#53647D]">Roles and hospitals patients should know about.</p>
            </div>
            <button type="button" data-add="experiences" class="inline-flex h-11 items-center rounded-xl bg-[#0B1F3A] px-4 text-sm font-semibold text-white hover:bg-[#2a2d33]">
                Add experience
            </button>
        </div>
        <div data-list="experiences" class="mt-5 space-y-4">
            @foreach ($experiences as $index => $item)
            @include('dashboard.doctor.partials.experience', ['index' => $index, 'item' => $item])
            @endforeach
        </div>
        <template id="experiences-template">
            @include('dashboard.doctor.partials.experience', ['index' => '__INDEX__', 'item' => $blankExperience])
        </template>
    </section>

    @include('dashboard.doctor.partials.weekly-schedule', [
    'timeSlots' => $timeSlots,
    'appointmentDuration' => $appointmentDuration,
    'appointmentBuffer' => $appointmentBuffer,
    ])

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
    const photoInput = document.getElementById('doctor-photo');
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

    document.querySelectorAll('[data-list]').forEach((list) => {
        const key = list.dataset.list;
        const addButton = document.querySelector(`[data-add="${key}"]`);
        const template = document.getElementById(`${key}-template`);

        const reindex = () => {
            list.querySelectorAll('[data-row]').forEach((row, index) => {
                row.querySelectorAll('[name]').forEach((field) => {
                    field.name = field.name.replace(/\[(?:\d+|__INDEX__)\]/, `[${index}]`);
                });
            });
        };

        addButton?.addEventListener('click', () => {
            if (!template) {
                return;
            }

            list.appendChild(template.content.cloneNode(true));
            reindex();
        });

        list.addEventListener('click', (event) => {
            const removeButton = event.target.closest('[data-remove]');

            if (!removeButton) {
                return;
            }

            removeButton.closest('[data-row]')?.remove();
            reindex();
        });
    });
</script>
@endpush