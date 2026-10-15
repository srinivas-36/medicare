@extends('layouts.dashboard')

@php
    $snapshot = $snapshot ?? [];
    $patient = is_array($snapshot['patient'] ?? null) ? $snapshot['patient'] : [];
    $from = is_array($snapshot['referring_doctor'] ?? null) ? $snapshot['referring_doctor'] : [];
    $visit = is_array($snapshot['visit'] ?? null) ? $snapshot['visit'] : [];
    $packet = is_array($snapshot['patient_summary'] ?? null) ? $snapshot['patient_summary'] : [];
    $recipient = $referral->toDoctor;
    $incoming = $incoming ?? false;

    $patientName = $patient['name'] ?? ($referral->patient?->name ?? 'Patient');
    $age = filled($patient['age'] ?? null) ? $patient['age'].' years' : '';
    $gender = filled($patient['gender'] ?? null) ? ucfirst((string) $patient['gender']) : '';
    $meta = collect([$age, $gender])->filter()->implode(' · ');

    $conditionLabels = [
        'hyperthyroidism' => 'Hyperthyroidism',
        'diabetic' => 'Diabetes',
        'bp' => 'Blood pressure',
    ];
    $known = [];
    foreach ($conditionLabels as $key => $label) {
        $record = data_get($patient, 'health_notes.'.$key, []);
        if (! is_array($record) || ! filter_var($record['known'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            continue;
        }
        $value = trim((string) ($record['value'] ?? ''));
        $known[] = ($value !== '' && ! in_array(strtolower($value), ['yes', 'true', '1'], true))
            ? $label.' '.$value
            : $label;
    }

    $fromName = $from['name'] ?? ($referral->fromDoctor?->user?->name ?? 'Doctor');
    $toName = $recipient?->user?->name ?? 'Doctor';
    $toSpecialty = $recipient?->specialization ?? '';
    $clinic = $recipient?->experiences?->sortByDesc('id')->first()?->hospital_name;
    $sentLabel = $referral->created_at
        ? $referral->created_at->timezone(config('app.timezone'))->format('j M Y, g:i A')
        : ($sent ?? '');

    $summaryText = filled($packet['text'] ?? null)
        ? $packet['text']
        : ($snapshot['approved_summary'] ?? $snapshot['ai_summary'] ?? null);
    $briefText = $packet['referral_brief'] ?? $snapshot['referral_brief'] ?? null;
    $source = $packet['source'] ?? (filled($snapshot['approved_summary'] ?? null) ? 'approved' : (filled($summaryText) ? 'analysis' : null));

    $reportNotes = collect(is_array($packet['report_notes'] ?? null) ? $packet['report_notes'] : []);
    $usedNoteIds = [];
    $reportCards = ($referral->reports ?? collect())->map(function ($report) use ($referral, $reportNotes, &$usedNoteIds) {
        $extension = strtolower((string) pathinfo((string) $report->original_name, PATHINFO_EXTENSION));
        $kind = match ($extension) {
            'pdf' => 'PDF',
            'png', 'jpg', 'jpeg', 'webp', 'gif' => 'Image',
            default => 'File',
        };
        $group = trim((string) $report->group_name);
        $filename = (string) $report->original_name;
        $note = $reportNotes->first(fn ($item) => (int) ($item['report_id'] ?? 0) === (int) $report->id);
        if (is_array($note)) {
            $usedNoteIds[] = (int) $report->id;
        }

        return [
            'title' => $group !== '' ? $group : ($filename !== '' ? $filename : 'Report'),
            'filename' => $filename,
            'kind' => $kind,
            'date' => $report->created_at?->timezone(config('app.timezone'))->format('j M Y') ?? '',
            'url' => route('doctor.referrals.reports.show', [$referral, $report]),
            'note' => is_array($note) ? $note : null,
        ];
    })->all();
    $extraNotes = $reportNotes
        ->reject(fn ($item) => in_array((int) ($item['report_id'] ?? 0), $usedNoteIds, true))
        ->values()
        ->all();

    $footer = match (true) {
        $referral->status === 'declined' => $toName.' declined this referral.',
        $referral->status === 'appointment_booked' => $toName.' booked the referred visit.',
        $referral->status === 'accepted' && ! $incoming => $toName.' accepted. They can book the visit from their schedule.',
        $referral->status === 'accepted' => 'Accepted. Choose an open time to book the visit.',
        $incoming => 'This referral is waiting for your decision.',
        default => 'Awaiting response from '.$toName,
    };
    $footerTone = match ($referral->status) {
        'declined' => 'alert',
        'accepted', 'appointment_booked' => 'done',
        default => 'waiting',
    };

    $detail = [
        'patientName' => $patientName,
        'meta' => $meta,
        'known' => implode(' · ', $known),
        'clinic' => $clinic,
        'referredTo' => $toName,
        'referredToSpecialty' => $toSpecialty,
        'referredBy' => $fromName,
        'sent' => $sentLabel,
        'status' => $status,
        'statusValue' => $referral->status,
        'reason' => $referral->reason,
        'question' => $referral->clinical_question,
        'declineReason' => $referral->status === 'declined' ? $referral->decline_reason : null,
        'visit' => [
            'notes' => $visit['notes'] ?? null,
            'diagnosis' => $visit['diagnosis'] ?? null,
            'plan' => $visit['plan'] ?? null,
        ],
        'summaryText' => $summaryText,
        'briefText' => $briefText,
        'source' => $source,
        'highlights' => $packet['highlights'] ?? [],
        'findings' => $packet['findings'] ?? [],
        'steps' => $packet['steps'] ?? [],
        'limitations' => $packet['limitations'] ?? null,
        'reports' => $reportCards,
        'extraNotes' => $extraNotes,
        'footer' => $footer,
        'footerTone' => $footerTone,
        'patientUrl' => $referral->patient ? route('doctor.patients.show', $referral->patient) : null,
        'bookedUrl' => ($incoming && $referral->status === 'appointment_booked' && $referral->proposedAppointment)
            ? route('doctor.appointments.show', $referral->proposedAppointment)
            : null,
        'acceptUrl' => ($incoming && $referral->status === 'sent') ? route('doctor.referrals.accept', $referral) : null,
        'declineUrl' => ($incoming && $referral->status === 'sent') ? route('doctor.referrals.decline', $referral) : null,
        'scheduleUrl' => route('doctor.referrals.schedule', $referral),
        'openSlots' => $openSlots ?? [],
        'showBooking' => $incoming && $referral->status === 'accepted',
    ];
@endphp

@section('title', 'Referral details')

@section('content')
    @include('dashboard.doctor.referrals.partials.detail-screen', ['detail' => $detail])
@endsection
