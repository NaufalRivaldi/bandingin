@props([
    'variant' => 'bar',
    'active' => '',
])

@php
    $pill = $variant === 'pill';

    $links = [
        'katalog' => ['label' => 'Katalog HP', 'route' => 'katalog'],
        'bandingkan' => ['label' => 'Bandingkan', 'route' => 'bandingkan'],
        'merek' => ['label' => 'Merek', 'route' => 'merek'],
        'berita' => ['label' => 'Berita', 'route' => 'berita'],
    ];

    $ctaHref = $pill ? '#bandingkan' : route('bandingkan');
@endphp

<div
    x-data="{ open: false }"
    @resize.window="if (window.innerWidth > 820) open = false"
    @class([
        'relative z-3' => $pill,
        'sticky top-0 z-30 border-b border-line bg-white/92 backdrop-blur-[14px]' => ! $pill,
    ])
>
    <nav @class([
        'mx-auto flex items-center gap-[clamp(10px,1.6vw,26px)] rounded-full border border-white/85 bg-white/72 px-4 py-2 pr-2.5 shadow-[0_10px_34px_rgba(12,20,36,.07)] backdrop-blur-[16px] justify-between nav:w-fit nav:justify-center' => $pill,
        'mx-auto flex max-w-[1140px] items-center justify-between gap-3.5 px-[clamp(16px,2.4vw,24px)] py-[clamp(11px,1.4vw,14px)]' => ! $pill,
    ])>
        <a href="{{ route('home') }}" class="flex flex-none items-center gap-[9px] text-ink hover:text-ink">
            <x-site-logo />
        </a>

        <div class="hidden items-center gap-[clamp(14px,1.8vw,26px)] text-[14.5px] font-medium nav:flex">
            @foreach ($links as $key => $link)
                <a
                    href="{{ route($link['route']) }}"
                    @class([
                        'font-semibold text-brand' => $active === $key,
                        'text-ink hover:text-brand' => $active !== $key,
                    ])
                >{{ $link['label'] }}</a>
            @endforeach
        </div>

        <a
            href="{{ $ctaHref }}"
            @class([
                'hidden flex-none items-center gap-[7px] rounded-full bg-brand font-semibold text-white hover:bg-brand-dark hover:text-white nav:inline-flex',
                'px-[18px] py-[11px] text-[14.5px] shadow-[0_6px_18px_rgba(37,99,235,.28)]' => $pill,
                'px-[17px] py-2.5 text-sm' => ! $pill,
            ])
        >
            Mulai Bandingkan <span class="font-mono text-xs">↗</span>
        </a>

        <button
            type="button"
            x-on:click="open = ! open"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-label="open ? 'Tutup menu' : 'Buka menu'"
            aria-label="Buka menu"
            class="grid size-11 flex-none cursor-pointer place-items-center content-center gap-1 rounded-[13px] border border-line bg-white p-0 nav:hidden"
        >
            <span class="block h-[1.5px] w-[18px] rounded-sm bg-ink transition-transform duration-200" :class="open && 'translate-y-[5.5px] rotate-45'"></span>
            <span class="block h-[1.5px] w-[18px] rounded-sm bg-ink transition-opacity duration-200" :class="open && 'opacity-0'"></span>
            <span class="block h-[1.5px] w-[18px] rounded-sm bg-ink transition-transform duration-200" :class="open && '-translate-y-[5.5px] -rotate-45'"></span>
        </button>
    </nav>

    <div
        x-show="open"
        x-cloak
        @class([
            'nav:hidden',
            'mt-2.5 rounded-[20px] border border-line bg-white p-3.5 shadow-[0_24px_50px_-22px_rgba(12,20,36,.28)]' => $pill,
            'border-t border-line bg-white px-[clamp(16px,2.4vw,24px)] pt-3.5 pb-[18px]' => ! $pill,
        ])
    >
        <div class="flex flex-col gap-0.5">
            @foreach ($links + ['faq' => ['label' => 'Bantuan', 'route' => 'faq']] as $key => $link)
                <a
                    href="{{ route($link['route']) }}"
                    @class([
                        'flex min-h-12 items-center rounded-xl px-3 text-base',
                        'bg-brand/7 font-semibold text-brand' => $active === $key,
                        'font-medium text-ink' => $active !== $key,
                    ])
                >{{ $link['label'] }}</a>
            @endforeach
        </div>

        <a
            href="{{ $ctaHref }}"
            class="mt-3.5 flex min-h-12 items-center justify-center gap-[7px] rounded-[13px] bg-brand px-[18px] py-[13px] text-[15px] font-semibold text-white hover:bg-brand-dark hover:text-white"
        >
            Mulai Bandingkan <span class="font-mono text-xs">↗</span>
        </a>
    </div>
</div>
