<div>
    <x-page-intro
        eyebrow="Directory"
        title="Find a doctor"
        subtitle="Search by name or specialty, then open a profile to see the fee and request a visit."
    >
        <form action="{{ route('doctors.index') }}" method="GET" class="mx-auto flex w-full max-w-2xl flex-col gap-3 text-left sm:flex-row sm:items-center">
            <label class="relative block w-full sm:min-w-0 sm:flex-1">
                <span class="sr-only">Search doctors</span>
                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-muted">
                    <x-ui.icon name="search" />
                </span>
                <input
                    type="search"
                    name="query"
                    value="{{ request('query') }}"
                    placeholder="Name or specialization"
                    class="field bg-white pl-11"
                >
            </label>
            <button type="submit" class="btn-primary w-full sm:w-auto">Search</button>
        </form>
    </x-page-intro>

    <section class="py-14 lg:py-20">
        <div class="page">
            @if ($doctors->count())
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($doctors as $doctor)
                        <x-doctors.card :doctor="$doctor" />
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $doctors->links() }}
                </div>
            @else
                <x-ui.empty-state
                    title="No doctors found"
                    text="Try another name or specialty, or browse the full directory."
                >
                    <a href="{{ route('doctors.index') }}" class="btn-primary">Clear search</a>
                </x-ui.empty-state>
            @endif
        </div>
    </section>
</div>
