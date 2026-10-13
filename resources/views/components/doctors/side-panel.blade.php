@props(['doctor'])

@php
    $role = auth()->check() ? data_get(auth()->user(), 'role') : null;
    $doctorId = data_get($doctor, 'id', data_get($doctor, '_id'));
    $bookUrl = route('login');

    if ($doctorId && \Illuminate\Support\Facades\Route::has('doctors.book')) {
        $bookUrl = route('doctors.book', $doctorId);
    } elseif ($role === 'patient' && $doctorId) {
        $bookUrl = url('/doctors/'.$doctorId.'/book');
    }
@endphp

<div class="surface sticky top-28 p-6">
    <p class="text-sm font-semibold uppercase tracking-[0.14em] text-text">Consultation fee</p>
    <p class="mt-2 text-3xl font-extrabold tracking-tight text-primary">{{ data_get($doctor, 'ticketPrice') }} USD</p>
    <p class="mt-2 text-sm leading-6 text-text">Shown before you request the visit. The clinic confirms the appointment from your dashboard.</p>

    @if ($role !== 'doctor')
        <a href="{{ $bookUrl }}" class="btn-primary mt-6 w-full">
            Book appointment
        </a>
    @endif

    <ul class="mt-6 space-y-3 border-t border-[#E2EAF4] pt-6 text-sm text-text">
        <li class="flex gap-2"><span class="text-primary">•</span> Choose a date and an open time</li>
        <li class="flex gap-2"><span class="text-primary">•</span> Add the reason for your visit</li>
        <li class="flex gap-2"><span class="text-primary">•</span> Track the status after you submit</li>
    </ul>
</div>
