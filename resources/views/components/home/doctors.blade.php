{{--
    Featured doctors. $featuredDoctors is loaded in routes/web.php through PublicCatalog.
    The card itself lives in components/doctors/card.blade.php.
--}}
<section class="bg-white py-16 lg:py-20">
    <div class="page">
        <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-end">
            <x-section-heading
                align="left"
                title="Meet Our Top Doctors"
                subtitle="Explore qualified healthcare professionals and find a doctor who fits your needs."
            />
            <a href="{{ route('doctors.index') }}" class="shrink-0 text-sm font-semibold text-primary no-underline hover:text-primary-hover">
                View all doctors
            </a>
        </div>

        @if (collect($featuredDoctors ?? [])->isEmpty())
            <div class="mt-10">
                <x-ui.empty-state
                    title="No doctors to show yet"
                    text="Approved doctor profiles will appear here. You can still open the directory."
                >
                    <a href="{{ route('doctors.index') }}" class="btn-primary">View all doctors</a>
                </x-ui.empty-state>
            </div>
        @else
            <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredDoctors as $doctor)
                    <x-doctors.card :doctor="$doctor" />
                @endforeach
            </div>
        @endif
    </div>
</section>
