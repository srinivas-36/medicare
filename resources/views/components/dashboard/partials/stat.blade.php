<div class="rounded-2xl border border-[#E2EAF4] bg-white p-5 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
    <p class="text-sm font-semibold text-[#53647D]">{{ $stat['label'] }}</p>
    <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#0B1F3A]">{{ $stat['value'] }}</p>
    @if (! empty($stat['note']))
        <p class="mt-1 text-sm font-medium text-[#1769FF]">{{ $stat['note'] }}</p>
    @endif
</div>
