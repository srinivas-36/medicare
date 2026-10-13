<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex">
        <title>Medicare</title>
        @include('partials.fonts')
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-heading antialiased">
        <div class="flex min-h-screen flex-col items-center justify-center bg-light px-5 py-10">
            <a href="{{ route('home') }}" class="mb-6">
                <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="h-12 w-auto">
            </a>

            <div class="card w-full sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
