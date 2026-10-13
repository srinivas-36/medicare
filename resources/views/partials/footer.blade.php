{{--
    Footer links use named routes only.
    There is no public phone number or street address stored in the app, so none is shown.
--}}
<footer class="bg-navy text-white">
    <div class="page grid gap-10 py-14 sm:grid-cols-2 lg:grid-cols-4">
        <div>
            <span class="inline-flex rounded-2xl bg-white px-3 py-2">
                <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="block h-9 w-auto">
            </span>
            <p class="mt-4 max-w-xs text-sm leading-6 text-white/70">
                Search approved doctors, see the consultation fee, and request a visit from your account.
            </p>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-white">Explore</h2>
            <ul class="mt-4 space-y-3">
                <li><a href="{{ route('home') }}" class="text-sm text-white/75 no-underline hover:text-white">Home</a></li>
                <li><a href="{{ route('doctors.index') }}" class="text-sm text-white/75 no-underline hover:text-white">Find a doctor</a></li>
                <li><a href="{{ route('services.index') }}" class="text-sm text-white/75 no-underline hover:text-white">Services</a></li>
                <li><a href="{{ route('about') }}" class="text-sm text-white/75 no-underline hover:text-white">About</a></li>
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-white">Patients and doctors</h2>
            <ul class="mt-4 space-y-3">
                <li><a href="{{ route('faq') }}" class="text-sm text-white/75 no-underline hover:text-white">FAQ</a></li>
                <li><a href="{{ route('security') }}" class="text-sm text-white/75 no-underline hover:text-white">Privacy and security</a></li>
                <li><a href="{{ route('contact') }}" class="text-sm text-white/75 no-underline hover:text-white">Contact</a></li>
                @auth
                    <li><a href="{{ route('dashboard') }}" class="text-sm text-white/75 no-underline hover:text-white">Your dashboard</a></li>
                @else
                    <li><a href="{{ route('register') }}" class="text-sm text-white/75 no-underline hover:text-white">Create an account</a></li>
                @endauth
            </ul>
        </div>

        <div>
            <h2 class="text-sm font-semibold text-white">Account</h2>
            <ul class="mt-4 space-y-3">
                @guest
                    <li><a href="{{ route('login') }}" class="text-sm text-white/75 no-underline hover:text-white">Log in</a></li>
                    <li><a href="{{ route('register') }}" class="text-sm text-white/75 no-underline hover:text-white">Register</a></li>
                @endguest
                @auth
                    <li><a href="{{ route('dashboard') }}" class="text-sm text-white/75 no-underline hover:text-white">Dashboard</a></li>
                @endauth
                <li><a href="{{ route('password.request') }}" class="text-sm text-white/75 no-underline hover:text-white">Reset password</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="page flex flex-col gap-2 py-5 text-sm text-white/55 sm:flex-row sm:items-center sm:justify-between">
            <p>© {{ date('Y') }} Medicare. All rights reserved.</p>
            <a href="{{ route('security') }}" class="text-white/70 no-underline hover:text-white">How account access works</a>
        </div>
    </div>
</footer>
