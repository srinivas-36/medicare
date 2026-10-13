{{-- Privacy notes limited to checks that exist in the application. --}}
<section class="bg-white py-16 lg:py-20">
    <div class="page">
        <div class="grid items-center gap-8 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)]">
            <div>
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-mint text-primary">
                    <x-ui.icon name="shield" />
                </span>
                @php($securityHeading = request()->routeIs('security') ? 'h1' : 'h2')
                <{{ $securityHeading }} class="mt-4 text-3xl font-bold tracking-tight text-heading md:text-4xl">Security &amp; access</{{ $securityHeading }}>
                <p class="mt-3 text-base leading-7 text-text">
                    Accounts are separated by role. A patient does not open another patient’s bookings, and dashboard pages require a signed-in session.
                </p>
                @unless (request()->routeIs('security'))
                    <a href="{{ route('security') }}" class="mt-5 inline-flex text-sm font-semibold text-primary no-underline hover:text-primary-hover">Read how access works</a>
                @endunless
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['title' => 'Role-based access', 'text' => 'Patient, doctor, and receptionist routes each check the signed-in role before the page loads.'],
                    ['title' => 'Account sign-in', 'text' => 'Login uses the existing session. Password reset is available if you forget the password.'],
                    ['title' => 'Your appointments', 'text' => 'A patient dashboard loads appointments for that account. Cancelling checks that the booking belongs to you.'],
                    ['title' => 'Form protection', 'text' => 'State-changing forms include a CSRF token. Uploaded profile photos are stored with the account that uploaded them.'],
                ] as $item)
                    <article class="rounded-2xl border border-line bg-page p-5">
                        <h3 class="font-semibold text-navy">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-text">{{ $item['text'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
