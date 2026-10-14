@extends('layouts.dashboard')

@section('title', 'Reports')

@section('content')
    <x-ui.page-header title="Reports" description="Files patients attached to their visits." />

    <section class="overflow-hidden rounded-2xl border border-[#E7EEF6] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        @if ($reports->isEmpty())
            <p class="px-5 py-10 text-sm text-[#64748B]">No reports have been uploaded.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-[#F1F5F9] text-[11px] font-semibold uppercase tracking-[0.08em] text-[#94A3B8]">
                            <th class="px-5 py-3 font-semibold">File</th>
                            <th class="px-3 py-3 font-semibold">Group</th>
                            <th class="px-3 py-3 font-semibold">Patient</th>
                            <th class="px-3 py-3 font-semibold">Visit</th>
                            <th class="px-4 py-3 font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F1F5F9]">
                        @foreach ($reports as $report)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-[#0F172A]">{{ $report->original_name }}</td>
                                <td class="px-3 py-3 text-[#475569]">{{ $report->group_name }}</td>
                                <td class="px-3 py-3 text-[#475569]">{{ $report->appointment?->patient?->name ?? 'Patient' }}</td>
                                <td class="px-3 py-3 text-[#475569]">{{ $report->appointment?->appointment_date?->format('j M Y') }}</td>
                                <td class="px-4 py-3 text-right">
                                    @if ($report->appointment)
                                        <a href="{{ route('doctor.appointments.reports.show', [$report->appointment, $report]) }}" class="text-sm font-semibold text-[#0067FF] no-underline">Open</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($reports->hasPages())
                <div class="border-t border-[#F1F5F9] px-5 py-3">{{ $reports->links() }}</div>
            @endif
        @endif
    </section>
@endsection
