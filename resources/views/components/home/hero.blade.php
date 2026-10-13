{{--
    Homepage hero. The search form uses the existing doctors.index ?query= filter.
    Location is not a supported filter, so it is not shown.
--}}
<section class="bg-light">
    <div class="page grid items-center gap-12 py-12 lg:grid-cols-2 lg:py-16">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-primary">Care that fits your life</p>
            <h1 class="mt-3 text-4xl font-extrabold leading-[1.12] tracking-tight text-navy sm:text-5xl lg:text-[56px]">
                Your Health<br>In Trusted Hands
            </h1>
            <p class="mt-4 max-w-xl text-base leading-7 text-body sm:text-[17px]">
                Find the right doctor, book appointments in minutes, and get the care you need.
            </p>

            <form action="{{ route('doctors.index') }}" method="GET" class="mt-8 rounded-[20px] border border-line bg-white p-3 shadow-card sm:flex sm:items-center sm:gap-3">
                <label class="relative block min-w-0 flex-1">
                    <span class="sr-only">Search by doctor name or specialty</span>
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted">
                        <x-ui.icon name="search" />
                    </span>
                    <input
                        type="search"
                        name="query"
                        value="{{ request('query') }}"
                        placeholder="Search by doctor name or specialty"
                        class="field pl-11"
                    >
                </label>
                <button type="submit" class="btn-primary mt-3 w-full sm:mt-0 sm:w-auto">Find a Doctor</button>
            </form>

            <ul class="mt-5 grid grid-cols-1 gap-3 text-sm font-medium text-navy sm:grid-cols-2">
                <li class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mint text-success"><x-ui.icon name="check" class="h-4 w-4" /></span>
                    Approved doctors
                </li>
                <li class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mint text-success"><x-ui.icon name="card" class="h-4 w-4" /></span>
                    Fees shown first
                </li>
                <li class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mint text-success"><x-ui.icon name="calendar" class="h-4 w-4" /></span>
                    Request a time
                </li>
                <li class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-mint text-success"><x-ui.icon name="shield" class="h-4 w-4" /></span>
                    Your own bookings
                </li>
            </ul>
        </div>

        <div class="relative mx-auto w-full max-w-lg">
            <div class="absolute -left-4 top-10 h-24 w-24 rounded-full bg-mint" aria-hidden="true"></div>
            <div class="absolute -right-3 bottom-8 h-16 w-16 rounded-full bg-primary/15" aria-hidden="true"></div>
            <div class="relative overflow-hidden rounded-[32px] border border-line bg-white shadow-card">
                <img
                    src="{{ asset('images/hero-img02.png') }}"
                    alt=""
                    width="640"
                    height="760"
                    class="h-[420px] w-full object-cover object-top sm:h-[480px]"
                >
            </div>
        </div>
    </div>
</section>
