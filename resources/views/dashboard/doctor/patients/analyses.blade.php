@extends('layouts.dashboard')

@php
    $name = $patient->name ?? 'Patient';
@endphp

@section('title', 'AI Analysis')

@section('content')
    <x-ui.page-header :title="$name" description="Every AI analysis saved on this chart.">
        <a href="{{ route('doctor.patients.show', $patient) }}" class="btn-secondary">Back to chart</a>
        @if ($startUrl)
            <a href="{{ $startUrl }}" class="btn-primary">
                <x-ui.icon name="spark" class="h-4 w-4" />
                New analysis
            </a>
        @endif
    </x-ui.page-header>

    <section class="dash-panel">
        @if ($analyses->isEmpty())
            <div class="px-5 py-10 text-center">
                <p class="text-sm font-semibold text-[#181A1E]">No AI analysis yet</p>
                <p class="mt-1 text-sm text-[#64748B]">Analyses run on this patient’s visits will be listed here.</p>
            </div>
        @else
            <ul class="divide-y divide-[#F1F5F9]">
                @foreach ($analyses as $analysis)
                    @php
                        $visit = $analysis->appointment;
                        $summary = filled($analysis->clinician_summary)
                            ? $analysis->clinician_summary
                            : $analysis->original_summary;
                        $when = $analysis->completed_at ?? $analysis->created_at;
                    @endphp
                    <li>
                        <a href="{{ route('doctor.appointments.analysis', [$analysis->appointment_id, $analysis]) }}" class="flex items-start justify-between gap-4 px-5 py-4 no-underline hover:bg-[#F8FAFC]">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-[#181A1E]">
                                    {{ $visit ? \App\Support\AppointmentPresenter::date($visit->appointment_date) : 'Visit' }}
                                    @if ($visit && filled($visit->reason))
                                        <span class="font-medium text-[#4E545F]">· {{ \Illuminate\Support\Str::of($visit->reason)->before("\n") }}</span>
                                    @endif
                                </span>
                                <span class="mt-1 block text-xs text-[#64748B]">{{ $when?->timezone(config('app.timezone'))->format('j M Y, g:i A') }}</span>
                                @if (filled($summary))
                                    <span class="mt-2 block line-clamp-2 text-sm text-[#4E545F]">{{ $summary }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 rounded-full bg-[#E8F2FF] px-2.5 py-1 text-xs font-semibold text-[#1D4ED8]">{{ $analysis->statusLabel() }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
