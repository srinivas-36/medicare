{{--
    Booking steps that match the current flow:
    search, open times, request, then the clinic updates the status.
    Payment and online video visits are not part of this flow.
--}}
<section class="py-16 lg:py-20">
    <div class="page">
        <x-section-heading
            title="How It Works"
            subtitle="Book your appointment in a few steps. The clinic confirms the request after you send it."
        />

        <ol class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['icon' => 'search', 'title' => 'Search & Choose', 'text' => 'Look up a doctor by name or specialty and open the profile.'],
                ['icon' => 'clock', 'title' => 'Check Availability', 'text' => 'Choose a date. Open times come from that doctor’s schedule.'],
                ['icon' => 'calendar', 'title' => 'Book & Confirm', 'text' => 'Sign in as a patient, add a reason, and send the request. It starts as pending.'],
                ['icon' => 'user', 'title' => 'Visit or Consult', 'text' => 'Follow the status on your dashboard. You can cancel while the visit is still open.'],
            ] as $index => $step)
                <li class="card relative text-center">
                    <span class="absolute left-1/2 top-0 flex h-8 w-8 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-primary text-sm font-bold text-white">{{ $index + 1 }}</span>
                    <span class="mx-auto mt-2 flex h-12 w-12 items-center justify-center rounded-2xl bg-mint text-primary">
                        <x-ui.icon :name="$step['icon']" />
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-navy">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-text">{{ $step['text'] }}</p>
                    @if ($index < 3)
                        <span class="absolute -right-3 top-1/2 hidden -translate-y-1/2 text-primary xl:block" aria-hidden="true">
                            <x-ui.icon name="arrow" />
                        </span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>
</section>
