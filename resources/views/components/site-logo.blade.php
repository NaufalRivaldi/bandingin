@props([
    'size' => 28,
    'wordmark' => true,
])

<span {{ $attributes->class('inline-flex items-center gap-[9px]') }}>
    <svg
        viewBox="0 0 32 32"
        width="{{ $size }}"
        height="{{ $size }}"
        role="img"
        aria-label="Bandingin.id"
        class="flex-none"
    >
        <rect width="32" height="32" rx="9" class="fill-brand" />
        <g fill="none" stroke="#fff" stroke-width="3.1" stroke-linecap="round" stroke-linejoin="round">
            <path d="M6.6 10.4 10.2 21.6 13.8 10.4" />
            <path d="M25.4 11.6c0-1.7-1.8-2.4-3.5-2c-2.5.6-2.7 4-.1 4.8c3.4 1.1 3.8 5 .8 6.8c-2 1.2-4.4.7-5-1" />
        </g>
    </svg>

    @if ($wordmark)
        <span class="font-display text-[clamp(15.5px,1.6vw,17px)] font-extrabold tracking-[-.03em] whitespace-nowrap">Bandingin<span class="text-brand">.id</span></span>
    @endif
</span>
