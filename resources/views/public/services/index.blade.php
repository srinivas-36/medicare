@extends('layouts.public')

@section('title', 'Services')
@section('description', 'Browse cancer care, heart and vascular, neurology, mental health, labor and delivery, and burn care.')

@section('content')
<x-page-intro
    eyebrow="Care"
    title="Services"
    subtitle="Browse each care area, then open the doctors who match." />
@include('components.home.medical-services', ['showHeading' => false])
@endsection