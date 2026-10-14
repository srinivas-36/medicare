@extends('layouts.dashboard')

@section('title', 'Consultations')

@section('content')
    <x-ui.page-header title="Consultations" description="Visits recorded as a consultation, including notes saved on the chart." />

    <section class="overflow-hidden rounded-2xl border border-[#E7EEF6] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
        @if ($rows->isEmpty())
            <p class="px-5 py-10 text-sm text-[#64748B]">No consultations yet.</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-[#F1F5F9] text-[11px] font-semibold uppercase tracking-[0.08em] text-[#94A3B8]">
                            <th class="px-5 py-3 font-semibold">Patient</th>
                            <th class="px-3 py-3 font-semibold">Date</th>
                            <th class="px-3 py-3 font-semibold">Type</th>
                            <th class="px-3 py-3 font-semibold">Diagnosis</th>
                            <th class="px-3 py-3 font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F1F5F9]">
                        @foreach ($rows as $row)
                            <tr>
                                <td class="px-5 py-3 font-semibold text-[#0F172A]">
                                    <a href="{{ route('doctor.appointments.show', $row) }}" class="text-[#0F172A] no-underline hover:text-[#0067FF]">{{ $row->patient?->name ?? 'Patient' }}</a>
                                </td>
                                <td class="px-3 py-3 text-[#475569]">{{ $row->appointment_date?->format('j M Y') }}</td>
                                <td class="px-3 py-3 text-[#475569]">{{ \App\Support\AppointmentPresenter::VISIT_LABELS[$row->visit_type] ?? 'Visit' }}</td>
                                <td class="px-3 py-3 text-[#475569]">{{ $row->consultation?->diagnosis ?: '—' }}</td>
                                <td class="px-3 py-3 text-[#475569]">{{ \App\Support\AppointmentPresenter::STATUS_LABELS[$row->status] ?? ucfirst((string) $row->status) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($rows->hasPages())
                <div class="border-t border-[#F1F5F9] px-5 py-3">{{ $rows->links() }}</div>
            @endif
        @endif
    </section>
@endsection
