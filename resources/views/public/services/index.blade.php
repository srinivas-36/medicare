@extends('layouts.public')

@section('title', 'Services')
@section('description', 'Browse specialties listed by approved doctors and open matching profiles.')

@section('content')
    <x-page-intro
        eyebrow="Care"
        title="Services"
        subtitle="These cards are the specialties approved doctors have entered. Each one searches the directory."
    />
    @include('components.home.medical-services', ['showHeading' => false])
@endsection