@extends('layouts.dashboard')

@section('title', 'Reviews')

@section('content')
    <x-ui.page-header
        title="Reviews"
        description="Reviews patients leave on your public profile. New reviews show up here as they are submitted."
    />

    <div x-data="doctorReviews(@js($reviewState))" class="space-y-4">
        <section class="rounded-2xl border border-[#E7EEF6] bg-white p-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
            <p @if ($reviewState['total'] > 0) x-cloak @endif x-show="total < 1" class="text-sm text-[#64748B]">No ratings yet.</p>

            <div @if ($reviewState['total'] < 1) x-cloak @endif x-show="total > 0" class="flex items-end gap-4">
                <p class="text-5xl font-bold tracking-tight text-[#0F172A]" x-text="average">{{ $reviewState['average'] }}</p>
                <div class="pb-1">
                    <p class="flex items-center gap-1 text-[#F59E0B]">
                        <template x-for="star in starScale" :key="'summary-' + star">
                            <span :class="star <= Math.round(Number(average)) ? '' : 'opacity-30'">
                                <x-ui.icon name="star" class="h-4 w-4" />
                            </span>
                        </template>
                    </p>
                    <p class="mt-1 text-sm text-[#64748B]" x-text="total + (total === 1 ? ' rating' : ' ratings')">
                        {{ $reviewState['total'] }} {{ $reviewState['total'] === 1 ? 'rating' : 'ratings' }}
                    </p>
                </div>
            </div>
        </section>

        <section
            @if ($reviewState['total'] < 1) x-cloak @endif
            x-show="total > 0"
            class="rounded-2xl border border-[#E7EEF6] bg-white px-6 shadow-[0_1px_2px_rgba(15,23,42,0.04)]"
        >
            <x-doctors.review-feed :state="$reviewState" />
        </section>
    </div>
@endsection
