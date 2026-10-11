@php
$testimonials = [
[
'name' => 'Muhibur Rahman',
'rating' => 5,
'message' => 'I have taken medical services from them. They treat so well and provide the best medical care.',
],
[
'name' => 'Sophia Patel',
'rating' => 5,
'message' => 'Booking appointments was so easy. The doctors are very professional and caring.',
],
[
'name' => 'John Smith',
'rating' => 4,
'message' => 'Excellent platform! I could quickly find a specialist and get treated without delays.',
],
[
'name' => 'Aarav Mehta',
'rating' => 5,
'message' => 'Very convenient and reliable service. I received timely reminders and support throughout.',
],
];
@endphp

<section class="py-[35px] lg:py-[76px]">
    <div class="mx-auto w-full max-w-[1440px] px-5">
        <div class="mx-auto xl:w-[500px]">
            <!-- <h2 class="text-center text-[26px] font-extrabold leading-[36px] text-[#181A1E] md:text-[44px] md:leading-[54px]">
                What our patients Say
            </h2>
            <p class="mt-3 text-center text-base font-normal leading-7 text-[#4E545F] md:mt-[18px] md:text-[18px] md:leading-[30px]">
                World-class care for everyone . Our health System offers unmatched, expert health care.
            </p> -->

            <x-section-heading
                title="What our patients Say"
                subtitle=" World-class care for everyone . Our health System offers unmatched, expert health care.
            " />
        </div>

        <!-- <div class="mt-[30px] grid grid-cols-1 gap-5 md:grid-cols-2 lg:mt-[55px] lg:grid-cols-3 lg:gap-[30px]">
            <article class="rounded-[13px] px-5 py-[30px] shadow-md">
                <div class="flex items-center gap-[13px]">
                    <img src="{{ asset('images/patient-avatar.png') }}" alt="Muhibur Rahman" class="h-16 w-16 rounded-full object-cover">
                    <div>
                        <h4 class="text-[18px] font-semibold leading-[30px] text-[#181A1E]">Muhibur Rahman</h4>
                        <div class="flex items-center gap-[2px] text-[#FEB60D]">
                            @for ($star = 0; $star < 5; $star++)
                                <svg class="h-5 w-[18px]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.36 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.03a1 1 0 0 0-1.18 0l-2.8 2.03c-.79.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29z" />
                                </svg>
                                @endfor
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-base font-normal leading-7 text-[#4E545F]">"I have taken medical services from them. They treat so well and provide the best medical care."</p>
            </article>

            <article class="rounded-[13px] px-5 py-[30px] shadow-md">
                <div class="flex items-center gap-[13px]">
                    <img src="{{ asset('images/patient-avatar.png') }}" alt="Sophia Patel" class="h-16 w-16 rounded-full object-cover">
                    <div>
                        <h4 class="text-[18px] font-semibold leading-[30px] text-[#181A1E]">Sophia Patel</h4>
                        <div class="flex items-center gap-[2px] text-[#FEB60D]">
                            @for ($star = 0; $star < 5; $star++)
                                <svg class="h-5 w-[18px]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.36 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.03a1 1 0 0 0-1.18 0l-2.8 2.03c-.79.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29z" />
                                </svg>
                                @endfor
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-base font-normal leading-7 text-[#4E545F]">"Booking appointments was so easy. The doctors are very professional and caring."</p>
            </article>

            <article class="rounded-[13px] px-5 py-[30px] shadow-md">
                <div class="flex items-center gap-[13px]">
                    <img src="{{ asset('images/patient-avatar.png') }}" alt="John Smith" class="h-16 w-16 rounded-full object-cover">
                    <div>
                        <h4 class="text-[18px] font-semibold leading-[30px] text-[#181A1E]">John Smith</h4>
                        <div class="flex items-center gap-[2px] text-[#FEB60D]">
                            @for ($star = 0; $star < 4; $star++)
                                <svg class="h-5 w-[18px]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.36 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.03a1 1 0 0 0-1.18 0l-2.8 2.03c-.79.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29z" />
                                </svg>
                                @endfor
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-base font-normal leading-7 text-[#4E545F]">"Excellent platform! I could quickly find a specialist and get treated without delays."</p>
            </article>

            <article class="rounded-[13px] px-5 py-[30px] shadow-md">
                <div class="flex items-center gap-[13px]">
                    <img src="{{ asset('images/patient-avatar.png') }}" alt="Aarav Mehta" class="h-16 w-16 rounded-full object-cover">
                    <div>
                        <h4 class="text-[18px] font-semibold leading-[30px] text-[#181A1E]">Aarav Mehta</h4>
                        <div class="flex items-center gap-[2px] text-[#FEB60D]">
                            @for ($star = 0; $star < 5; $star++)
                                <svg class="h-5 w-[18px]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.36 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.03a1 1 0 0 0-1.18 0l-2.8 2.03c-.79.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29z" />
                                </svg>
                                @endfor
                        </div>
                    </div>
                </div>
                <p class="mt-4 text-base font-normal leading-7 text-[#4E545F]">"Very convenient and reliable service. I received timely reminders and support throughout."</p>
            </article>
        </div> -->

        <div class="relative overflow-hidden mt-[55px]">

            <div class="testimonial-slider flex gap-8">

                @foreach($testimonials as $testimonial)
                <x-testimonial-card
                    :name="$testimonial['name']"
                    :rating="$testimonial['rating']"
                    :message="$testimonial['message']"
                    image="images/patient-avatar.png" />
                @endforeach

                @foreach($testimonials as $testimonial)
                <x-testimonial-card
                    :name="$testimonial['name']"
                    :rating="$testimonial['rating']"
                    :message="$testimonial['message']"
                    image="images/patient-avatar.png" />
                @endforeach

            </div>

        </div>
    </div>
</section>