@props([
    'title',
    'lead' => null,
    'items' => [],
    'width' => 'wide',
])

@php
    $maxWidth = match ($width) {
        'narrow' => 'max-w-[820px]',
        'medium' => 'max-w-[1140px]',
        default => 'max-w-[1240px]',
    };
@endphp

<div class="page-wash border-b border-line">
    <div class="{{ $maxWidth }} mx-auto px-[clamp(16px,2.4vw,24px)] pt-[clamp(20px,3vw,32px)] pb-[clamp(24px,3vw,40px)]">
        <x-breadcrumb :items="$items" />

        <h1 class="anim-fade-up mt-3.5 font-display text-[clamp(28px,4.4vw,50px)] leading-[1.02] font-extrabold tracking-[-.035em]">
            {{ $title }}
        </h1>

        @if ($lead)
            <p class="mt-3 max-w-[620px] text-[14.5px] leading-relaxed text-muted">{{ $lead }}</p>
        @endif

        {{ $slot }}
    </div>
</div>
