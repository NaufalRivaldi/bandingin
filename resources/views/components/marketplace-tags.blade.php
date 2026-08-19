@props(['tone' => 'plain'])

@php
    $chipClass = $tone === 'boxed'
        ? 'rounded-md border border-line bg-white px-2 py-1 text-[10.5px] font-medium text-muted'
        : 'rounded-md border border-line px-[7px] py-[3px] font-sans text-[10px] font-medium text-muted';
@endphp

<span {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-[5px]']) }}>
    @foreach (['Shopee', 'Tokopedia', 'TikTok Shop'] as $store)
        <span class="{{ $chipClass }}">{{ $store }}</span>
    @endforeach
</span>
