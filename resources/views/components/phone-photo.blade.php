@props([
    'phone',
    'src' => null,
    'mockClass' => '',
    'imageClass' => 'max-h-[82%] max-w-[76%] object-contain',
    'radius' => 'rounded-[9px]',
    'inset' => 'inset-[3px]',
    'faceRadius' => 'rounded-md',
])

@php
    /** @var \App\Models\Phone $phone */
    $src ??= $phone->primaryImage();
@endphp

{{-- Photos are optional and hotlinked; phones without one keep the gradient slab. --}}
@if (filled($src))
    <img
        src="{{ $src }}"
        alt="{{ $phone->name }}"
        loading="lazy"
        decoding="async"
        class="{{ $imageClass }}"
    >
@else
    <x-device-mock
        :class="$mockClass"
        :radius="$radius"
        :inset="$inset"
        :face-radius="$faceRadius"
    />
@endif
