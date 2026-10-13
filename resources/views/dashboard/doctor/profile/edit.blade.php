@extends('layouts.dashboard')

@php
    $doctor = $doctor ?? auth()->user();
    $inputClass = 'h-12 w-full rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 text-base text-[#0B1F3A] placeholder:text-[#53647D] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20';

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
    $blankSlot = ['day' => '', 'startingTime' => '', 'endingTime' => '', 'isAvailable' => true];

    $qualifications = old('qualifications', data_get($doctor, 'qualifications', []));
    $experiences = old('experiences', data_get($doctor, 'experiences', []));
    $timeSlots = old('timeSlots', data_get($doctor, 'timeSlots', []));

    $qualifications = is_array($qualifications) && count($qualifications) ? array_values($qualifications) : [$blankQualification];
    $experiences = is_array($experiences) && count($experiences) ? array_values($experiences) : [$blankExperience];
    $timeSlots = is_array($timeSlots) && count($timeSlots) ? array_values($timeSlots) : [$blankSlot];

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
    <div class="dash-banner">
        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Doctor profile</p>
        <h1 class="mt-2 text-3xl font-extrabold leading-tight">Edit your details</h1>
        <p class="mt-3 max-w-2xl text-sm leading-6 text-white/80">
            Update the information patients see before they book: your specialty, fee, background, and open hours.
        </p>
        <div class="mt-6 flex flex-wrap gap-2">
            <span class="dash-chip">Profile settings</span>
            @if ($specialization !== '')
                <span class="dash-chip">{{ $specialization }}</span>
            @endif
            <span class="dash-chip">{{ $isPending ? 'Awaiting approval' : 'Visible after you save' }}</span>
        </div>
    </div>

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

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <h2 class="text-xl font-bold text-[#0B1F3A]">Personal information</h2>
            <p class="mt-1 text-sm leading-6 text-[#53647D]">Email stays as the login address and cannot be changed here.</p>

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <figure data-photo-preview class="{{ $photoUrl ? 'flex' : 'hidden' }} h-16 w-16 items-center justify-center overflow-hidden rounded-full border-2 border-[#1769FF]">
                    <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-full w-full object-cover">
                </figure>
                <div class="relative h-12 w-[140px]">
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
                    <input id="phone" type="number" name="phone" value="{{ $phone }}" placeholder="Phone number" class="{{ $inputClass }}">
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
                <div>
                    <label for="specialization" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Specialization</label>
                    <select id="specialization" name="specialization" class="{{ $inputClass }}">
                        <option value="">Select</option>
                        @foreach ($specializations as $option)
                            <option value="{{ $option }}" @selected($specialization === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                    @error('specialization')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="ticketPrice" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Ticket price</label>
                    <input id="ticketPrice" type="number" min="0" name="ticketPrice" value="{{ $ticketPrice }}" placeholder="1500" class="{{ $inputClass }}">
                    @error('ticketPrice')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="bio" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">Short bio</label>
                    <input id="bio" type="text" name="bio" value="{{ $bio }}" placeholder="One line about your practice" class="{{ $inputClass }}">
                    @error('bio')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-5">
                <label for="about" class="mb-2 block text-sm font-semibold text-[#0B1F3A]">About</label>
                <textarea id="about" name="about" rows="5" placeholder="Write about your care, procedures, and follow-up." class="w-full resize-y rounded-xl border border-[#E2EAF4] bg-[#F4F7FB] px-4 py-3 text-base leading-7 text-[#0B1F3A] placeholder:text-[#53647D] focus:border-[#1769FF] focus:outline-none focus:ring-2 focus:ring-[#1769FF]/20">{{ $about }}</textarea>
                @error('about')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </section>

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-[#0B1F3A]">Qualifications</h2>
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

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-[#0B1F3A]">Experience</h2>
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

        <section class="rounded-2xl border border-[#E2EAF4] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.08)] sm:p-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-[#0B1F3A]">Time slots</h2>
                    <p class="mt-1 text-sm leading-6 text-[#53647D]">Days and hours when patients can book you.</p>
                </div>
                <button type="button" data-add="timeSlots" class="inline-flex h-11 items-center rounded-xl bg-[#0B1F3A] px-4 text-sm font-semibold text-white hover:bg-[#2a2d33]">
                    Add time slot
                </button>
            </div>
            <div data-list="timeSlots" class="mt-5 space-y-4">
                @foreach ($timeSlots as $index => $item)
                    @include('dashboard.doctor.partials.time-slot', ['index' => $index, 'item' => $item])
                @endforeach
            </div>
            <template id="timeSlots-template">
                @include('dashboard.doctor.partials.time-slot', ['index' => '__INDEX__', 'item' => $blankSlot])
            </template>
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
