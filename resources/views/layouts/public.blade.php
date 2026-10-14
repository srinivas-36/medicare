<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php($pageTitle = trim($__env->yieldContent('title')))
    @php($pageDescription = trim($__env->yieldContent('description')))
    <title>{{ $pageTitle !== '' ? $pageTitle.' — Medicare' : 'Medicare' }}</title>
    <meta name="description" content="{{ $pageDescription !== '' ? $pageDescription : 'Find an approved doctor, see the consultation fee, and request an appointment.' }}">
    @include('partials.fonts')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="overflow-x-hidden font-sans text-body">
    @include('partials.header')

    <main>
        @yield('content')
    </main>

    @if (trim($__env->yieldContent('hide-footer')) === '')
        @include('partials.footer')
    @endif
    @stack('scripts')
</body>
</html>
