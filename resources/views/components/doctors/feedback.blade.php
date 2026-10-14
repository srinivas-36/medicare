@props(['state'])

<div id="feedback">
    <div class="surface px-6 sm:px-8">
        <x-doctors.review-feed
            :state="$state"
            empty="No reviews yet. A new review shows up here as soon as a patient submits it."
        />
    </div>

    @if ($state['showForm'])
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p x-show="saved" x-cloak class="text-sm font-medium text-success" x-text="saved"></p>
                @if (session('status'))
                    <p class="text-sm font-medium text-success">{{ session('status') }}</p>
                @endif
            </div>
            <button type="button" class="btn-primary" @click="openForm()">
                <span x-text="mineId ? 'Update your review' : 'Write a review'">
                    {{ ($state['mine']['id'] ?? null) ? 'Update your review' : 'Write a review' }}
                </span>
            </button>
        </div>

        <div
            x-show="formOpen"
            x-cloak
            class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-6"
            role="dialog"
            aria-modal="true"
            aria-labelledby="review-dialog-title"
            @keydown.escape.window="closeForm()"
        >
            <div class="absolute inset-0 bg-[#0B1F3A]/50" @click="closeForm()"></div>

            <div
                x-ref="reviewDialog"
                tabindex="-1"
                class="relative flex max-h-[100dvh] w-full max-w-lg flex-col overflow-hidden bg-white shadow-[0_24px_80px_rgba(11,31,58,0.28)] outline-none sm:max-h-[min(720px,92dvh)] sm:rounded-2xl"
            >
                <div class="flex items-start justify-between gap-4 border-b border-[#E2EAF4] px-5 py-4 sm:px-6">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-primary">Feedback</p>
                        <h2 id="review-dialog-title" class="mt-1 text-xl font-bold text-heading" x-text="mineId ? 'Update your review' : 'Write a review'">
                            {{ ($state['mine']['id'] ?? null) ? 'Update your review' : 'Write a review' }}
                        </h2>
                    </div>
                    <button type="button" class="rounded-full px-2 text-2xl leading-none text-text hover:text-heading" @click="closeForm()" aria-label="Close">
                        ×
                    </button>
                </div>

                <div class="overflow-y-auto px-5 py-5 sm:px-6">
                    <x-doctors.feedback-form :state="$state" />
                </div>
            </div>
        </div>
    @else
        <p class="mt-6 text-sm leading-6 text-text">Patients write reviews on this profile. New reviews show up here as they are submitted.</p>
    @endif
</div>
