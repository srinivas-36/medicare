{{--
    FAQ answers follow the booking code:
    fees are displayed, patients can cancel, there is no reschedule action,
    and visit types are not online versus in person.
--}}
<section class="py-16 lg:py-20">
    <div class="page">
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <x-section-heading
                align="left"
                :level="request()->routeIs('faq') ? 'h1' : 'h2'"
                title="Frequently Asked Questions"
                subtitle="Short answers based on how booking works today."
            />
            @unless (request()->routeIs('faq'))
                <a href="{{ route('faq') }}" class="text-sm font-semibold text-primary no-underline hover:text-primary-hover">View all FAQs</a>
            @endunless
        </div>

        <div class="mt-10 grid grid-cols-1 gap-4 lg:grid-cols-2">
            @foreach ([
                ['q' => 'How do consultation fees work?', 'a' => 'Each doctor sets a consultation fee on their profile. You see that amount before you request an appointment. Medicare does not collect the payment on this website.'],
                ['q' => 'How do I book an appointment?', 'a' => 'Create a patient account, open a doctor, choose a date and an open time, and send the request. It stays pending until the clinic updates the status.'],
                ['q' => 'Can I reschedule or cancel an appointment?', 'a' => 'You can cancel from My Bookings while the visit is still open to cancel. To pick a different time, cancel that request and book another open slot. There is no separate reschedule button.'],
                ['q' => 'Is my medical information private?', 'a' => 'Booking requires an account. Patients see their own appointments. Doctors and reception see the appointments their role is allowed to manage. Passwords are stored hashed, and forms use CSRF protection.'],
                ['q' => 'Can I choose an online or in-person consultation?', 'a' => 'The booking form asks for a visit type: consultation, check-up, follow-up, or emergency. It does not offer a separate online or in-person choice.'],
                ['q' => 'Who can see a doctor in the directory?', 'a' => 'The public list includes doctors whose approval status is approved and whose account is active. Other accounts stay off that list.'],
            ] as $item)
                <details class="card group p-5">
                    <summary class="flex cursor-pointer items-center justify-between gap-4 text-left text-[15px] font-semibold text-navy">
                        {{ $item['q'] }}
                        <span class="text-xl leading-none text-primary transition group-open:rotate-45" aria-hidden="true">+</span>
                    </summary>
                    <p class="mt-3 text-sm leading-6 text-text">{{ $item['a'] }}</p>
                </details>
            @endforeach
        </div>
    </div>
</section>
