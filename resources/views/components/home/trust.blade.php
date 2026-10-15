{{-- Four benefits. These are product facts, not patient or rating counts. --}}
<section class="page relative z-10 -mt-2 pb-10 lg:pb-14">
    <div class="grid gap-4 rounded-[20px] border border-line bg-white p-5 shadow-card sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['icon' => 'user', 'title' => 'Qualified professionals', 'text' => 'Public profiles are limited to doctors the clinic has approved.'],
            ['icon' => 'card', 'title' => 'Transparent fees', 'text' => 'Each profile shows the consultation fee before you request a visit.'],
            ['icon' => 'calendar', 'title' => 'Simple booking', 'text' => 'Pick an open time. The request stays pending until the clinic updates it.'],
            ['icon' => 'file', 'title' => 'Care in one place', 'text' => 'Patients, doctors, and reception each have their own dashboard.'],
        ] as $item)
            <div class="flex gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-mint text-primary">
                    <x-ui.icon :name="$item['icon']" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-navy">{{ $item['title'] }}</p>
                    <p class="mt-1 text-sm leading-6 text-text">{{ $item['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
