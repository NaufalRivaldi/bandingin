@php
    $article = $this->article;
@endphp

<div class="bg-white">
    <x-site-nav active="berita" />

    <div class="anim-fade-up mx-auto max-w-[760px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(26px,3.4vw,52px)]">
        <x-breadcrumb :items="[
            ['label' => 'Berita', 'url' => route('berita')],
            ['label' => $article->category->value],
        ]" />

        <x-eyebrow class="mt-[clamp(22px,2.6vw,34px)] text-brand!">{{ $article->category->value }}</x-eyebrow>

        <h1 class="mt-3.5 font-display text-[clamp(28px,4.2vw,52px)] leading-[1.08] font-extrabold tracking-[-.03em] text-pretty">
            {{ $article->title }}
        </h1>

        <p class="mt-[18px] text-[clamp(16px,1.4vw,19px)] leading-[1.65] text-muted">{{ $article->excerpt }}</p>

        <div class="mt-6 flex flex-wrap items-center gap-3.5 border-y border-line py-4">
            @if ($article->isScraped())
                <span class="grid size-[34px] place-items-center rounded-full border border-line bg-surface font-mono text-[11px] font-semibold text-muted">↗</span>
                <span class="text-[13.5px] font-medium text-ink">
                    Sumber:
                    <a href="{{ $article->source_url }}" target="_blank" rel="noopener nofollow" class="text-brand hover:underline">{{ $article->source }}</a>
                </span>
            @else
                <span class="grid size-[34px] place-items-center rounded-full border border-line bg-surface font-mono text-[11px] font-semibold text-muted">RA</span>
                <span class="text-[13.5px] font-medium text-ink">Rizky Adhitama</span>
            @endif
            <span class="font-mono text-[11.5px] text-muted">{{ $article->dateLong() }}</span>
            <span class="font-mono text-[11.5px] text-muted">{{ $article->read_minutes }} menit baca</span>
        </div>
    </div>

    <div class="mx-auto mt-[clamp(26px,3vw,40px)] max-w-[980px] px-[clamp(16px,2.4vw,24px)]">
        @if ($article->image_url)
            <img
                src="{{ $article->image_url }}"
                alt="{{ $article->title }}"
                title="{{ $article->title }}"
                referrerpolicy="no-referrer"
                class="aspect-16/9 w-full rounded-[clamp(16px,2vw,26px)] object-cover"
            >
        @else
            <div class="photo-slot relative aspect-16/9 rounded-[clamp(16px,2vw,26px)]">
                <div class="absolute inset-0 grid place-items-center font-mono text-[10.5px] tracking-[.14em] text-faint uppercase">Foto artikel</div>
            </div>
        @endif

        <div class="mt-2.5 text-[11.5px] text-muted">
            @if ($article->isScraped())
                Foto: {{ $article->source }}
            @else
                Foto: dokumentasi Bandingin.id
            @endif
        </div>
    </div>

    <article class="mx-auto mt-[clamp(30px,3.6vw,48px)] max-w-[720px] px-[clamp(16px,2.4vw,24px)]">
        <div class="article-prose text-[clamp(16px,1.4vw,17.5px)] leading-[1.78] text-ink">
            {!! $this->body !!}
        </div>

        <div class="mt-[30px] rounded-2xl border border-line bg-surface p-[18px]">
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-[13.5px] font-semibold">Cek harga hari ini</span>

                <x-affiliate-link :href="route('katalog')" />

                <x-marketplace-tags tone="boxed" />
            </div>

            <x-affiliate-note class="mt-2.5" show-warranty />
        </div>

        <div class="mt-7 flex flex-wrap items-center gap-2">
            <x-eyebrow class="tracking-[.13em]">Tag</x-eyebrow>

            @foreach ($article->tags as $tag)
                <span wire:key="tag-{{ $loop->index }}" class="rounded-full border border-line px-3 py-1.5 text-[12.5px] text-ink">{{ $tag }}</span>
            @endforeach
        </div>

        @if (! $article->isScraped())
            <div class="mt-[30px] flex items-start gap-3.5 border-t border-line pt-[26px]">
                <span class="grid size-11 flex-none place-items-center rounded-full border border-line bg-surface font-mono text-[13px] font-semibold text-muted">RA</span>

                <div>
                    <div class="text-[14.5px] font-semibold">Rizky Adhitama</div>
                    <div class="mt-1.5 max-w-[520px] text-[13.5px] leading-[1.65] text-muted">
                        Nulis soal HP kelas menengah di Bandingin.id sejak 2024. Semua unit diuji sendiri, harga dicek langsung di marketplace.
                    </div>
                </div>
            </div>
        @endif
    </article>

    <x-disqus-comments
        :identifier="$article->slug"
        :url="route('berita.show', $article->slug)"
        :title="$article->title"
    />

    <section class="mx-auto mt-[clamp(48px,6vw,84px)] max-w-[1140px] px-[clamp(16px,2.4vw,24px)]">
        <div class="flex flex-wrap items-baseline justify-between gap-3.5 border-b border-line pb-[18px]">
            <h2 class="font-display text-[clamp(20px,2.2vw,26px)] font-extrabold tracking-[-.026em]">Artikel Terkait</h2>

            <a href="{{ route('berita') }}" class="font-sans text-[13.5px] font-semibold">← Kembali ke semua berita</a>
        </div>

        <div class="mt-[26px] grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] gap-[clamp(18px,2.2vw,28px)]">
            @foreach ($this->related as $item)
                <x-article-card wire:key="related-{{ $item->id }}" :article="$item" :show-excerpt="false" />
            @endforeach
        </div>
    </section>

    <x-site-footer />
</div>
