<section class="dash-panel p-4 sm:p-5">
    <div class="flex items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 text-[15px] font-semibold text-[#181A1E]">
            <x-ui.icon name="users" class="h-4 w-4 text-[#0067FF]" />
            Referrals
        </h2>
        @if ($canRefer ?? false)
            <button type="button" class="inline-flex items-center gap-1 text-sm font-semibold text-[#0067FF]" @click="$dispatch('open-referral')">
                <x-ui.icon name="plus" class="h-4 w-4" />
                Send Referral
            </button>
        @endif
    </div>
    @if ($referrals === [])
        <p class="mt-3 text-sm text-[#64748B]">No referrals have been sent from these visits.</p>
    @else
        <ul class="mt-3 space-y-2">
            @foreach ($referrals as $referral)
                <li class="text-sm text-[#181A1E]">
                    <a href="{{ route('doctor.referrals.show', $referral['id']) }}" class="font-semibold text-[#181A1E] no-underline hover:text-[#0067FF]">{{ $referral['date'] }} · {{ $referral['doctor'] }}</a>
                    <span class="text-[#4E545F]"> · {{ $referral['status'] }}</span>
                </li>
            @endforeach
        </ul>
    @endif
</section>
