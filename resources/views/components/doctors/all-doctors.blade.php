<main class="overflow-hidden">
    <section class="bg-[#fff9ea] py-[35px] lg:py-[76px]">
        <div class="mx-auto w-full max-w-[1440px] px-5 text-center">
            <h2 class="text-[26px] font-extrabold leading-[36px] text-[#181A1E] md:text-[44px] md:leading-[54px]">
                Find a Doctor
            </h2>

            <form action="{{ route('doctors.index') }}" method="GET"
                class="mx-auto mt-[30px] flex max-w-[570px] items-center justify-between rounded-md bg-[#0066ff2c]">

                <input
                    type="search"
                    name="query"
                    value="{{ request('query') }}"
                    placeholder="Search Doctor by name or specialization"
                    class="w-full bg-transparent py-4 pl-4 pr-2 placeholder:text-[#4E545F] focus:outline-none">

                <button
                    type="submit"
                    class="rounded-r-md bg-[#0067FF] px-[35px] py-[15px] font-semibold text-white">
                    Search
                </button>
            </form>
        </div>
    </section>

    <section class="py-[35px] lg:py-[76px]">
        <div class="mx-auto w-full max-w-[1440px] px-5">

            @if($doctors->count())
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">

                @foreach($doctors as $doctor)
                <article class="rounded-xl border border-gray-200 p-3 shadow-sm lg:p-5">

                    <img
                        src="{{ asset('images/doctor-placeholder.png') }}"
                        alt="{{ $doctor->user->name }}"
                        class="w-full rounded-lg">

                    <h2 class="mt-3 text-[18px] font-bold leading-[30px] text-[#181A1E] lg:mt-5 lg:text-[26px] lg:leading-9">
                        {{ $doctor->user->name }}
                    </h2>

                    <div class="mt-2 flex items-center justify-between lg:mt-4">

                        <span
                            class="rounded bg-[#CCF0F3] px-2 py-1 text-[12px] font-semibold leading-4 text-[#01B5C5] lg:px-6 lg:py-2 lg:text-base lg:leading-7">
                            {{ $doctor->specialization }}
                        </span>

                        <div class="flex items-center gap-[6px]">
                            <span
                                class="flex items-center gap-[6px] text-sm font-semibold leading-6 text-[#181A1E] lg:text-base lg:leading-7">
                                <img src="{{ asset('images/Star.png') }}" alt="">
                                {{ $doctor->average_rating ?? '0.0' }}
                            </span>

                            <span
                                class="text-sm font-normal leading-6 text-[#4E545F] lg:text-base lg:leading-7">
                                ({{ $doctor->total_rating ?? 0 }})
                            </span>
                        </div>

                    </div>

                    <div class="mt-[10px] flex items-center justify-between lg:mt-5">

                        <p class="text-base font-normal leading-6 text-[#4E545F]">
                            ₹{{ number_format($doctor->ticket_price) }}
                        </p>

                        <a
                            href="{{ route('doctors.show', $doctor->user->slug) }}"
                            class="group flex h-[44px] w-[44px] shrink-0 items-center justify-center rounded-full border border-[#181A1E] hover:border-none hover:bg-[#0067FF]">

                            <svg
                                class="h-5 w-6 group-hover:text-white"
                                viewBox="0 0 16 16"
                                fill="currentColor"
                                aria-hidden="true">

                                <path
                                    fill-rule="evenodd"
                                    d="M1 8a.5.5 0 0 1 .5-.5h11.793l-3.147-3.146a.5.5 0 0 1 .708-.708l4 4a.5.5 0 0 1 0 .708l-4 4a.5.5 0 0 1-.708-.708L13.293 8.5H1.5A.5.5 0 0 1 1 8z" />
                            </svg>

                        </a>

                    </div>
                </article>
                @endforeach

            </div>

            <div class="mt-10">
                {{ $doctors->links() }}
            </div>

            @else

            <p class="text-center text-base leading-7 text-[#4E545F]">
                No doctors found.
            </p>

            @endif

        </div>
    </section>

    @include('components.home.testimonials')
</main>