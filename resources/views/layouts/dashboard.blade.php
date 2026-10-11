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



    <main class="overflow-hidden bg-gradient-to-b from-[#e8f2ff] via-[#f7faff] to-white pt-24">
        <section class="py-10 lg:py-14">
            <div class="mx-auto flex w-full max-w-[1440px] flex-col gap-6 px-5 lg:flex-row lg:items-start lg:gap-8">
                @include('components.dashboard.sidebar')

                <div class="min-w-0 flex-1">
                    @yield('content')
                </div>
            </div>
        </section>
    </main>


</body>

</html>