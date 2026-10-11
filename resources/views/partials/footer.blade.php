<footer class="bg-white pt-[60px] pb-[70px]">
    <div class="mx-auto flex w-full max-w-[1440px] flex-col flex-wrap justify-between gap-[30px] px-5 md:flex-row">
        <div>
            <img src="{{ asset('images/logo.png') }}" alt="Medicare" class="block h-12 w-auto">

            <p class="mt-4 max-w-[320px] text-base font-normal leading-7 text-[#4E545F]">
                Copyright © {{ date('Y') }} developed by Srinivas Balam all rights Reserved
            </p>

            <div class="mt-4 flex items-center gap-3">
                <a href="#" aria-label="YouTube" class="flex h-9 w-9 items-center justify-center rounded-full border border-[#181A1E] text-[#181A1E] no-underline hover:border-transparent hover:bg-[#0067FF] hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23 12.2s0-3.2-.4-4.6c-.2-.9-.9-1.6-1.8-1.8C19.2 5.4 12 5.4 12 5.4s-7.2 0-8.8.4c-.9.2-1.6.9-1.8 1.8C1 9 1 12.2 1 12.2s0 3.2.4 4.6c.2.9.9 1.6 1.8 1.8 1.6.4 8.8.4 8.8.4s7.2 0 8.8-.4c.9-.2 1.6-.9 1.8-1.8.4-1.4.4-4.6.4-4.6zM9.8 15.5v-6.6l6.2 3.3-6.2 3.3z" />
                    </svg>
                </a>
                <a href="#" aria-label="Instagram" class="flex h-9 w-9 items-center justify-center rounded-full border border-[#181A1E] text-[#181A1E] no-underline hover:border-transparent hover:bg-[#0067FF] hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zm10 2H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2zm-5 3.2A3.8 3.8 0 1 1 8.2 12 3.8 3.8 0 0 1 12 8.2zm0 1.6A2.2 2.2 0 1 0 14.2 12 2.2 2.2 0 0 0 12 9.8zM17.4 6.9a.9.9 0 1 1-.9.9.9.9 0 0 1 .9-.9z" />
                    </svg>
                </a>
                <!-- <a href="https://github.com/srinivas-36" target="_blank" rel="noopener" aria-label="GitHub" class="flex h-9 w-9 items-center justify-center rounded-full border border-[#181A1E] text-[#181A1E] no-underline hover:border-transparent hover:bg-[#0067FF] hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2a10 10 0 0 0-3.2 19.5c.5.1.7-.2.7-.5v-1.7c-2.8.6-3.4-1.2-3.4-1.2-.4-1.1-1-1.4-1-1.4-.9-.6.1-.6.1-.6 1 .1 1.5 1 1.5 1 .9 1.6 2.4 1.1 3 .9.1-.7.4-1.1.6-1.4-2.2-.3-4.6-1.1-4.6-5a3.9 3.9 0 0 1 1-2.7 3.6 3.6 0 0 1 .1-2.6s.8-.3 2.8 1a9.6 9.6 0 0 1 5 0c2-1.3 2.8-1 2.8-1a3.6 3.6 0 0 1 .1 2.6 3.9 3.9 0 0 1 1 2.7c0 3.9-2.4 4.7-4.6 5 .4.3.7.9.7 1.9v2.8c0 .3.2.6.7.5A10 10 0 0 0 12 2z" />
                    </svg>
                </a> -->
                <a href="https://www.linkedin.com/in/srinivas-balam-a63624254/" target="_blank" rel="noopener" aria-label="LinkedIn" class="flex h-9 w-9 items-center justify-center rounded-full border border-[#181A1E] text-[#181A1E] no-underline hover:border-transparent hover:bg-[#0067FF] hover:text-white">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6.5 9H4V20h2.5V9zM5.2 4A1.6 1.6 0 1 0 5.2 7.2 1.6 1.6 0 0 0 5.2 4zM20 20h-2.5v-5.6c0-1.6-.6-2.6-2-2.6a2.2 2.2 0 0 0-2 1.5c-.1.3-.1.7-.1 1.1V20H11V9h2.4v1.5a3.4 3.4 0 0 1 3.1-1.7c2.2 0 3.5 1.4 3.5 4.5V20z" />
                    </svg>
                </a>
            </div>
        </div>

        <div>
            <h2 class="mb-6 text-xl font-bold leading-[30px] text-[#181A1E]">Quick Links</h2>
            <ul class="m-0 list-none p-0">
                <li class="mb-4"><a href="{{ route('home') }}" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Home</a></li>
                <li class="mb-4"><a href="{{ route('about') }}" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">About Us</a></li>
                <li class="mb-4"><a href="{{ route('services.index') }}" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Services</a></li>
                <!-- <li class="mb-4"><a href="#" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Blog</a></li> -->
            </ul>
        </div>

        <div>
            <h2 class="mb-6 text-xl font-bold leading-[30px] text-[#181A1E]">I want to :</h2>
            <ul class="m-0 list-none p-0">
                <li class="mb-4"><a href="{{ route('doctors.index') }}" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Find a Doctor</a></li>
                <li class="mb-4"><a href="{{ route('doctors.index') }}" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Request an Appointment</a></li>
                <li class="mb-4"><a href="{{ route('contact') }}" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Find a Location</a></li>
                <li class="mb-4"><a href="{{ route('contact') }}" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Get an Opinion</a></li>
            </ul>
        </div>

        <div>
            <h2 class="mb-6 text-xl font-bold leading-[30px] text-[#181A1E]">Support</h2>
            <ul class="m-0 list-none p-0">
                <li class="mb-4"><a href="#" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Donate</a></li>
                <li class="mb-4"><a href="{{ route('contact') }}" class="text-base font-normal leading-7 text-[#4E545F] no-underline hover:text-[#0067FF]">Contact Us</a></li>
            </ul>
        </div>
    </div>
</footer>