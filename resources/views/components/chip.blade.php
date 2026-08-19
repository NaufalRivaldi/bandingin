@props([
    'active' => false,
    'tone' => 'dark',
])

@php
    $base = 'cursor-pointer rounded-full px-[14px] py-[9px] min-h-10 font-sans text-[12.5px] font-semibold transition-colors';

    $state = match (true) {
        $tone === 'solid' && $active => 'border border-brand bg-brand text-white',
        $tone === 'soft' && $active => 'border border-brand/25 bg-brand/9 text-brand',
        $active => 'border border-ink bg-ink text-white',
        $tone === 'soft' => 'border border-line bg-white text-muted hover:border-line-strong',
        default => 'border border-line bg-white text-ink hover:border-line-strong',
    };
@endphp

<button type="button" {{ $attributes->merge(['class' => "{$base} {$state}"]) }}>
    {{ $slot }}
</button>
