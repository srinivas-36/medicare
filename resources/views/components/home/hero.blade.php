<!-- <section class="-mt-[10px] bg-gradient-to-b from-[#e7f6fb] to-white pb-10 pt-[66px] md:pb-16 md:pt-[90px]">
    <div class="mx-auto w-full max-w-[1440px] px-5 text-center">
        <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-[#181A1E] md:text-6xl">
            Find the right doctor.
        </h1>
        <p class="mt-4 text-xl font-semibold text-[#181A1E] md:text-[26px]">
            Book in minutes. Get smarter care.
        </p>
        <p class="mx-auto mt-3 max-w-2xl text-base leading-7 text-[#4E545F] md:text-lg">
            AI-assisted booking &amp; referrals for faster, transparent healthcare.
        </p>

        <form action="{{ route('doctors.index') }}" method="GET" class="mx-auto mt-10 flex w-full max-w-4xl flex-col gap-3 rounded-2xl border border-[#0066ff1f] bg-white p-3 shadow-[0_12px_40px_rgba(0,103,255,0.10)] md:flex-row md:items-center">
            <label class="block w-full md:min-w-0 md:flex-1">
                <span class="sr-only">Search by symptom or specialty</span>
                <input
                    type="text"
                    name="query"
                    placeholder="Search by symptom / specialty"
                    class="h-12 w-full rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20"
                >
            </label>
            <label class="block w-full md:w-56 md:shrink-0">
                <span class="sr-only">City or locality</span>
                <input
                    type="text"
                    name="city"
                    placeholder="City / locality"
                    class="h-12 w-full rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20"
                >
            </label>
            <button type="submit" class="inline-flex h-12 w-full shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-[#0067FF] px-7 text-base font-semibold text-white transition hover:bg-[#0056d6] md:w-auto">
                Search Doctors
            </button>
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-2 text-sm">
            <span class="text-[#4E545F]">Popular:</span>
            @foreach (['Surgeon', 'Neurologist', 'Dermatologist'] as $specialty)
                <a href="{{ route('doctors.index', ['query' => $specialty]) }}" class="rounded-full bg-white px-3.5 py-1.5 font-medium text-[#0067FF] ring-1 ring-[#0067FF]/20 transition hover:bg-[#0067FF] hover:text-white">
                    {{ $specialty }}
                </a>
            @endforeach
        </div>
    </div>
</section> -->


<section class="-mt-[10px] bg-gradient-to-b from-[#e7f6fb] to-white pb-10 pt-[66px] md:pb-16 md:pt-[90px]">
    <div class="mx-auto w-full max-w-[1440px] px-5 text-center">
        <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-[#181A1E] md:text-6xl">
            Find the right doctor.
        </h1>
        <p class="mt-4 text-xl font-semibold text-[#181A1E] md:text-[26px]">
            Book in minutes. Get smarter care.
        </p>
        <p class="mx-auto mt-3 max-w-2xl text-base leading-7 text-[#4E545F] md:text-lg">
            AI-assisted booking &amp; referrals for faster, transparent healthcare.
        </p>

        <form action="{{ route('doctors.index') }}" method="GET" class="mx-auto mt-10 flex w-full max-w-4xl flex-col gap-3 rounded-2xl border border-[#0066ff1f] bg-white p-3 shadow-[0_12px_40px_rgba(0,103,255,0.10)] md:flex-row md:items-center">
            <label class="block w-full md:min-w-0 md:flex-1">
                <span class="sr-only">Search by symptom or specialty</span>
                <input
                    type="text"
                    name="query"
                    placeholder="Search by symptom / specialty"
                    class="h-12 w-full rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20">
            </label>
            <label class="block w-full md:w-56 md:shrink-0">
                <span class="sr-only">City or locality</span>
                <input
                    type="text"
                    name="city"
                    placeholder="City / locality"
                    class="h-12 w-full rounded-xl border border-[#c9dcff] bg-[#f7faff] px-4 text-base text-[#181A1E] placeholder:text-[#4E545F] focus:border-[#0067FF] focus:outline-none focus:ring-2 focus:ring-[#0067FF]/20">
            </label>
            <button type="submit" class="inline-flex h-12 w-full shrink-0 items-center justify-center whitespace-nowrap rounded-xl bg-[#0067FF] px-7 text-base font-semibold text-white transition hover:bg-[#0056d6] md:w-auto">
                Search Doctors
            </button>
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-center gap-2 text-sm">
            <span class="text-[#4E545F]">Popular:</span>
            @foreach (['Surgeon', 'Neurologist', 'Dermatologist'] as $specialty)
            <a href="{{ route('doctors.index', ['query' => $specialty]) }}" class="rounded-full bg-white px-3.5 py-1.5 font-medium text-[#0067FF] ring-1 ring-[#0067FF]/20 transition hover:bg-[#0067FF] hover:text-white">
                {{ $specialty }}
            </a>
            @endforeach
        </div>
    </div>
</section>