<article class="dash-stat">
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted">{{ $stat['label'] }}</p>
    <p class="mt-3 text-3xl font-bold tracking-tight text-navy">{{ $stat['value'] }}</p>
    @if (! empty($stat['note']))
        <p class="mt-2 text-sm font-medium text-primary">{{ $stat['note'] }}</p>
    @endif
</article>
