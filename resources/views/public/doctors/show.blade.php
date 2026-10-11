@extends('layouts.public')

@section('content')

<main class="overflow-hidden">
    <section class="py-[35px] lg:py-[76px]">
        <div class="mx-auto max-w-[1170px] px-5">
            @if (empty($doctor))
            <div class="flex h-full w-full items-center justify-center">
                <h3 class="text-[20px] font-semibold leading-[30px] text-[#181A1E]">Doctor not found</h3>
            </div>
            @else
            <div class="grid gap-[50px] md:grid-cols-3">
                <div class="md:col-span-2">
                    <x-doctors.profile :doctor="$doctor" />

                    <div class="mt-[50px] border-b border-solid border-[#0066ff34]">
                        <button type="button" data-doctor-tab="about" class="mr-5 border-b border-solid border-[#0067FF] px-5 py-2 text-[16px] font-semibold leading-7 text-[#181A1E]">
                            About
                        </button>
                        <button type="button" data-doctor-tab="feedback" class="mr-5 border-b border-solid border-transparent px-5 py-2 text-[16px] font-semibold leading-7 text-[#181A1E]">
                            Feedback
                        </button>
                    </div>

                    <div class="mt-[50px]" data-doctor-panel="about">
                        <x-doctors.about :doctor="$doctor" />
                    </div>

                    <div class="mt-[50px] hidden" data-doctor-panel="feedback">
                        <x-doctors.feedback :doctor="$doctor" />
                    </div>
                </div>

                <div class="self-start">
                    <x-doctors.side-panel :doctor="$doctor" />
                </div>
            </div>
            @endif
        </div>
    </section>
</main>


@once
<script>
    document.querySelectorAll('[data-doctor-tab]').forEach((button) => {
        button.addEventListener('click', () => {
            const selected = button.dataset.doctorTab;

            document.querySelectorAll('[data-doctor-tab]').forEach((tab) => {
                tab.classList.toggle('border-[#0067FF]', tab.dataset.doctorTab === selected);
                tab.classList.toggle('border-transparent', tab.dataset.doctorTab !== selected);
            });

            document.querySelectorAll('[data-doctor-panel]').forEach((panel) => {
                panel.classList.toggle('hidden', panel.dataset.doctorPanel !== selected);
            });
        });
    });
</script>
@endonce

@endsection