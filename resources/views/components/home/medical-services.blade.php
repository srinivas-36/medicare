<section class="py-[35px] lg:py-[76px]">
    <div class="mx-auto w-full max-w-[1440px] px-5">
        <div class="mx-auto xl:w-[470px]">
            <!-- <h2 class="text-center text-[26px] font-extrabold leading-[36px] text-[#181A1E] md:text-[44px] md:leading-[54px]">
                Our Medical Services
            </h2>
            <p class="mt-3 text-center text-base font-normal leading-7 text-[#4E545F] md:mt-[18px] md:text-[18px] md:leading-[30px]">
                World-class care for everyone . Our health System offers unmatched, expert health care.
            </p> -->
            <x-section-heading
                title="Our Medical Services"
                subtitle="World-class care for everyone . Our health System offers unmatched, expert health care." />
        </div>


        <div class="mt-[30px] grid grid-cols-1 gap-5 md:grid-cols-2 lg:mt-[55px] lg:grid-cols-3 lg:gap-[30px]">
            <article class="overflow-hidden rounded-xl bg-white shadow transition hover:shadow-lg">
                <img src="{{ asset('images/cancer-care.jpg') }}" alt="Cancer Care" class="h-72 w-full object-cover">
                <div class="p-5">
                    <h2 class="text-lg font-bold text-gray-900">Cancer Care</h2>
                    <p class="mt-2 text-sm text-gray-600">World-class cancer care with compassion. From early detection to advanced treatment, we’re with you every step of the journey.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('doctors.index') }}" class="group flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 hover:border-transparent hover:bg-teal-600">
                            <svg class="h-5 w-5 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                            </svg>
                        </a>
                        <span class="flex h-10 w-10 items-center justify-center rounded bg-[#FEB60D]/20 text-sm font-semibold text-[#FEB60D]">1</span>
                    </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl bg-white shadow transition hover:shadow-lg">
                <img src="{{ asset('images/labor-and-delivery.jpeg') }}" alt="Labor & Delivery" class="h-72 w-full object-cover">
                <div class="p-5">
                    <h2 class="text-lg font-bold text-gray-900">Labor & Delivery</h2>
                    <p class="mt-2 text-sm text-gray-600">Personalized labor and delivery care, bringing new life into the world with confidence.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('doctors.index') }}" class="group flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 hover:border-transparent hover:bg-teal-600">
                            <svg class="h-5 w-5 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                            </svg>
                        </a>
                        <span class="flex h-10 w-10 items-center justify-center rounded bg-[#9771FF]/20 text-sm font-semibold text-[#9771FF]">2</span>
                    </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl bg-white shadow transition hover:shadow-lg">
                <img src="{{ asset('images/Heart-andVascular.jpg') }}" alt="Heart & Vascular" class="h-72 w-full object-cover">
                <div class="p-5">
                    <h2 class="text-lg font-bold text-gray-900">Heart & Vascular</h2>
                    <p class="mt-2 text-sm text-gray-600">Expert heart and vascular care for every patient. From diagnosis to advanced treatment, we’re here for your heart health.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('doctors.index') }}" class="group flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 hover:border-transparent hover:bg-teal-600">
                            <svg class="h-5 w-5 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                            </svg>
                        </a>
                        <span class="flex h-10 w-10 items-center justify-center rounded bg-[#01B5C5]/20 text-sm font-semibold text-[#01B5C5]">3</span>
                    </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl bg-white shadow transition hover:shadow-lg">
                <img src="{{ asset('images/mental-health.jpg') }}" alt="Mental Health" class="h-72 w-full object-cover">
                <div class="p-5">
                    <h2 class="text-lg font-bold text-gray-900">Mental Health</h2>
                    <p class="mt-2 text-sm text-gray-600">Compassionate care for mental wellness. Our health system offers expert support and holistic treatment.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('doctors.index') }}" class="group flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 hover:border-transparent hover:bg-teal-600">
                            <svg class="h-5 w-5 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                            </svg>
                        </a>
                        <span class="flex h-10 w-10 items-center justify-center rounded bg-[#01B5C5]/20 text-sm font-semibold text-[#01B5C5]">4</span>
                    </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl bg-white shadow transition hover:shadow-lg">
                <img src="{{ asset('images/Neurology.jpg') }}" alt="Neurology" class="h-72 w-full object-cover">
                <div class="p-5">
                    <h2 class="text-lg font-bold text-gray-900">Neurology</h2>
                    <p class="mt-2 text-sm text-gray-600">Comprehensive neurology services for every stage of life, providing expert care to support your nervous system and overall well-being.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('doctors.index') }}" class="group flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 hover:border-transparent hover:bg-teal-600">
                            <svg class="h-5 w-5 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                            </svg>
                        </a>
                        <span class="flex h-10 w-10 items-center justify-center rounded bg-[#FEB60D]/20 text-sm font-semibold text-[#FEB60D]">5</span>
                    </div>
                </div>
            </article>

            <article class="overflow-hidden rounded-xl bg-white shadow transition hover:shadow-lg">
                <img src="{{ asset('images/burn.webp') }}" alt="Burn Treatment" class="h-72 w-full object-cover">
                <div class="p-5">
                    <h2 class="text-lg font-bold text-gray-900">Burn Treatment</h2>
                    <p class="mt-2 text-sm text-gray-600">Advanced burn care for every patient, providing expert treatment and compassionate support from emergency care to full recovery.</p>
                    <div class="mt-4 flex items-center justify-between">
                        <a href="{{ route('doctors.index') }}" class="group flex h-10 w-10 items-center justify-center rounded-full border border-gray-300 hover:border-transparent hover:bg-teal-600">
                            <svg class="h-5 w-5 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                            </svg>
                        </a>
                        <span class="flex h-10 w-10 items-center justify-center rounded bg-[#9771FF]/20 text-sm font-semibold text-[#9771FF]">6</span>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>