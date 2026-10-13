@php
    $healthLabel = function ($record, $yesNo = false) {
        $known = filter_var(data_get($record, 'known', true), FILTER_VALIDATE_BOOLEAN);
        $value = data_get($record, 'value', '');

        if (! $known) {
            return 'Not known';
        }

        if ($value === null || $value === '') {
            return null;
        }

        return $yesNo ? ucfirst((string) $value) : $value;
    };

    $notes = $healthNotes ?? [];
@endphp

<div class="grid gap-5 sm:grid-cols-3">
    @include('dashboard.partials.detail', ['label' => 'Blood pressure', 'value' => $healthLabel(data_get($notes, 'bp', []))])
    @include('dashboard.partials.detail', ['label' => 'Diabetic', 'value' => $healthLabel(data_get($notes, 'diabetic', []), true)])
    @include('dashboard.partials.detail', ['label' => 'Hyperthyroidism', 'value' => $healthLabel(data_get($notes, 'hyperthyroidism', []), true)])
</div>
