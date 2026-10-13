{{--
    Patient reviews are not stored in this application.
    This partial stays so older includes do not show invented quotes.
--}}
<section class="py-16">
    <div class="page">
        <x-ui.empty-state
            title="Patient reviews are not listed yet"
            text="Medicare does not collect public reviews. Use a doctor profile to see the fee, specialty, and open times."
        >
            <a href="{{ route('doctors.index') }}" class="btn-primary">Find a doctor</a>
        </x-ui.empty-state>
    </div>
</section>
