@props([
    'label',
    'value',
    'max' => 100,
    'suffix' => '',
])

@php
    $percentage = max(0, min(100, ($value / $max) * 100));
@endphp

<div {{ $attributes }}>
    <div class="flex items-baseline justify-between gap-2.5">
        <span class="text-sm font-medium">{{ $label }}</span>
        <span class="font-mono text-sm font-semibold">{{ $value }}{{ $suffix }}</span>
    </div>
    <span class="mt-2 block h-2 overflow-hidden rounded-full bg-line">
        <span
            @class([
                'block h-full rounded-full transition-[width] duration-400',
                'bg-win' => $value >= 85 && $max === 100,
                'bg-brand' => ! ($value >= 85 && $max === 100),
            ])
            style="width: {{ $percentage }}%"
        ></span>
    </span>
    {{ $slot }}
</div>
