@props(['doctor'])

<div>
    @if ((int) data_get($doctor, 'totalRating', 0) > 0)
        <div class="mb-[50px]">
            <h4 class="mb-[30px] text-[20px] font-bold leading-[30px] text-[#0B1F3A]">
                All reviews ({{ data_get($doctor, 'totalRating', 0) }})
            </h4>

            @foreach (data_get($doctor, 'reviews') ?? [] as $review)
                <x-doctors.review-item :review="$review" />
            @endforeach
        </div>
    @endif

    @if (\Illuminate\Support\Facades\Route::has('doctors.reviews.store'))
        <div class="text-center" data-feedback-toggle>
            <button type="button" class="btn-primary mt-6">
                Give Feedback
            </button>
        </div>

        <x-doctors.feedback-form :doctor="$doctor" />
    @else
        <p class="text-sm leading-6 text-text">Public reviews are not collected on this site yet.</p>
    @endif
</div>

@once
<script>
    document.querySelectorAll('[data-feedback-toggle]').forEach((toggle) => {
        toggle.querySelector('button')?.addEventListener('click', () => {
            toggle.classList.add('hidden');
            toggle.nextElementSibling?.classList.remove('hidden');
        });
    });
</script>
@endonce