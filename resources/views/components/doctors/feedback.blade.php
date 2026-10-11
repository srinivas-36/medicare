@props(['doctor'])

<div>
    <div class="mb-[50px]">
        <h4 class="mb-[30px] text-[20px] font-bold leading-[30px] text-[#181A1E]">
            All reviews ({{ data_get($doctor, 'totalRating', 0) }})
        </h4>

        @foreach (data_get($doctor, 'reviews') ?? [] as $review)
        <x-doctors.review-item :review="$review" />
        @endforeach
    </div>

    <div class="text-center" data-feedback-toggle>
        <button type="button" class="mt-[30px] rounded-[50px] bg-[#0067FF] px-[35px] py-[15px] font-semibold text-white">
            Give Feedback
        </button>
    </div>

    <x-doctors.feedback-form :doctor="$doctor" />
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