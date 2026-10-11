<section class="py-[35px] lg:py-[76px]">
    <div class="mx-auto w-full max-w-[1440px] px-5">
        <div class="mx-auto lg:w-[550px]">
            <!-- <h2 class="text-center text-[26px] font-extrabold leading-[36px] text-[#181A1E] md:text-[44px] md:leading-[54px]">
                Providing the best medical services
            </h2>
            <p class="mt-3 text-center text-base font-normal leading-7 text-[#4E545F] md:mt-[18px] md:text-[18px] md:leading-[30px]">
                Compassionate, expert care for every patient. Our network provides advanced treatments and personalized support to help you stay healthy.
            </p> -->
            <x-section-heading
                title="Providing the best medical services"
                subtitle=" Compassionate, expert care for every patient. Our network provides advanced treatments and personalized support to help you stay healthy." />
        </div>


        <div class="mt-[30px] grid grid-cols-1 gap-5 md:grid-cols-2 lg:mt-[55px] lg:grid-cols-3 lg:gap-[30px]">
            <div class="px-5 py-[30px]">
                <div class="flex items-center justify-center">
                    <img src="{{ asset('images/icon01.png') }}" alt="Find a Doctor">
                </div>
                <div class="mt-[30px]">
                    <h2 class="text-center text-[26px] font-bold leading-9 text-[#181A1E]">Find a Doctor</h2>
                    <p class="mt-4 text-center text-base font-normal leading-7 text-[#4E545F]">
                        Search from a wide network of trusted specialists and general physicians to find the right doctor for your health needs.
                    </p>
                    <a href="{{ route('doctors.index') }}" class="group mx-auto mt-[30px] flex h-[44px] w-[44px] items-center justify-center rounded-full border border-[#181A1E] hover:border-transparent hover:bg-[#0067FF]">
                        <svg class="h-5 w-6 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="px-5 py-[30px]">
                <div class="flex items-center justify-center">
                    <img src="{{ asset('images/availability.jpeg') }}" alt="Check Availability" class="h-[150px]">
                </div>
                <div class="mt-[30px]">
                    <h2 class="text-center text-[26px] font-bold leading-9 text-[#181A1E]">Check Availability</h2>
                    <p class="mt-4 text-center text-base font-normal leading-7 text-[#4E545F]">
                        View real-time schedules and availability so you can plan your appointments at the most convenient time for you.
                    </p>
                    <a href="{{ route('doctors.index') }}" class="group mx-auto mt-[30px] flex h-[44px] w-[44px] items-center justify-center rounded-full border border-[#181A1E] hover:border-transparent hover:bg-[#0067FF]">
                        <svg class="h-5 w-6 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                        </svg>
                    </a>
                </div>
            </div>

            <div class="px-5 py-[30px]">
                <div class="flex items-center justify-center">
                    <img src="{{ asset('images/icon03.png') }}" alt="Book an Appointment">
                </div>
                <div class="mt-[30px]">
                    <h2 class="text-center text-[26px] font-bold leading-9 text-[#181A1E]">Book an Appointment</h2>
                    <p class="mt-4 text-center text-base font-normal leading-7 text-[#4E545F]">
                        Secure your appointment instantly with just a few clicks and get timely reminders for a smooth healthcare experience.
                    </p>
                    <a href="{{ route('doctors.index') }}" class="group mx-auto mt-[30px] flex h-[44px] w-[44px] items-center justify-center rounded-full border border-[#181A1E] hover:border-transparent hover:bg-[#0067FF]">
                        <svg class="h-5 w-6 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>