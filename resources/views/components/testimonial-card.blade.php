<article class="surface w-[380px] flex-shrink-0 px-6 py-8">
    <div class="flex items-center gap-[13px]">
        <img
            src="{{ asset($image) }}"
            alt="{{ $name }}"
            class="h-16 w-16 rounded-full object-cover">

        <div>
            <h4 class="text-[18px] font-semibold text-[#0B1F3A]">
                {{ $name }}
            </h4>

            <div class="flex items-center gap-[2px] text-[#E99A17]">
                @for($i = 0; $i < $rating; $i++)
                    <svg class="h-5 w-[18px]" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M9.05 2.93c.3-.92 1.6-.92 1.9 0l1.07 3.29a1 1 0 0 0 .95.69h3.46c.97 0 1.37 1.24.59 1.81l-2.8 2.03a1 1 0 0 0-.36 1.12l1.07 3.29c.3.92-.75 1.69-1.54 1.12l-2.8-2.03a1 1 0 0 0-1.18 0l-2.8 2.03c-.79.57-1.84-.2-1.54-1.12l1.07-3.29a1 1 0 0 0-.36-1.12L2.98 8.72c-.78-.57-.38-1.81.59-1.81h3.46a1 1 0 0 0 .95-.69l1.07-3.29z" />
                    </svg>
                    @endfor
            </div>
        </div>
    </div>

    <p class="mt-4 text-base leading-7 text-[#53647D]">
        {{ $message }}
    </p>
</article>