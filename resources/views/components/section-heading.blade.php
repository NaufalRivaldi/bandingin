@props([
    'eyebrow' => null,
    'title',
    'size' => 'md',
    'lead' => null,
])

@php
    $titleSize = match ($size) {
        'lg' => 'text-[clamp(28px,3.6vw,44px)] leading-[1.05]',
        'sm' => 'text-[clamp(21px,2.5vw,29px)]',
        default => 'text-[clamp(24px,3vw,36px)]',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-end justify-between gap-4']) }}>
    <div>
        @if ($eyebrow)
            <x-eyebrow>{{ $eyebrow }}</x-eyebrow>
        @endif

        <h2 class="mt-2.5 font-display {{ $titleSize }} font-extrabold tracking-[-.028em]">{{ $title }}</h2>
    </div>

    @if ($lead)
        <p class="max-w-[380px] text-[14.5px] leading-relaxed text-muted">{{ $lead }}</p>
    @endif

    {{ $slot }}
</div>
