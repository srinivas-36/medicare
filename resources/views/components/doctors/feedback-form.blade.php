@props(['doctor'])

@php
$doctorId = data_get($doctor, 'id', data_get($doctor, '_id'));
$action = $doctorId && \Illuminate\Support\Facades\Route::has('doctors.reviews.store')
? route('doctors.reviews.store', $doctorId)
: '#';
@endphp

<form action="{{ $action }}" method="POST" class="hidden" data-feedback-form>
    @csrf
    <input type="hidden" name="rating" value="0" data-rating-input>

    <div>
        <h3 class="mb-4 mt-0 text-[16px] font-semibold leading-6 text-[#0B1F3A]">
            How would rate the Overall Experience
        </h3>
        <div data-rating-stars>
            @for ($index = 1; $index <= 5; $index++)
                <button type="button" data-star="{{ $index }}" class="cursor-pointer border-none bg-transparent text-[22px] text-gray-400 outline-none">
                <x-doctors.star />
                </button>
                @endfor
        </div>
    </div>

    <div class="mt-[30px]">
        <h3 class="mb-4 mt-0 text-[16px] font-semibold leading-6 text-[#0B1F3A]">
            Share your Feedback or suggestions *
        </h3>
        <textarea name="reviewText" rows="5" placeholder="Write your message" class="w-full rounded-md border border-solid border-[#E2EAF4] px-4 py-3 outline-[#1769FF] focus:outline"></textarea>
    </div>

    <button type="submit" class="btn-primary mt-6">
        Submit Feedback
    </button>
</form>

@once
<script>
    document.querySelectorAll('[data-feedback-form]').forEach((form) => {
        const ratingInput = form.querySelector('[data-rating-input]');
        const starButtons = [...form.querySelectorAll('[data-star]')];
        let rating = 0;
        let hover = 0;

        const paintStars = () => {
            const active = (rating && hover) || hover;

            starButtons.forEach((star) => {
                const selected = Number(star.dataset.star) <= active;
                star.classList.toggle('text-[#E99A17]', selected);
                star.classList.toggle('text-gray-400', !selected);
            });
        };

        starButtons.forEach((star) => {
            const value = Number(star.dataset.star);

            star.addEventListener('click', () => {
                rating = value;
                hover = value;
                ratingInput.value = rating;
                paintStars();
            });

            star.addEventListener('mouseenter', () => {
                hover = value;
                paintStars();
            });

            star.addEventListener('mouseleave', () => {
                hover = rating;
                paintStars();
            });

            star.addEventListener('dblclick', () => {
                rating = 0;
                hover = 0;
                ratingInput.value = 0;
                paintStars();
            });
        });
    });
</script>
@endonce