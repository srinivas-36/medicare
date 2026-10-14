@php
    $note = is_array($note ?? null) ? $note : [];
    $rows = array_values(array_filter($note['rows'] ?? [], fn ($row) => is_array($row)));
@endphp

@if (filled($note['narrative'] ?? null))
    <p class="mt-3 text-sm leading-6 text-[#53647D]">{{ $note['narrative'] }}</p>
@endif

@if ($rows !== [])
    <div class="mt-3 overflow-x-auto rounded-xl border border-[#E2EAF4]">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="bg-[#F4F7FB] text-[11px] font-semibold uppercase tracking-wide text-[#53647D]">
                <tr>
                    <th class="px-4 py-2.5 font-semibold">Parameter</th>
                    <th class="px-4 py-2.5 font-semibold">Result</th>
                    <th class="px-4 py-2.5 font-semibold">Reference</th>
                    <th class="px-4 py-2.5 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E2EAF4] bg-white">
                @foreach ($rows as $row)
                    @php
                        $flag = strtolower(trim((string) ($row['flag'] ?? '')));
                        $abnormal = $flag !== '' && ! in_array($flag, ['normal', 'unknown'], true);
                    @endphp
                    <tr>
                        <td class="px-4 py-2.5 font-medium text-[#0B1F3A]">{{ $row['parameter'] ?: 'Result' }}</td>
                        <td class="px-4 py-2.5 tabular-nums text-[#0B1F3A]">{{ filled($row['value'] ?? null) ? $row['value'] : '—' }}</td>
                        <td class="px-4 py-2.5 tabular-nums text-[#53647D]">{{ filled($row['range'] ?? null) ? $row['range'] : '—' }}</td>
                        <td class="px-4 py-2.5">
                            @if ($flag === '' || $flag === 'unknown')
                                <span class="text-[#53647D]">—</span>
                            @elseif ($abnormal)
                                <span class="font-semibold text-[#C2410C]">{{ ucfirst($flag) }}</span>
                            @else
                                <span class="font-semibold text-success">Normal</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
