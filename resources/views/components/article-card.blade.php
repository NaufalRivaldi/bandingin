@props([
    'article',
    'showExcerpt' => true,
])

@php
    /** @var \App\Models\Article $article */
@endphp

<a
    href="{{ route('berita.show', $article->slug) }}"
    {{ $attributes->merge(['class' => 'block w-full text-left text-ink transition-transform duration-200 hover:-translate-y-[3px] hover:text-ink']) }}
>
    @if ($article->image_url)
        <img
            src="{{ $article->image_url }}"
            alt="{{ $article->title }}"
            loading="lazy"
            referrerpolicy="no-referrer"
            class="aspect-16/10 w-full rounded-2xl object-cover"
        >
    @else
        <span class="photo-slot relative block aspect-16/10 rounded-2xl"></span>
    @endif

    <span class="mt-3.5 flex items-center gap-[9px] font-mono text-[10.5px] tracking-[.13em] uppercase">
        <span class="text-brand">{{ $article->category->value }}</span>
        <span class="text-[#c3cee3]">·</span>
        <span class="text-muted">{{ $article->dateShort() }}</span>
    </span>

    <span class="mt-[9px] block font-display text-[17.5px] leading-[1.28] font-extrabold tracking-[-.02em] text-ink">{{ $article->title }}</span>

    @if ($showExcerpt)
        <span class="mt-[9px] block text-sm leading-relaxed text-muted">{{ $article->excerpt }}</span>
        <span class="mt-3 block font-mono text-[11px] text-muted">{{ $article->read_minutes }} menit baca</span>
    @endif
</a>
