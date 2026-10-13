@props(['value'])

<label {{ $attributes->merge(['class' => 'mb-2 block text-sm font-semibold text-heading']) }}>
    {{ $value ?? $slot }}
</label>
