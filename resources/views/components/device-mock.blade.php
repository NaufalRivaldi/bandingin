@props([
    'radius' => 'rounded-[9px]',
    'inset' => 'inset-[3px]',
    'faceRadius' => 'rounded-md',
])

{{-- The design never ships product photography; every phone is this gradient slab. --}}
<span {{ $attributes->merge(['class' => "device-shell relative block {$radius}"]) }}>
    <span class="device-face absolute {{ $inset }} {{ $faceRadius }}"></span>
</span>
