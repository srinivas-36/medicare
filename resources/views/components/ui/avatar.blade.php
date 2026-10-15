{{--
    Person mark. Pass a photo URL when one exists; otherwise initials are drawn from the name.
--}}
@props(['name' => '', 'photo' => null, 'size' => 'md', 'tone' => 'blue'])

@php
    $sizes = [
        'sm' => 'h-8 w-8 text-[11px]',
        'md' => 'h-9 w-9 text-xs',
        'lg' => 'h-11 w-11 text-sm',
        'xl' => 'h-14 w-14 text-base',
        '2xl' => 'h-[72px] w-[72px] text-xl',
        '3xl' => 'h-20 w-20 text-2xl',
    ];
    $tones = [
        'blue' => 'bg-[#E8F2FF] text-[#0067FF]',
        'violet' => 'bg-[#7C3AED] text-white',
        'lavender' => 'bg-[#EDE9FE] text-[#5B21B6]',
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $toneClass = $tones[$tone] ?? $tones['blue'];
    $initials = \App\Support\AppointmentPage::initials((string) $name, 'M');
    $photoUrl = is_string($photo) && $photo !== ''
        ? (str_starts_with($photo, 'http') || str_starts_with($photo, '/') ? $photo : \App\Support\AppointmentPage::photoUrl($photo))
        : null;
@endphp

@if ($photoUrl)
    <img src="{{ $photoUrl }}" alt="" {{ $attributes->merge(['class' => $sizeClass.' shrink-0 rounded-full bg-[#F7FAFF] object-contain object-center']) }}>
@else
    <span {{ $attributes->merge(['class' => $sizeClass.' inline-flex shrink-0 items-center justify-center rounded-full font-semibold '.$toneClass]) }} aria-hidden="true">{{ $initials }}</span>
@endif
