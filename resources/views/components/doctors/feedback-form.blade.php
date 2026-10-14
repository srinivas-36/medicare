@props(['state'])

@php
    $selected = (int) old('rating', $state['mine']['rating'] ?? 0);
    $draft = old('reviewText', $state['mine']['body'] ?? '');
@endphp

<form action="{{ $state['sendUrl'] }}" method="POST" data-no-lock="true" @submit.prevent="send">
    @csrf

    <div>
        <h3 class="text-base font-semibold text-heading">How would you rate the overall experience?</h3>
        <div class="mt-3 flex gap-1" role="radiogroup" aria-label="Overall experience">
            @for ($index = 1; $index <= 5; $index++)
                <label
                    class="cursor-pointer rounded text-[22px] text-[#D5DDE8] focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-primary"
                    :style="{{ $index }} <= (hover || rating) ? 'color: #E99A17' : ''"
                >
                    <input
                        type="radio"
                        name="rating"
                        value="{{ $index }}"
                        class="sr-only"
                        x-model.number="rating"
                        @checked($selected === $index)
                        @mouseenter="hover = {{ $index }}"
                        @mouseleave="hover = Number(rating) || 0"
                        @change="hover = Number(rating) || 0"
                        aria-label="{{ $index }} {{ $index === 1 ? 'star' : 'stars' }}"
                        @if ($index === 1) required @endif
                    >
                    <x-doctors.star />
                </label>
            @endfor
        </div>
        @error('rating')
            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-6">
        <label for="review-text" class="text-base font-semibold text-heading">Share your feedback or suggestions</label>
        <textarea
            id="review-text"
            name="reviewText"
            rows="5"
            maxlength="1000"
            required
            x-model="body"
            placeholder="How was the visit? What should other patients know?"
            class="mt-3 w-full rounded-md border border-solid border-[#E2EAF4] px-4 py-3 text-heading outline-[#1769FF] focus:outline"
        >{{ $draft }}</textarea>
        @error('reviewText')
            <p class="mt-2 text-sm text-danger">{{ $message }}</p>
        @enderror
    </div>

    <p class="mt-3 text-sm text-text">Your name is shown with the review. It appears on this page as soon as you submit, and you can update it later.</p>

    <p x-show="error" x-cloak class="mt-3 text-sm text-danger" x-text="error"></p>
    <p x-show="hint" x-cloak class="mt-3 text-sm text-[#B45309]" x-text="hint"></p>

    <button type="submit" class="btn-primary mt-6 disabled:cursor-not-allowed disabled:opacity-60" :disabled="sending">
        <span x-text="sending ? 'Saving...' : (canWrite ? (mineId ? 'Update feedback' : 'Submit feedback') : 'Sign in to submit')">
            {{ $state['canWrite'] ? (($state['mine']['id'] ?? null) ? 'Update feedback' : 'Submit feedback') : 'Sign in to submit' }}
        </span>
    </button>
</form>
