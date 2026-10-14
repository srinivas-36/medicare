@extends('layouts.dashboard')

@section('title', 'Services')

@section('content')
    <x-ui.page-header title="Services" description="What patients see when they book a visit with you.">
        <a href="{{ route('doctor.profile.edit') }}" class="btn-primary">Edit profile</a>
    </x-ui.page-header>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['Specialization', $specialization],
            ['Consultation fee', $fee],
            ['Visit length', $duration],
            ['Buffer', $buffer],
        ] as [$label, $value])
            <article class="rounded-2xl border border-[#E7EEF6] bg-white px-4 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <p class="text-[13px] font-medium text-[#64748B]">{{ $label }}</p>
                <p class="mt-2 text-xl font-bold text-[#0F172A]">{{ $value }}</p>
            </article>
        @endforeach
    </div>

    <section class="mt-4 rounded-2xl border border-[#E7EEF6] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        <h2 class="text-[15px] font-semibold text-[#0F172A]">Visit types</h2>
        <ul class="mt-3 flex flex-wrap gap-2">
            @foreach ($visits as $visit)
                <li class="rounded-full bg-[#EAF2FF] px-3 py-1 text-sm font-semibold text-[#0067FF]">{{ $visit }}</li>
            @endforeach
        </ul>
        <a href="{{ route('doctor.availability') }}" class="mt-4 inline-flex text-sm font-semibold text-[#0067FF] no-underline">Manage availability</a>
    </section>
@endsection
