@extends('layouts.public')

@section('content')

<main class="overflow-hidden bg-gradient-to-b from-[#e8f2ff] via-[#f7faff] to-white">
    <section class="relative py-14 lg:py-20">
        <div class="pointer-events-none absolute -left-24 top-10 h-64 w-64 rounded-full bg-[#0067FF]/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-16 top-32 h-72 w-72 rounded-full bg-[#01B5C5]/10 blur-3xl"></div>

        <div class="relative mx-auto w-full max-w-[1100px] px-5">
            <div class="mx-auto max-w-2xl text-center">
                <span class="inline-flex items-center rounded-full bg-white px-3.5 py-1 text-sm font-semibold text-[#0067FF] ring-1 ring-[#0067FF]/20">
                    We're here to help
                </span>
                <h1 class="mt-4 text-[32px] font-extrabold leading-tight tracking-tight text-[#181A1E] md:text-5xl">
                    Contact Us
                </h1>
                <p class="mt-3 text-base leading-7 text-[#4E545F] md:text-lg">
                    Got a technical issue? Want to send feedback about a beta feature? Tell us what you need and we'll get back to you.
                </p>
            </div>

            <div class="mt-12 grid items-start gap-6 lg:grid-cols-[340px_1fr] lg:gap-8">
                <aside class="space-y-4">
                    <div class="rounded-2xl bg-[#0067FF] p-6 text-white shadow-[0_12px_40px_rgba(0,103,255,0.22)]">
                        <p class="text-sm font-semibold uppercase tracking-wide text-white/80">Medicare support</p>
                        <h2 class="mt-2 text-2xl font-extrabold leading-snug">Talk to the team behind your care.</h2>
                        <p class="mt-3 text-sm leading-6 text-white/85">
                            Questions about booking, referrals, or something that isn't working yet — send a note and a person will read it.
                        </p>
                    </div>

                    <div class="space-y-3 rounded-2xl border border-[#0066ff1f] bg-white p-4 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
                        <div class="flex gap-3 rounded-xl bg-[#f7faff] p-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-[#0067FF] ring-1 ring-[#0067FF]/15">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M4 6h16v12H4z" stroke-linejoin="round" />
                                    <path d="m4 7 8 6 8-6" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-[#181A1E]">Reply by email</p>
                                <p class="mt-0.5 text-sm leading-6 text-[#4E545F]">We answer at the address you enter in the form</p>
                            </div>
                        </div>

                        <div class="flex gap-3 rounded-xl bg-[#f7faff] p-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-[#0067FF] ring-1 ring-[#0067FF]/15">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <circle cx="12" cy="12" r="9" />
                                    <path d="M12 7v6l4 2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-[#181A1E]">Response time</p>
                                <p class="mt-0.5 text-sm leading-6 text-[#4E545F]">Usually within one business day</p>
                            </div>
                        </div>

                        <div class="flex gap-3 rounded-xl bg-[#f7faff] p-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-white text-[#0067FF] ring-1 ring-[#0067FF]/15">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path d="M12 21s7-5.4 7-11a7 7 0 1 0-14 0c0 5.6 7 11 7 11z" stroke-linejoin="round" />
                                    <circle cx="12" cy="10" r="2.5" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-semibold text-[#181A1E]">What we can help with</p>
                                <p class="mt-0.5 text-sm leading-6 text-[#4E545F]">Technical issues, beta feedback, and appointment questions</p>
                            </div>
                        </div>
                    </div>
                </aside>

                <form action="#" method="POST" class="rounded-2xl border border-[#0066ff1f] bg-white p-6 shadow-[0_12px_40px_rgba(0,103,255,0.10)] sm:p-8">
                    @csrf
                    <h2 class="text-xl font-bold text-[#181A1E]">Send a message</h2>
                    <p class="mt-1 text-sm leading-6 text-[#4E545F]">We read every note. Include as much detail as you can.</p>

                    <div class="mt-6 space-y-5">
                        <div>
                            <label for="email" class="mb-2 block text-sm font-semibold text-[#181A1E]">Your email</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="example@gmail.com"
                                class="h-12 w-full rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20">
                        </div>

                        <div>
                            <label for="subject" class="mb-2 block text-sm font-semibold text-[#181A1E]">Subject</label>
                            <input
                                type="text"
                                id="subject"
                                name="subject"
                                placeholder="Let us know how we can help you."
                                class="h-12 w-full rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20">
                        </div>

                        <div>
                            <label for="message" class="mb-2 block text-sm font-semibold text-[#181A1E]">Your message</label>
                            <textarea
                                id="message"
                                name="message"
                                rows="6"
                                placeholder="Leave a comment..."
                                class="w-full resize-y rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 py-3 text-base leading-7 text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20"></textarea>
                        </div>
                    </div>

                    <button type="submit" class="mt-6 inline-flex h-12 w-full items-center justify-center rounded-xl bg-[#0067FF] px-8 text-base font-semibold text-white transition hover:bg-[#0056d6] sm:w-auto">
                        Submit
                    </button>
                </form>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-[#0066ff1f] bg-white px-5 py-4">
                    <p class="text-sm font-semibold text-[#0067FF]">01</p>
                    <p class="mt-1 font-semibold text-[#181A1E]">Send your note</p>
                    <p class="mt-1 text-sm leading-6 text-[#4E545F]">Share the issue, the page, and what you expected to happen.</p>
                </div>
                <div class="rounded-2xl border border-[#0066ff1f] bg-white px-5 py-4">
                    <p class="text-sm font-semibold text-[#0067FF]">02</p>
                    <p class="mt-1 font-semibold text-[#181A1E]">We look into it</p>
                    <p class="mt-1 text-sm leading-6 text-[#4E545F]">The right person reviews bookings, bugs, and product feedback.</p>
                </div>
                <div class="rounded-2xl border border-[#0066ff1f] bg-white px-5 py-4">
                    <p class="text-sm font-semibold text-[#0067FF]">03</p>
                    <p class="mt-1 font-semibold text-[#181A1E]">You hear back</p>
                    <p class="mt-1 text-sm leading-6 text-[#4E545F]">A reply lands in your inbox, usually within one business day.</p>
                </div>
            </div>
        </div>
    </section>
</main>

@endsection