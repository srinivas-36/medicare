@props(['state', 'empty' => 'No reviews yet.'])

<div>
    <div class="mb-2 flex items-center justify-between gap-3">
        <h3 class="text-lg font-bold text-heading">
            All reviews
            <span class="font-semibold text-text" x-show="total > 0" x-text="'(' + total + ')'">{{ $state['total'] > 0 ? '('.$state['total'].')' : '' }}</span>
        </h3>
        <p class="inline-flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-[0.14em] text-text">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
            Live
        </p>
    </div>

    <div x-show="false">
        @forelse ($state['reviews'] as $review)
            <article class="flex gap-4 border-b border-[#E2EAF4] py-5 last:border-b-0">
                <x-ui.avatar :name="$review['name']" :photo="$review['photo']" size="md" />
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-bold text-primary">
                                {{ $review['name'] }}
                                @if (($state['mine']['id'] ?? null) === $review['id'])
                                    <span class="ml-2 rounded-full bg-[#E8F2FF] px-2 py-0.5 align-middle text-[11px] font-semibold">You</span>
                                @endif
                            </h4>
                            <p class="text-sm text-text">{{ $review['edited'] ? 'Updated '.$review['date'] : $review['date'] }}</p>
                        </div>
                        <div class="flex shrink-0 gap-0.5 text-[#E99A17]" aria-label="{{ $review['rating'] }} out of 5 stars">
                            @for ($star = 1; $star <= 5; $star++)
                                <span @class(['text-[#E2EAF4]' => $star > $review['rating']])>
                                    <x-doctors.star class="h-4 w-4" />
                                </span>
                            @endfor
                        </div>
                    </div>
                    <p class="mt-3 whitespace-pre-wrap text-[15px] leading-7 text-text">{{ $review['body'] }}</p>
                </div>
            </article>
            @empty
            <p class="py-6 text-sm leading-6 text-text">{{ $empty }}</p>
        @endforelse

        @if (($state['lastPage'] ?? 1) > 1)
            <nav class="flex items-center justify-between gap-3 border-t border-[#E2EAF4] py-4" aria-label="Review pages">
                @if ($state['prevUrl'])
                    <a href="{{ $state['prevUrl'] }}" class="rounded-full px-4 py-2 text-sm font-semibold text-heading no-underline ring-1 ring-[#E2EAF4]">Previous</a>
                @else
                    <span class="rounded-full px-4 py-2 text-sm font-semibold text-[#94A3B8] ring-1 ring-[#E2EAF4]">Previous</span>
                @endif
                <p class="text-sm text-text">Page {{ $state['page'] }} of {{ $state['lastPage'] }}</p>
                @if ($state['nextUrl'])
                    <a href="{{ $state['nextUrl'] }}" class="rounded-full px-4 py-2 text-sm font-semibold text-heading no-underline ring-1 ring-[#E2EAF4]">Next</a>
                @else
                    <span class="rounded-full px-4 py-2 text-sm font-semibold text-[#94A3B8] ring-1 ring-[#E2EAF4]">Next</span>
                @endif
            </nav>
        @endif
    </div>

    <div x-cloak aria-live="polite">
        <p x-show="reviews.length === 0" class="py-6 text-sm leading-6 text-text">{{ $empty }}</p>

        <template x-for="review in reviews" :key="review.id">
            <article class="flex gap-4 border-b border-[#E2EAF4] py-5 last:border-b-0">
                <template x-if="review.photo">
                    <img :src="review.photo" alt="" class="h-10 w-10 shrink-0 rounded-full bg-[#F7FAFF] object-contain object-center">
                </template>
                <template x-if="!review.photo">
                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#E8F2FF] text-xs font-semibold text-primary" x-text="review.initials"></span>
                </template>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h4 class="text-base font-bold text-primary">
                                <span x-text="review.name"></span>
                                <span x-show="mineId !== null && review.id === mineId" x-cloak class="ml-2 rounded-full bg-[#E8F2FF] px-2 py-0.5 align-middle text-[11px] font-semibold">You</span>
                            </h4>
                            <p class="text-sm text-text" x-text="review.edited ? 'Updated ' + review.date : review.date"></p>
                        </div>
                        <div class="flex shrink-0 gap-0.5" :aria-label="review.rating + ' out of 5 stars'">
                            <template x-for="star in starScale" :key="star">
                                <span :class="star <= review.rating ? 'text-[#E99A17]' : 'text-[#E2EAF4]'">
                                    <x-doctors.star class="h-4 w-4" />
                                </span>
                            </template>
                        </div>
                    </div>
                    <p class="mt-3 whitespace-pre-wrap text-[15px] leading-7 text-text" x-text="review.body"></p>
                </div>
            </article>
        </template>

        <nav x-show="lastPage > 1" x-cloak class="flex items-center justify-between gap-3 border-t border-[#E2EAF4] py-4" aria-label="Review pages">
            <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold text-heading ring-1 ring-[#E2EAF4] disabled:cursor-not-allowed disabled:opacity-40" @click="goTo(page - 1)" :disabled="page <= 1">
                Previous
            </button>
            <p class="text-center text-sm text-text" x-text="pageRange()">Page {{ $state['page'] ?? 1 }} of {{ $state['lastPage'] ?? 1 }}</p>
            <button type="button" class="rounded-full px-4 py-2 text-sm font-semibold text-heading ring-1 ring-[#E2EAF4] disabled:cursor-not-allowed disabled:opacity-40" @click="goTo(page + 1)" :disabled="page >= lastPage">
                Next
            </button>
        </nav>
    </div>
</div>
