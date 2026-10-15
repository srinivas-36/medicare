{{--
    The six care areas live in PublicCatalog::services().
    Home receives the full list and shows the first three.
    The services page receives a paginator so the rest can follow on later pages.
--}}
@php
$services = $services ?? collect();
$paginated = $services instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
$cards = $paginated ? collect($services->items()) : collect($services)->take(3);
$serviceCount = $paginated ? $services->total() : collect($services)->count();
$numberOffset = $paginated ? (($services->currentPage() - 1) * $services->perPage()) : 0;
$showHeading = $showHeading ?? true;
$tones = [
'bg-[#FBF4DC] text-[#C6A15B]',
'bg-[#F3EAFE] text-[#B39AF0]',
'bg-[#DFF7F4] text-[#67CFC6]',
'bg-[#E7F0FF] text-[#7EA6E8]',
'bg-[#FDECEC] text-[#E39AA4]',
'bg-[#E9F8EE] text-[#79C49A]',
];
@endphp

<section class="py-14 lg:py-20">
    <div class="page">
        @if ($showHeading)
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <x-section-heading
                align="left"
                title="Comprehensive Healthcare Services"
                subtitle="From routine checkups to specialized treatments, find care that fits your needs." />
            @if ($serviceCount > 3)
            <a href="{{ route('services.index') }}" class="shrink-0 text-sm font-semibold text-primary no-underline hover:text-primary-hover">
                View all services
            </a>
            @endif
        </div>
        @endif

        @if ($cards->isEmpty())
        <div class="mt-10">
            <x-ui.empty-state
                title="Services will appear here"
                text="Care areas show up here so patients can browse doctors by the kind of visit they need.">
                <a href="{{ route('doctors.index') }}" class="btn-primary">Browse doctors</a>
            </x-ui.empty-state>
        </div>
        @else
        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($cards as $service)
            <article class="flex h-full flex-col rounded-[22px] border border-[#EEF2F6] bg-white p-3 shadow-[0_10px_30px_rgba(15,23,42,0.06)]">
                <img src="{{ asset($service['photo']) }}" alt="{{ $service['name'] }}" class="aspect-[3/2] w-full rounded-2xl bg-[#F7FAFF] object-contain object-center" loading="lazy">
                <div class="flex flex-1 flex-col px-2 pb-1 pt-4">
                    <h3 class="text-[17px] font-bold tracking-tight text-[#1A1D21]">{{ $service['name'] }}</h3>
                    <p class="mt-2 flex-1 text-sm leading-6 text-[#6B7280]">{{ $service['text'] }}</p>
                    <div class="mt-5 flex items-center justify-between">
                        <a href="{{ route('doctors.index', ['query' => $service['query']]) }}" aria-label="View {{ $service['name'] }} doctors" class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-[#D8DEE6] text-[#1F2937] no-underline transition hover:border-[#0067FF] hover:text-[#0067FF]">
                            <x-ui.icon name="arrow" class="h-4 w-4" />
                        </a>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-sm font-semibold {{ $tones[($numberOffset + $loop->iteration - 1) % 6] }}">{{ $numberOffset + $loop->iteration }}</span>
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        @if ($paginated && $services->hasPages())
        <div class="mt-10">
            {{ $services->links() }}
        </div>
        @endif
        @endif
    </div>
</section>