@extends('layouts.dashboard')

@php
    $referral = $referral ?? [];
    $summary = is_array($summary ?? null) ? $summary : [];
    $demographics = is_array($demographics ?? null) ? $demographics : [];
    $viewer = is_array($viewer ?? null) ? $viewer : ['name' => 'Doctor', 'specialization' => ''];
    $outgoing = ($referral['direction'] ?? '') === 'Outgoing';
    $otherName = $referral['name'] ?? 'Doctor';
    $otherSpecialty = $referral['specialization'] ?? '';
    $directory = collect(\App\Support\MockReferralDirectory::doctors());
    $clinic = trim((string) ($referral['clinic'] ?? ''));
    if ($clinic === '') {
        $match = $directory->firstWhere('name', $outgoing ? $otherName : ($viewer['name'] ?? ''));
        $clinic = (string) ($match['clinic'] ?? '');
    }

    $age = filled($demographics['age'] ?? null) ? $demographics['age'].' years' : '';
    $gender = filled($demographics['gender'] ?? null) ? ucfirst((string) $demographics['gender']) : '';
    $meta = collect([$age, $gender])->filter()->implode(' · ');
    $conditionLabels = [
        'hyperthyroidism' => 'Hyperthyroidism',
        'diabetic' => 'Diabetes',
        'bp' => 'Blood pressure',
    ];
    $known = [];
    foreach ($conditionLabels as $key => $label) {
        $record = data_get($demographics, 'health_notes.'.$key, []);
        if (! is_array($record) || ! filter_var($record['known'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            continue;
        }
        $value = trim((string) ($record['value'] ?? ''));
        $known[] = ($value !== '' && ! in_array(strtolower($value), ['yes', 'true', '1'], true))
            ? $label.' '.$value
            : $label;
    }

    $sentLabel = str_replace(' · ', ', ', (string) ($referral['sent'] ?? ''));
    $status = (string) ($referral['status'] ?? 'Sent');
    $statusValue = match ($status) {
        'Accepted' => 'accepted',
        'Declined' => 'declined',
        'Appointment booked' => 'appointment_booked',
        default => 'sent',
    };
    $toName = $outgoing ? $otherName : ($viewer['name'] ?? 'Doctor');
    $fromName = $outgoing ? ($viewer['name'] ?? 'Doctor') : ($referral['from'] ?? $otherName);

    $reportNotes = collect(is_array($summary['report_notes'] ?? null) ? $summary['report_notes'] : []);
    $usedNoteIds = [];
    $reportCards = collect($reports ?? [])->map(function ($report) use ($reportNotes, &$usedNoteIds) {
        $group = trim((string) ($report['group'] ?? ''));
        $title = trim((string) ($report['title'] ?? ''));
        $heading = $group !== '' ? $group : ($title !== '' ? $title : 'Report');
        $note = $reportNotes->first(fn ($item) => (int) ($item['report_id'] ?? 0) === (int) ($report['id'] ?? 0));
        if (is_array($note)) {
            $usedNoteIds[] = (int) ($report['id'] ?? 0);
        }

        return [
            'title' => $heading,
            'filename' => $title !== '' && $title !== $heading ? $title : '',
            'kind' => $report['kind'] ?? 'File',
            'date' => $report['date'] ?? '',
            'url' => $report['url'] ?? null,
            'note' => is_array($note) ? $note : null,
        ];
    })->all();
    $extraNotes = $reportNotes
        ->reject(fn ($item) => in_array((int) ($item['report_id'] ?? 0), $usedNoteIds, true))
        ->values()
        ->all();

    $footer = match (true) {
        $status === 'Declined' => $toName.' declined this referral.',
        $status === 'Appointment booked' => $toName.' booked the referred visit.',
        $status === 'Accepted' => $toName.' accepted. They can book the visit from their schedule.',
        $outgoing => 'Awaiting response from '.$toName,
        default => 'This referral is waiting for your decision.',
    };

    $detail = [
        'patientName' => $referral['patient'] ?? 'Patient',
        'meta' => $meta,
        'known' => implode(' · ', $known),
        'clinic' => $clinic,
        'referredTo' => $toName,
        'referredToSpecialty' => $outgoing ? $otherSpecialty : ($viewer['specialization'] ?? ''),
        'referredBy' => $fromName,
        'sent' => $sentLabel,
        'status' => $status,
        'statusValue' => $statusValue,
        'reason' => $referral['reason'] ?? null,
        'question' => $referral['question'] ?? null,
        'declineReason' => null,
        'visit' => [],
        'summaryText' => $summary['text'] ?? null,
        'briefText' => $summary['referral_brief'] ?? null,
        'source' => $summary['source'] ?? null,
        'highlights' => $summary['highlights'] ?? [],
        'findings' => $summary['findings'] ?? [],
        'steps' => $summary['steps'] ?? [],
        'limitations' => $summary['limitations'] ?? null,
        'reports' => $reportCards,
        'extraNotes' => $extraNotes,
        'footer' => $footer,
        'footerTone' => match ($statusValue) {
            'declined' => 'alert',
            'accepted', 'appointment_booked' => 'done',
            default => 'waiting',
        },
        'patientUrl' => null,
        'bookedUrl' => null,
        'acceptUrl' => null,
        'declineUrl' => null,
        'showBooking' => false,
    ];
@endphp

@section('title', 'Referral details')

@section('content')
    @include('dashboard.doctor.referrals.partials.detail-screen', ['detail' => $detail])
@endsection
