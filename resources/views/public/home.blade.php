@extends('layouts.public')

@section('title', 'Home')
@section('description', 'Find an approved doctor, compare the consultation fee, and request an appointment with Medicare.')

@section('content')
    @include('components.home.hero')
    @include('components.home.trust')
    @include('components.home.medical-services')
    @include('components.home.doctors')
    @include('components.home.how-it-works')
    @include('components.home.services')
    @include('components.home.faq')
    @include('components.home.security')
@endsection
