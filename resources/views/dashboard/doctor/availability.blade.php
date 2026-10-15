@extends('layouts.dashboard')

@section('title', 'Availability')

@section('content')
@if (session('status'))
<p class="mb-4 rounded-lg border border-[#BBF7D0] bg-[#F0FDF4] px-4 py-3 text-sm text-success">{{ session('status') }}</p>
@endif

@if ($errors->any())
<p class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-danger">{{ $errors->first() }}</p>
@endif

<form method="POST" action="{{ route('doctor.availability.update') }}" class="space-y-4">
    @csrf
    @method('PUT')
    @include('dashboard.doctor.partials.weekly-schedule', [
    'timeSlots' => $timeSlots,
    'appointmentDuration' => $appointmentDuration,
    'appointmentBuffer' => $appointmentBuffer,
    'asPage' => true,
    ])
    <div class="flex justify-end">
        <button type="submit" class="btn-primary">Save schedule</button>
    </div>
</form>
<!-- 
    <section class="panel mt-4 p-4 sm:p-5">
        <h2 class="text-sm font-semibold text-heading">Date exceptions</h2>
        <p class="mt-1 max-w-2xl text-sm text-body">Leave blocks new bookings. Extra hours add a one-off clinic. Override replaces that day’s weekly hours.</p>
        <form method="POST" action="{{ route('doctor.availability.exceptions.store') }}" class="mt-5 grid gap-4 sm:grid-cols-2">
            @csrf
            <label class="text-sm font-semibold text-navy">Title
                <input name="title" value="{{ old('title') }}" required class="field mt-2">
            </label>
            <label class="text-sm font-semibold text-navy">Type
                <select name="kind" class="field mt-2">
                    <option value="unavailable">Leave or closure</option>
                    <option value="extra">Extra hours</option>
                    <option value="override">Replace that day</option>
                </select>
            </label>
            <label class="text-sm font-semibold text-navy">Starts
                <input type="date" name="starts_on" value="{{ old('starts_on') }}" required class="field mt-2">
            </label>
            <label class="text-sm font-semibold text-navy">Ends
                <input type="date" name="ends_on" value="{{ old('ends_on') }}" required class="field mt-2">
            </label>
            <label class="text-sm font-semibold text-navy">From
                <input type="time" name="starts_at" value="{{ old('starts_at') }}" class="field mt-2">
            </label>
            <label class="text-sm font-semibold text-navy">To
                <input type="time" name="ends_at" value="{{ old('ends_at') }}" class="field mt-2">
            </label>
            <label class="sm:col-span-2 text-sm font-semibold text-navy">Reason
                <input name="reason" value="{{ old('reason') }}" class="field mt-2">
            </label>
            <div>
                <button type="submit" class="btn-secondary">Add exception</button>
            </div>
        </form>

        <ul class="mt-6 space-y-3">
            @forelse ($exceptions as $exception)
                <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line px-4 py-3">
                    <div>
                        <p class="font-semibold text-navy">{{ $exception->title }}</p>
                        <p class="text-sm text-text">{{ ucfirst($exception->kind) }} · {{ $exception->starts_on->format('M j, Y') }} – {{ $exception->ends_on->format('M j, Y') }}</p>
                    </div>
                    <form method="POST" action="{{ route('doctor.availability.exceptions.destroy', $exception) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-semibold text-danger">Remove</button>
                    </form>
                </li>
            @empty
                <li class="text-sm text-text">No date exceptions yet.</li>
            @endforelse
        </ul>
    </section> -->
@endsection