@php
    $article = $this->article;
@endphp

<div class="bg-white">
    <x-site-nav active="berita" />

    <div class="anim-fade-up mx-auto max-w-[760px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(26px,3.4vw,52px)]">
        <x-breadcrumb :items="[
            ['label' => 'Berita', 'url' => route('berita')],
            ['label' => $article->category],
        ]" />

        <x-eyebrow class="mt-[clamp(22px,2.6vw,34px)] text-brand!">{{ $article->category }}</x-eyebrow>

        <h1 class="mt-3.5 font-display text-[clamp(28px,4.2vw,52px)] leading-[1.08] font-extrabold tracking-[-.03em] text-pretty">
            {{ $article->title }}
        </h1>

        <p class="mt-[18px] text-[clamp(16px,1.4vw,19px)] leading-[1.65] text-muted">{{ $article->excerpt }}</p>

        <div class="mt-6 flex flex-wrap items-center gap-3.5 border-y border-line py-4">
            <span class="grid size-[34px] place-items-center rounded-full border border-line bg-surface font-mono text-[11px] font-semibold text-muted">RA</span>
            <span class="text-[13.5px] font-medium text-ink">Rizky Adhitama</span>
            <span class="font-mono text-[11.5px] text-muted">{{ $article->dateLong() }}</span>
            <span class="font-mono text-[11.5px] text-muted">{{ $article->readMinutes }} menit baca</span>
        </div>
    </div>

    <div class="mx-auto mt-[clamp(26px,3vw,40px)] max-w-[980px] px-[clamp(16px,2.4vw,24px)]">
        <div class="photo-slot relative aspect-16/9 rounded-[clamp(16px,2vw,26px)]">
            <div class="absolute inset-0 grid place-items-center font-mono text-[10.5px] tracking-[.14em] text-faint uppercase">Foto artikel</div>
        </div>

        <div class="mt-2.5 text-[11.5px] text-muted">Foto: dokumentasi Bandingin.id</div>
    </div>

    <article class="mx-auto mt-[clamp(30px,3.6vw,48px)] max-w-[720px] px-[clamp(16px,2.4vw,24px)]">
        @foreach ($article->body as $paragraph)
            <p wire:key="body-{{ $loop->index }}" class="mb-[22px] text-[clamp(16px,1.4vw,17.5px)] leading-[1.78] text-ink">{{ $paragraph }}</p>
        @endforeach

        <blockquote class="my-8 rounded-r-2xl border-l-[3px] border-brand bg-surface px-[26px] py-[22px]">
            <p class="font-display text-[clamp(18px,2vw,22px)] leading-[1.4] font-bold tracking-[-.02em]">{{ $article->quote }}</p>
        </blockquote>

        @if ($article->specs)
            <div class="my-[34px] overflow-hidden rounded-[20px] border border-line">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-surface px-[18px] py-4">
                    <div>
                        <x-eyebrow class="text-[10px] tracking-[.14em]">Kartu Duel</x-eyebrow>
                        <div class="mt-1 font-display text-base font-extrabold tracking-[-.02em]">{{ $article->specs->title }}</div>
                    </div>

                    <a href="{{ route('bandingkan') }}" class="text-[12.5px] font-semibold">Buka perbandingan penuh →</a>
                </div>

                <table class="w-full table-fixed border-collapse">
                    <caption class="sr-only">Perbandingan spesifikasi</caption>
                    <colgroup>
                        <col class="w-[28%]" />
                        <col class="w-[36%]" />
                        <col class="w-[36%]" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col" class="px-2.5 pt-3.5 pb-3 pl-4 text-left font-mono text-[10px] font-normal tracking-[.12em] text-muted uppercase">Spesifikasi</th>
                            <th scope="col" class="px-2.5 pt-3.5 pb-3 text-left font-display text-sm font-extrabold tracking-[-.02em] text-ink">{{ $article->specs->left }}</th>
                            <th scope="col" class="px-2.5 pt-3.5 pr-4 pb-3 text-left font-display text-sm font-extrabold tracking-[-.02em] text-ink">{{ $article->specs->right }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($article->specs->rows as $row)
                            <tr wire:key="spec-{{ $loop->index }}">
                                <th scope="row" class="border-t border-line px-2.5 py-[13px] pl-4 text-left align-top font-sans text-[12.5px] font-medium text-muted">{{ $row['label'] }}</th>

                                <td @class([
                                    'border-t border-line px-2.5 py-[13px] align-top font-mono text-[12.5px] leading-[1.45] tracking-[-.01em] text-ink',
                                    'bg-win/7' => $row['winner'] === 'left',
                                ])>
                                    {{ $row['left'] }}
                                    @if ($row['winner'] === 'left')
                                        <x-win-badge />
                                    @endif
                                </td>

                                <td @class([
                                    'border-t border-line px-2.5 py-[13px] align-top font-mono text-[12.5px] leading-[1.45] tracking-[-.01em] text-ink',
                                    'bg-win/7' => $row['winner'] === 'right',
                                ])>
                                    {{ $row['right'] }}
                                    @if ($row['winner'] === 'right')
                                        <x-win-badge />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @foreach ($article->bodyRest as $paragraph)
            <p wire:key="body-rest-{{ $loop->index }}" class="mb-[22px] text-[clamp(16px,1.4vw,17.5px)] leading-[1.78] text-ink">{{ $paragraph }}</p>
        @endforeach

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

        <div class="mt-[30px] flex items-start gap-3.5 border-t border-line pt-[26px]">
            <span class="grid size-11 flex-none place-items-center rounded-full border border-line bg-surface font-mono text-[13px] font-semibold text-muted">RA</span>

            <div>
                <div class="text-[14.5px] font-semibold">Rizky Adhitama</div>
                <div class="mt-1.5 max-w-[520px] text-[13.5px] leading-[1.65] text-muted">
                    Nulis soal HP kelas menengah di Bandingin.id sejak 2024. Semua unit diuji sendiri, harga dicek langsung di marketplace.
                </div>
            </div>
        </div>
    </article>

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
