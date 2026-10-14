{{--
    Two-step booking progress. Step 1 can be revisited once a doctor is chosen.
--}}
@props(['step' => 1])

<nav aria-label="Booking progress" class="mb-6">
    <ol class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <li class="flex items-center gap-2.5">
            @if ((int) $step > 1)
                <a href="{{ route('patient.book') }}" class="group flex items-center gap-2.5 no-underline">
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#0067FF] text-sm font-semibold text-white">1</span>
                    <span class="text-sm font-semibold text-heading group-hover:text-[#0067FF]">Select a doctor</span>
                </a>
            @else
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#0067FF] text-sm font-semibold text-white">1</span>
                <span>
                    <span class="block text-sm font-semibold text-heading">Select a doctor</span>
                    <span class="block text-xs text-muted">Choose who you want to see</span>
                </span>
            @endif
        </li>

        <li class="hidden h-px w-12 bg-[#E2E8F0] sm:block" aria-hidden="true"></li>

        <li class="flex items-center gap-2.5">
            <span @class([
                'inline-flex h-8 w-8 items-center justify-center rounded-full text-sm font-semibold',
                'bg-[#0067FF] text-white' => (int) $step === 2,
                'border border-[#E2E8F0] bg-white text-muted' => (int) $step !== 2,
            ])>2</span>
            <span>
                <span @class([
                    'block text-sm font-semibold',
                    'text-heading' => (int) $step === 2,
                    'text-muted' => (int) $step !== 2,
                ])>Confirm booking</span>
                @if ((int) $step === 2)
                    <span class="block text-xs text-muted">Time, notes, and reports</span>
                @endif
            </span>
        </li>
    </ol>
</nav>
