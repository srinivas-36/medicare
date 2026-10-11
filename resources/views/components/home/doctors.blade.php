<section class="py-[35px] lg:py-[76px]">
    <div class="mx-auto w-full max-w-[1440px] px-5">
        <!-- <div class="mx-auto xl:w-[550px]">
            <h2 class="text-center text-[26px] font-extrabold leading-[36px] text-[#181A1E] md:text-[44px] md:leading-[54px]">
                Meet Our Top Specialists
            </h2>
            <p class="mt-3 text-center text-base font-normal leading-7 text-[#4E545F] md:mt-[18px] md:text-[18px] md:leading-[30px]">
                World-class care for everyone . Our health System offers unmatched, expert health care.
            </p> -->
        <x-section-heading
            title=" Meet Our Top Specialists"
            subtitle=" World-class care for everyone . Our health System offers unmatched, expert health care.
                    " />
    </div>


    <div class="mt-[30px] grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 lg:mt-[55px] lg:gap-[30px]">
        <article class="p-3 lg:p-5">
            <img src="{{ asset('images/doctor-img01.png') }}" alt="Dr. Alfaz Ahmed" class="w-full">
            <h2 class="mt-3 text-[18px] font-bold leading-[30px] text-[#181A1E] lg:mt-5 lg:text-[26px] lg:leading-9">Dr. Alfaz Ahmed</h2>
            <div class="mt-2 flex items-center justify-between lg:mt-4">
                <span class="rounded bg-[#CCF0F3] px-2 py-1 text-[12px] font-semibold leading-4 text-[#01B5C5] lg:px-6 lg:py-2 lg:text-base lg:leading-7">Surgeon</span>
                <div class="flex items-center gap-[6px]">
                    <span class="flex items-center gap-[6px] text-sm font-semibold leading-6 text-[#181A1E] lg:text-base lg:leading-7">
                        <img src="{{ asset('images/Star.png') }}" alt=""> 5
                    </span>
                    <span class="text-sm font-normal leading-6 text-[#4E545F] lg:text-base lg:leading-7">(272)</span>
                </div>
            </div>
            <div class="mt-[10px] flex items-center justify-between lg:mt-5">
                <p class="text-base font-normal leading-6 text-[#4E545F]">At Mount Adora Hospital, Sylhet.</p>
                <a href="{{ route('doctors.index') }}" class="group flex h-[44px] w-[44px] items-center justify-center rounded-full border border-[#181A1E] hover:border-transparent hover:bg-[#0067FF]">
                    <svg class="h-5 w-6 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                    </svg>
                </a>
            </div>
        </article>

        <article class="p-3 lg:p-5">
            <img src="{{ asset('images/doctor-img02.png') }}" alt="Dr. Saleh Mahmud" class="w-full">
            <h2 class="mt-3 text-[18px] font-bold leading-[30px] text-[#181A1E] lg:mt-5 lg:text-[26px] lg:leading-9">Dr. Saleh Mahmud</h2>
            <div class="mt-2 flex items-center justify-between lg:mt-4">
                <span class="rounded bg-[#CCF0F3] px-2 py-1 text-[12px] font-semibold leading-4 text-[#01B5C5] lg:px-6 lg:py-2 lg:text-base lg:leading-7">Neurologist</span>
                <div class="flex items-center gap-[6px]">
                    <span class="flex items-center gap-[6px] text-sm font-semibold leading-6 text-[#181A1E] lg:text-base lg:leading-7">
                        <img src="{{ asset('images/Star.png') }}" alt=""> 5
                    </span>
                    <span class="text-sm font-normal leading-6 text-[#4E545F] lg:text-base lg:leading-7">(272)</span>
                </div>
            </div>
            <div class="mt-[10px] flex items-center justify-between lg:mt-5">
                <p class="text-base font-normal leading-6 text-[#4E545F]">At Mount Adora Hospital, Sylhet.</p>
                <a href="{{ route('doctors.index') }}" class="group flex h-[44px] w-[44px] items-center justify-center rounded-full border border-[#181A1E] hover:border-transparent hover:bg-[#0067FF]">
                    <svg class="h-5 w-6 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                    </svg>
                </a>
            </div>
        </article>

        <article class="p-3 lg:p-5">
            <img src="{{ asset('images/doctor-img03.png') }}" alt="Dr. Farid Uddin" class="w-full">
            <h2 class="mt-3 text-[18px] font-bold leading-[30px] text-[#181A1E] lg:mt-5 lg:text-[26px] lg:leading-9">Dr. Farid Uddin</h2>
            <div class="mt-2 flex items-center justify-between lg:mt-4">
                <span class="rounded bg-[#CCF0F3] px-2 py-1 text-[12px] font-semibold leading-4 text-[#01B5C5] lg:px-6 lg:py-2 lg:text-base lg:leading-7">Dermatologist</span>
                <div class="flex items-center gap-[6px]">
                    <span class="flex items-center gap-[6px] text-sm font-semibold leading-6 text-[#181A1E] lg:text-base lg:leading-7">
                        <img src="{{ asset('images/Star.png') }}" alt=""> 5
                    </span>
                    <span class="text-sm font-normal leading-6 text-[#4E545F] lg:text-base lg:leading-7">(272)</span>
                </div>
            </div>
            <div class="mt-[10px] flex items-center justify-between lg:mt-5">
                <p class="text-base font-normal leading-6 text-[#4E545F]">At Mount Adora Hospital, Sylhet.</p>
                <a href="{{ route('doctors.index') }}" class="group flex h-[44px] w-[44px] items-center justify-center rounded-full border border-[#181A1E] hover:border-transparent hover:bg-[#0067FF]">
                    <svg class="h-5 w-6 group-hover:text-white" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8" />
                    </svg>
                </a>
            </div>
        </article>
    </div>
    </div>
</section>