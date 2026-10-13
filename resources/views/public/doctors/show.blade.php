@extends('layouts.public')

@section('title', data_get($doctor ?? null, 'name', 'Doctor'))

@section('content')
    <section class="py-10 lg:py-16">
        <div class="mx-auto max-w-[1170px] px-5">
            @if (empty($doctor))
                <div class="surface px-6 py-16 text-center">
                    <h1 class="text-2xl font-bold text-heading">Doctor not found</h1>
                    <a href="{{ route('doctors.index') }}" class="btn-primary mt-6">Back to directory</a>
                </div>
            @else
                <a href="{{ route('doctors.index') }}" class="mb-6 inline-flex text-sm font-semibold text-primary no-underline hover:text-[#0D57E5]">← All doctors</a>

                <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div>
                        <x-doctors.profile :doctor="$doctor" />

                        <div class="mt-8 flex gap-2" role="tablist">
                            <button type="button" data-doctor-tab="about" class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-white" role="tab">
                                About
                            </button>
                            <button type="button" data-doctor-tab="feedback" class="rounded-full bg-white px-5 py-2 text-sm font-semibold text-heading ring-1 ring-[#E2EAF4]" role="tab">
                                Feedback
                            </button>
                        </div>

                        <div class="mt-8" data-doctor-panel="about">
                            <x-doctors.about :doctor="$doctor" />
                        </div>

                        <div class="mt-8 hidden" data-doctor-panel="feedback">
                            <x-doctors.feedback :doctor="$doctor" />
                        </div>
                    </div>

                    <x-doctors.side-panel :doctor="$doctor" />
                </div>
            @endif
        </div>
    </section>
@endsection

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-doctor-tab]').forEach((button) => {
                button.addEventListener('click', () => {
                    const selected = button.dataset.doctorTab;

                    document.querySelectorAll('[data-doctor-tab]').forEach((tab) => {
                        const active = tab.dataset.doctorTab === selected;
                        tab.classList.toggle('bg-primary', active);
                        tab.classList.toggle('text-white', active);
                        tab.classList.toggle('bg-white', !active);
                        tab.classList.toggle('text-heading', !active);
                        tab.classList.toggle('ring-1', !active);
                        tab.classList.toggle('ring-[#E2EAF4]', !active);
                    });

                    document.querySelectorAll('[data-doctor-panel]').forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.doctorPanel !== selected);
                    });
                });
            });
        </script>
    @endpush
@endonce
