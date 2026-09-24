@php
    $budgets = [
        ['range' => 'Rp1–2 juta', 'count' => '48 HP', 'examples' => ['Redmi 14C', 'Infinix Smart 9']],
        ['range' => 'Rp2–4 juta', 'count' => '96 HP', 'examples' => ['Redmi Note 14 Pro 5G', 'Infinix Note 40 Pro']],
        ['range' => 'Rp4–7 juta', 'count' => '74 HP', 'examples' => ['Galaxy A56 5G', 'POCO X7 Pro']],
        ['range' => 'Rp7 juta ke atas', 'count' => '61 HP', 'examples' => ['iPhone 16', 'Galaxy S25']],
    ];
@endphp

<div class="bg-white">
    <div class="px-[clamp(10px,1.6vw,14px)] pt-[clamp(10px,1.6vw,14px)]">
        <div class="hero-wash relative rounded-[clamp(20px,3vw,40px)] px-[clamp(12px,2.4vw,20px)] pt-3.5 pb-[clamp(150px,17vw,250px)]">
            <div class="hero-grid absolute inset-0 overflow-hidden rounded-[inherit]"></div>

            <x-site-nav variant="pill" />

            <div id="top" class="relative mx-auto max-w-[1180px] pt-[clamp(38px,6vw,74px)] text-center">
                <div class="anim-fade-up text-[13px] font-medium text-muted [animation-delay:.05s]">
                    Data spesifikasi &amp; harga terbaru, dicek tiap hari
                </div>

                <h1 class="anim-fade-up mt-3.5 font-display text-[clamp(38px,7.6vw,108px)] leading-[.94] font-extrabold tracking-[-.035em] text-balance text-ink [animation-delay:.16s]">
                    Jangan Beli HP<br />Sebelum Dibandingkan
                </h1>

                <p class="anim-fade-up mx-auto mt-[clamp(18px,2.4vw,28px)] max-w-[620px] text-[clamp(15px,1.3vw,17.5px)] leading-relaxed text-muted [animation-delay:.28s]">
                    Adu spesifikasi, harga, dan performa dua gadget berdampingan. Kami tandai mana yang menang di tiap baris — kamu tinggal putuskan.
                </p>

                <div id="bandingkan" class="anim-fade-up relative z-20 mx-auto mt-[clamp(26px,3.4vw,42px)] max-w-[820px] [animation-delay:.4s]">
                    <div class="flex flex-wrap items-center justify-center gap-3.5" x-on:click.outside="$wire.closePickers()">
                        @foreach (['left' => 'HP pertama', 'right' => 'HP kedua'] as $side => $srLabel)
                            @if ($side === 'right')
                                <div class="grid size-[54px] flex-none place-items-center rounded-full border border-line bg-white font-mono text-sm font-semibold tracking-[-.02em] text-brand shadow-[0_8px_22px_rgba(12,20,36,.1)]">VS</div>
                            @endif

                            <div class="relative min-w-[240px] flex-[1_1_300px] text-left">
                                <label for="pick-{{ $side }}" class="sr-only">{{ $srLabel }}</label>

                                <input
                                    id="pick-{{ $side }}"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="Pilih {{ $side === 'left' ? 'HP pertama' : 'HP kedua' }}..."
                                    wire:model.live.debounce.200ms="{{ $side }}Query"
                                    x-on:focus="$wire.openPicker('{{ $side }}')"
                                    class="w-full rounded-2xl border border-line bg-white p-[18px] font-sans text-[15.5px] font-medium text-ink shadow-[0_8px_24px_rgba(12,20,36,.06)]"
                                />

                                @if ($open === $side)
                                    <div role="listbox" class="absolute top-[calc(100%+8px)] right-0 left-0 z-8 max-h-[292px] overflow-auto rounded-[18px] border border-line bg-white p-1.5 shadow-[0_24px_50px_-18px_rgba(12,20,36,.28)]">
                                        @forelse ($side === 'left' ? $this->leftSuggestions : $this->rightSuggestions as $suggestion)
                                            <button
                                                type="button"
                                                wire:key="sugg-{{ $side }}-{{ $suggestion->slug }}"
                                                wire:click="pick('{{ $side }}', '{{ $suggestion->slug }}')"
                                                class="flex w-full cursor-pointer items-center gap-3 rounded-[13px] border-0 bg-transparent px-2.5 py-[9px] text-left hover:bg-surface"
                                            >
                                                <x-device-mock class="h-11 w-8 flex-none rounded-md" inset="inset-[3px]" face-radius="rounded-sm" />
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-[14.5px] font-medium text-ink">{{ $suggestion->name }}</span>
                                                    <span class="block text-[11.5px] text-muted">{{ $suggestion->brand->name }}</span>
                                                </span>
                                                <span class="font-mono text-[12.5px] text-ink">{{ $suggestion->formattedPrice() }}</span>
                                            </button>
                                        @empty
                                            <div class="px-2.5 py-3 text-[13.5px] text-muted">HP tidak ditemukan.</div>
                                        @endforelse
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <a
                        href="{{ route('bandingkan', ['left' => $this->leftPhone->slug, 'right' => $this->rightPhone->slug]) }}"
                        class="mx-auto mt-4 block w-full max-w-[320px] rounded-[14px] bg-brand px-6 py-[17px] text-center font-sans text-base font-semibold text-white shadow-[0_12px_26px_rgba(37,99,235,.3)] transition-[background,transform] duration-200 hover:-translate-y-px hover:bg-brand-dark hover:text-white"
                    >
                        Bandingkan Sekarang
                    </a>

                    <div class="mt-[22px] flex flex-wrap items-center justify-center gap-2">
                        <span class="text-xs font-medium text-muted">Populer:</span>

                        @foreach ($matchups as $matchup)
                            <button
                                type="button"
                                wire:key="matchup-{{ $loop->index }}"
                                wire:click="setMatchup('{{ $matchup->leftPhone->slug }}', '{{ $matchup->rightPhone->slug }}')"
                                class="cursor-pointer rounded-full border border-line bg-white/70 px-3.5 py-2 font-sans text-[12.5px] font-medium text-ink transition-colors duration-200 hover:border-line-strong hover:bg-white"
                            >{{ $matchup->label() }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="px-[clamp(10px,1.6vw,14px)]">
        <div id="duel" class="anim-card-up relative z-5 mx-auto mt-[clamp(-140px,-14vw,-110px)] max-w-[1060px] overflow-hidden rounded-[clamp(18px,2.4vw,28px)] border border-line bg-white shadow-[0_40px_80px_-34px_rgba(12,20,36,.3),0_2px_6px_rgba(12,20,36,.04)]">
            <div class="flex flex-wrap items-center justify-between gap-3.5 border-b border-line bg-surface px-[clamp(16px,2.4vw,26px)] py-[clamp(16px,2vw,22px)]">
                <div>
                    <x-eyebrow>Kartu Duel</x-eyebrow>
                    <div class="mt-[5px] font-display text-[clamp(16px,1.7vw,20px)] font-extrabold tracking-[-.02em]">Hasil duel langsung</div>
                </div>

                <x-toggle-switch
                    :on="$onlyDifferences"
                    label="Hanya tampilkan perbedaan"
                    wire:click="toggleOnlyDifferences"
                />
            </div>

            <div class="px-[clamp(10px,2.4vw,26px)] pb-[clamp(16px,2vw,22px)]">
                <table class="w-full table-fixed border-collapse">
                    <caption class="sr-only">Perbandingan spesifikasi dua HP</caption>
                    <colgroup>
                        <col class="w-[27%]" />
                        <col class="w-[36.5%]" />
                        <col class="w-[36.5%]" />
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col" class="px-2 pt-[clamp(16px,2vw,24px)] pb-4 pl-1 text-left align-bottom">
                                <span class="font-mono text-[10.5px] font-normal tracking-[.12em] text-muted uppercase">Spesifikasi</span>
                            </th>

                            @foreach ([$this->leftPhone, $this->rightPhone] as $phone)
                                <th scope="col" wire:key="head-{{ $phone->slug }}" class="px-2.5 pt-[clamp(16px,2vw,24px)] pb-4 text-left">
                                    <x-device-mock class="h-[clamp(60px,8vw,88px)] w-[clamp(44px,6vw,64px)]" inset="inset-[4px]" />

                                    <span class="mt-3 block font-display text-[clamp(14px,1.5vw,18px)] font-extrabold tracking-[-.02em] text-ink">{{ $phone->name }}</span>
                                    <span class="mt-1.5 block font-mono text-[clamp(12px,1.3vw,15px)] font-medium text-ink">{{ $phone->formattedPrice() }}</span>

                                    <span class="mt-2.5 flex flex-wrap items-center gap-2">
                                        <x-affiliate-link :href="route('hp.show', $phone->slug)" />
                                    </span>

                                    <x-marketplace-tags class="mt-2" />
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->duel->rows() as $row)
                            <tr wire:key="row-{{ $row->key }}">
                                <th scope="row" class="border-t border-line px-2 py-3.5 pl-1 text-left align-top font-sans text-[clamp(11.5px,2.4vw,13.5px)] font-medium text-muted">{{ $row->label }}</th>

                                <td @class([
                                    'border-t border-line px-2.5 py-3.5 align-top font-mono text-[clamp(11.5px,2.4vw,13.5px)] leading-[1.45] tracking-[-.01em] text-ink',
                                    'bg-win/7' => $row->leftWins,
                                ])>
                                    {{ $row->leftValue }}
                                    @if ($row->leftWins)
                                        <x-win-badge :delay="0.85 + $loop->index * 0.09" />
                                    @endif
                                </td>

                                <td @class([
                                    'border-t border-line px-2.5 py-3.5 align-top font-mono text-[clamp(11.5px,2.4vw,13.5px)] leading-[1.45] tracking-[-.01em] text-ink',
                                    'bg-win/7' => $row->rightWins,
                                ])>
                                    {{ $row->rightValue }}
                                    @if ($row->rightWins)
                                        <x-win-badge :delay="0.85 + $loop->index * 0.09" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-line bg-surface px-[clamp(16px,2.4vw,26px)] py-[clamp(14px,1.8vw,20px)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="font-display text-[clamp(14px,1.5vw,17px)] font-extrabold tracking-[-.02em]">{{ $this->duel->summary() }}</div>

                    <div class="flex items-center gap-2.5 font-mono text-xs text-muted">
                        <span>{{ $this->duel->leftWins }}</span>
                        <span class="flex h-2 w-[clamp(90px,20vw,160px)] overflow-hidden rounded-full bg-line">
                            <span
                                class="block transition-[width] duration-300 {{ $this->duel->winner()?->id === $this->leftPhone->id ? 'bg-win' : 'bg-[#c3cee3]' }}"
                                style="width: {{ $this->duel->leftShare() }}%"
                            ></span>
                            <span
                                class="block transition-[width] duration-300 {{ $this->duel->winner()?->id === $this->rightPhone->id ? 'bg-win' : 'bg-[#c3cee3]' }}"
                                style="width: {{ $this->duel->rightShare() }}%"
                            ></span>
                        </span>
                        <span>{{ $this->duel->rightWins }}</span>
                    </div>
                </div>

                <x-affiliate-note class="mt-3" show-warranty />
            </div>
        </div>
    </div>

    <section id="harga" class="mx-auto max-w-[1200px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(64px,8vw,112px)]">
        <x-section-heading
            eyebrow="Belanja"
            title="Cari Berdasarkan Budget"
            size="lg"
            lead="Tentukan dulu batas belanjamu, kami tunjukkan HP yang masuk dan layak diadu."
        />

        <div class="mt-[clamp(26px,3vw,40px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,210px),1fr))] gap-4">
            @foreach ($budgets as $budget)
                <a
                    href="{{ route('katalog') }}"
                    class="block rounded-[20px] border border-line bg-surface p-[22px] text-ink transition-[transform,box-shadow] duration-200 hover:-translate-y-[3px] hover:text-ink hover:shadow-[0_18px_34px_-20px_rgba(12,20,36,.3)]"
                >
                    <div class="font-display text-[23px] font-extrabold tracking-[-.03em]">{{ $budget['range'] }}</div>
                    <div class="mt-1.5 font-mono text-xs text-muted">{{ $budget['count'] }}</div>
                    <div class="mt-4 border-t border-line pt-3.5 text-[13.5px] leading-[1.7] text-muted">
                        {!! implode('<br />', $budget['examples']) !!}
                    </div>
                    <div class="mt-3.5 text-[13px] font-semibold text-brand">Lihat semua →</div>
                </a>
            @endforeach
        </div>
    </section>

    <section id="berita" class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(72px,9vw,128px)]">
        <x-section-heading eyebrow="Redaksi" title="Berita dan Ulasan" class="items-baseline!">
            <a href="{{ route('berita') }}" class="text-[13.5px] font-semibold">Lihat Semua Berita →</a>
        </x-section-heading>

        <div class="mt-[clamp(32px,4vw,56px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,300px),1fr))] items-start gap-[clamp(32px,5vw,64px)]">
            <article>
                <a href="{{ route('berita.show', $featured->slug) }}" class="block text-ink hover:text-ink">
                    @if ($featured->image_url)
                        <img
                            src="{{ $featured->image_url }}"
                            alt="{{ $featured->title }}"
                            title="{{ $featured->title }}"
                            referrerpolicy="no-referrer"
                            class="aspect-16/10 w-full overflow-hidden rounded-[22px] object-cover"
                        >
                    @else
                        <div class="photo-slot relative aspect-16/10 overflow-hidden rounded-[22px]">
                            <div class="absolute inset-0 grid place-items-center font-mono text-[10.5px] tracking-[.14em] text-faint uppercase">Foto artikel</div>
                        </div>
                    @endif

                    <div class="mt-5 font-mono text-[10.5px] tracking-[.14em] text-brand uppercase">{{ $featured->category->value }}</div>

                    <h3 class="mt-2.5 font-display text-[clamp(21px,2.4vw,30px)] leading-[1.15] font-extrabold tracking-[-.025em]">{{ $featured->title }}</h3>

                    <p class="mt-3.5 max-w-[520px] text-[15px] leading-[1.65] text-muted">{{ $featured->excerpt }}</p>

                    <div class="mt-3.5 font-mono text-[11.5px] text-muted">{{ $featured->dateLong() }}</div>
                </a>
            </article>

            <div class="flex flex-col">
                @foreach ($latest as $article)
                    <a
                        href="{{ route('berita.show', $article->slug) }}"
                        wire:key="latest-{{ $article->id }}"
                        class="flex gap-4 border-b border-line py-[18px] text-ink last:border-b-0 hover:text-brand"
                    >
                        @if ($article->image_url)
                            <img
                                src="{{ $article->image_url }}"
                                alt="{{ $article->title }}"
                                title="{{ $article->title }}"
                                loading="lazy"
                                referrerpolicy="no-referrer"
                                class="h-[58px] w-[76px] flex-none rounded-xl object-cover"
                            >
                        @else
                            <span class="photo-slot h-[58px] w-[76px] flex-none rounded-xl"></span>
                        @endif
                        <span class="flex-1">
                            <span class="block text-[15px] leading-[1.35] font-semibold tracking-[-.01em]">{{ $article->title }}</span>
                            <span class="mt-[7px] block font-mono text-[11px] text-muted">{{ $article->meta() }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <x-site-footer />
</div>
