@if ($paginator->hasPages())
    <nav class="mt-6 flex flex-col gap-3 border-t border-[#E2E8F0] pt-4 sm:flex-row sm:items-center sm:justify-between" aria-label="Doctor pages">
        <p class="text-sm text-muted">
            Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}
        </p>
        <div class="flex flex-wrap items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <span class="inline-flex h-9 items-center rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm font-semibold text-muted">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex h-9 items-center rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm font-semibold text-heading no-underline hover:border-[#0067FF] hover:text-[#0067FF]">Previous</a>
            @endif

            @foreach ($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg bg-[#0067FF] px-2 text-sm font-semibold text-white">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="inline-flex h-9 min-w-9 items-center justify-center rounded-lg border border-[#E2E8F0] bg-white px-2 text-sm font-semibold text-heading no-underline hover:border-[#0067FF] hover:text-[#0067FF]">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex h-9 items-center rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm font-semibold text-heading no-underline hover:border-[#0067FF] hover:text-[#0067FF]">Next</a>
            @else
                <span class="inline-flex h-9 items-center rounded-lg border border-[#E2E8F0] bg-white px-3 text-sm font-semibold text-muted">Next</span>
            @endif
        </div>
    </nav>
@endif
