<div class="rounded-2xl border border-[#0066ff1f] bg-white p-5 shadow-[0_12px_40px_rgba(0,103,255,0.08)]">
    <p class="text-sm font-semibold text-[#4E545F]">{{ $stat['label'] }}</p>
    <p class="mt-2 text-3xl font-extrabold tracking-tight text-[#181A1E]">{{ $stat['value'] }}</p>
    @if (! empty($stat['note']))
    <p class="mt-1 text-sm font-medium text-[#0067FF]">{{ $stat['note'] }}</p>
    @endif
</div>