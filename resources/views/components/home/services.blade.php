{{-- Why Medicare. Copy stays inside what the app actually does. --}}
<section class="bg-white py-16 lg:py-20">
    <div class="page">
        <x-section-heading
            title="Why Choose Medicare"
            subtitle="A straightforward way to find a doctor and keep the appointment on your account."
        />

        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['icon' => 'stethoscope', 'title' => 'Qualified professionals', 'text' => 'Doctor accounts are approved before they appear in the public directory.'],
                ['icon' => 'card', 'title' => 'Transparent pricing', 'text' => 'The consultation fee is on the profile. This site does not take payment.'],
                ['icon' => 'calendar', 'title' => 'Easy booking', 'text' => 'Patients request a date and an open time. Reception and the doctor can update the status.'],
                ['icon' => 'shield', 'title' => 'Privacy and security', 'text' => 'You sign in with a password. Each role only opens the screens that role is allowed to use.'],
            ] as $item)
                <article class="card">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mint text-primary">
                        <x-ui.icon :name="$item['icon']" />
                    </span>
                    <h3 class="mt-4 text-lg font-semibold text-navy">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm leading-6 text-text">{{ $item['text'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>
