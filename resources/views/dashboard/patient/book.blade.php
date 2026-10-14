@extends('layouts.dashboard')

@php
    $name = $doctor->user?->name ?? 'Doctor';
    $specialty = $doctor->specialization ?: '';
@endphp

@section('title', 'Confirm booking')

@section('content')
    <x-ui.page-header
        title="Confirm booking"
        description="Choose an open time{{ $specialty ? ' with '.$specialty : '' }}, add notes, and upload any reports before you submit."
    >
        <a href="{{ route('patient.book') }}" class="btn-secondary">Change doctor</a>
    </x-ui.page-header>

    <x-dashboard.booking-steps :step="2" />

    @include('dashboard.patient.partials.book-form')
@endsection
