<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>Medicare</title>

    @vite(['resources/css/app.css','resources/js/app.js'])

</head>

<body class="overflow-x-hidden bg-white">

    @include('partials.header')

    <main class="">
        @yield('content')
    </main>

    @include('partials.footer')

</body>

</html>