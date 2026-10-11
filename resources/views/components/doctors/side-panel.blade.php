@props(['doctor'])

@php
$role = auth()->check() ? data_get(auth()->user(), 'role') : null;
$doctorId = data_get($doctor, 'id', data_get($doctor, '_id'));
$bookUrl = route('login');

if ($role === 'patient' && $doctorId) {
$bookUrl = \Illuminate\Support\Facades\Route::has('doctors.book')
? route('doctors.book', $doctorId)
: url('/doctors/'.$doctorId.'/book');
}
@endphp

<div class="rounded-xl bg-white p-6 shadow-[rgba(17,12,46,0.15)_0px_48px_100px_0px]">
    <div class="mb-6 flex items-center justify-between border-b pb-4">
        <p class="text-lg font-semibold text-gray-700">Consultation Fee</p>
        <span class="text-xl font-bold text-[#0067FF]">{{ data_get($doctor, 'ticketPrice') }} USD</span>
    </div>

    @if ($role !== 'doctor')
    <a href="{{ $bookUrl }}" class="block w-full rounded-lg bg-[#0067FF] py-3 text-center text-lg font-semibold text-white shadow-md transition-all duration-200 hover:bg-[#0067FF]/90">
        Book Appointment
    </a>
    @endif
</div>