@props(['item'])

@php
    /** @var \App\Models\NewsItem $item */
@endphp

<a
    href="{{ $item->url }}"
    target="_blank"
    rel="noopener nofollow"
    {{ $attributes->merge(['class' => 'block w-full text-left text-ink transition-transform duration-200 hover:-translate-y-[3px] hover:text-ink']) }}
>
    @if ($item->image_url)
        <img
            src="{{ $item->image_url }}"
            alt="{{ $item->title }}"
            loading="lazy"
            referrerpolicy="no-referrer"
            class="aspect-16/10 w-full rounded-2xl object-cover"
        >
    @else
        <span class="photo-slot relative block aspect-16/10 rounded-2xl"></span>
    @endif

    <span class="mt-3.5 flex items-center gap-[9px] font-mono text-[10.5px] tracking-[.13em] uppercase">
        <span class="text-brand">{{ $item->channel }}</span>
        <span class="text-[#c3cee3]">·</span>
        <span class="text-muted">{{ $item->dateShort() }}</span>
    </span>

    <span class="mt-[9px] block font-display text-[17.5px] leading-[1.28] font-extrabold tracking-[-.02em] text-ink">{{ $item->title }}</span>

    <span class="mt-[9px] block text-sm leading-relaxed text-muted">{{ $item->excerpt }}</span>

    <span class="mt-3 flex items-center gap-1.5 font-mono text-[11px] text-muted">
        <span>{{ $item->source }}</span>
        <span aria-hidden="true">↗</span>
    </span>
</a>
