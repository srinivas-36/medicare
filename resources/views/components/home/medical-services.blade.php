{{--
    Specialty cards come from $specialties, passed by the home and services routes.
    A photo is used only when the specialty name matches an image already in public/images.
--}}
@php
    $specialties = collect($specialties ?? []);

    $photoFor = function (string $specialty) {
        $name = strtolower($specialty);
        $photos = [
            'cancer' => 'images/cancer-care.jpg',
            'heart' => 'images/Heart-andVascular.jpg',
            'cardio' => 'images/Heart-andVascular.jpg',
            'vascular' => 'images/Heart-andVascular.jpg',
            'neuro' => 'images/Neurology.jpg',
            'mental' => 'images/mental-health.jpg',
            'psych' => 'images/mental-health.jpg',
            'labor' => 'images/labor-and-delivery.jpeg',
            'obstet' => 'images/labor-and-delivery.jpeg',
            'gynec' => 'images/labor-and-delivery.jpeg',
            'burn' => 'images/burn.webp',
        ];

        foreach ($photos as $needle => $path) {
            if (str_contains($name, $needle)) {
                return $path;
            }
        }

        return null;
    };
@endphp

@php($showHeading = $showHeading ?? true)

<section class="py-16 lg:py-20">
    <div class="page">
        @if ($showHeading)
            <x-section-heading
                title="Comprehensive Healthcare Services"
                subtitle="From routine checkups to specialized treatments, find care that fits your needs."
            />
        @endif

        @if ($specialties->isEmpty())
            <div class="mt-10">
                <x-ui.empty-state
                    title="Specialties will appear here"
                    text="When an approved doctor adds a specialty, it shows up as a way to search the directory."
                >
                    <a href="{{ route('doctors.index') }}" class="btn-primary">Browse doctors</a>
                </x-ui.empty-state>
            </div>
        @else
            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($specialties->take(8) as $specialty)
                    @php($photo = $photoFor($specialty))
                    <article class="card flex flex-col overflow-hidden !p-0">
                        @if ($photo)
                            <img src="{{ asset($photo) }}" alt="" class="h-40 w-full object-cover" loading="lazy" width="640" height="320">
                        @else
                            <div class="flex h-40 items-center justify-center bg-light">
                                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mint text-primary">
                                    <x-ui.icon name="stethoscope" class="h-7 w-7" />
                                </span>
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="text-lg font-semibold text-navy">{{ $specialty }}</h3>
                            <p class="mt-2 flex-1 text-sm leading-6 text-text">See approved doctors who list this specialty.</p>
                            <a href="{{ route('doctors.index', ['query' => $specialty]) }}" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-primary no-underline hover:text-primary-hover">
                                View doctors
                                <x-ui.icon name="arrow" class="h-4 w-4" />
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</section>
