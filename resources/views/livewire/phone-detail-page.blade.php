<div class="bg-white">
    <x-site-nav active="katalog" />

    <div class="page-wash border-b border-line">
        <div class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(20px,3vw,34px)] pb-[clamp(30px,4vw,52px)]">
            <x-breadcrumb :items="[
                ['label' => $this->phone->brand->name],
                ['label' => $this->phone->name],
            ]" />

            <div class="anim-fade-up mt-[clamp(18px,2.4vw,28px)] flex flex-wrap gap-[clamp(20px,3.4vw,48px)]">
                <div class="min-w-[min(100%,240px)] flex-[1_1_300px]">
                    <div class="relative grid aspect-4/3 place-items-center rounded-3xl border border-line bg-white shadow-[0_24px_50px_-34px_rgba(12,20,36,.45)]">
                        <x-device-mock class="h-[78%] w-[34%]" radius="rounded-2xl" inset="inset-[6px]" face-radius="rounded-[11px]" />

                        <span class="absolute bottom-3.5 left-1/2 -translate-x-1/2 font-mono text-[10px] tracking-[.14em] text-faint uppercase">Foto produk</span>
                    </div>

                    <div class="mt-3 flex gap-2.5">
                        @foreach ($thumbnails as $index)
                            <button
                                type="button"
                                wire:key="thumb-{{ $index }}"
                                wire:click="selectThumb({{ $index }})"
                                aria-label="Foto produk {{ $index + 1 }}"
                                @class([
                                    'block size-[62px] cursor-pointer rounded-[13px] border bg-[linear-gradient(155deg,#f4f6fb,#e4eaf5)]',
                                    'border-brand' => $thumb === $index,
                                    'border-line' => $thumb !== $index,
                                ])
                            ></button>
                        @endforeach
                    </div>
                </div>

                <div class="min-w-[min(100%,280px)] flex-[1_1_380px]">
                    <div class="font-mono text-[10.5px] tracking-[.12em] text-muted uppercase">{{ $this->phone->brand->name }}</div>

                    <h1 class="mt-2 font-display text-[clamp(28px,4.2vw,48px)] leading-[1.03] font-extrabold tracking-[-.035em]">{{ $this->phone->name }}</h1>

                    <div class="mt-4 flex flex-wrap items-center gap-2.5">
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-win/28 bg-win/12 px-[13px] py-1.5 text-[13px] font-semibold text-win-ink">
                            Skor {{ $this->phone->averageScore() }}
                        </span>
                        <span class="text-[13px] text-muted">Rata-rata dari lima kategori penilaian</span>
                    </div>

                    <div class="mt-5 flex flex-wrap items-end gap-3">
                        <div>
                            <div class="text-xs text-muted">Harga terendah hari ini</div>
                            <div class="mt-1 font-mono text-[clamp(24px,3vw,34px)] font-semibold tracking-[-.02em]">{{ $this->lowestPriceLabel }}</div>
                        </div>

                        <div class="pb-1.5 text-[12.5px] text-muted">Harga resmi {{ $this->officialPriceLabel }}</div>
                    </div>

                    <div class="mt-5 flex flex-wrap gap-2.5">
                        <a
                            href="#harga"
                            class="inline-flex items-center gap-[7px] rounded-[13px] bg-brand px-5 py-3.5 text-[15px] font-semibold text-white shadow-[0_12px_26px_rgba(37,99,235,.28)] hover:bg-brand-dark hover:text-white"
                        >Lihat Semua Harga</a>

                        <a
                            href="{{ route('bandingkan', ['left' => $this->phone->slug]) }}"
                            class="inline-flex items-center gap-[7px] rounded-[13px] border border-line bg-white px-5 py-3.5 text-[15px] font-semibold text-ink hover:border-brand hover:text-brand"
                        >Bandingkan HP Ini</a>
                    </div>

                    <div class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(min(100%,130px),1fr))] gap-2.5">
                        @foreach ($this->highlights as $highlight)
                            <div wire:key="highlight-{{ $loop->index }}" class="rounded-[14px] border border-line bg-white px-3.5 py-[13px]">
                                <div class="font-mono text-[10px] tracking-[.12em] text-muted uppercase">{{ $highlight['label'] }}</div>
                                <div class="mt-1.5 text-sm leading-[1.3] font-semibold">{{ $highlight['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(38px,5vw,68px)]">
        <x-pros-cons :pros="$this->phone->pros" :cons="$this->phone->cons" />
    </section>

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(44px,5.6vw,80px)]">
        <x-section-heading eyebrow="Penilaian" title="Skor per kategori" />

        <div class="mt-[clamp(20px,2.6vw,30px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,230px),1fr))] gap-[clamp(16px,2.4vw,28px)]">
            @foreach ($this->phone->scores() as $label => $value)
                <x-score-bar wire:key="score-{{ $loop->index }}" :label="$label" :value="$value" />
            @endforeach
        </div>
    </section>

    <section id="spesifikasi" class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(44px,5.6vw,80px)]">
        <x-section-heading eyebrow="Spesifikasi" title="Detail lengkap" />

        <div class="mt-[clamp(20px,2.6vw,30px)] overflow-hidden rounded-[22px] border border-line">
            @foreach ($this->specGroups as $group)
                <div wire:key="group-{{ $loop->index }}">
                    <div class="border-b border-line bg-surface px-[18px] py-[13px] font-mono text-[10.5px] tracking-[.14em] text-brand uppercase">{{ $group['title'] }}</div>

                    @foreach ($group['rows'] as $row)
                        <div
                            wire:key="row-{{ $group['title'] }}-{{ $row['label'] }}"
                            class="flex flex-wrap gap-x-4 gap-y-2 border-b border-line-soft px-[clamp(14px,2.2vw,18px)] py-[13px]"
                        >
                            <span class="min-w-[120px] flex-[1_1_34%] text-[13.5px] text-muted">{{ $row['label'] }}</span>
                            <span class="min-w-[150px] flex-[1_1_55%] font-mono text-[13.5px] tracking-[-.01em] [overflow-wrap:anywhere]">{{ $row['value'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </section>

    <section id="harga" class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(44px,5.6vw,80px)]">
        <x-section-heading eyebrow="Harga hari ini" title="Daftar penjual">
            <div class="flex flex-wrap gap-2">
                @foreach ($this->variants as $index => $option)
                    <x-chip
                        wire:key="variant-{{ $option->id }}"
                        :active="$variant === $index"
                        wire:click="selectVariant({{ $index }})"
                    >{{ $option->label }}</x-chip>
                @endforeach
            </div>
        </x-section-heading>

        <div class="mt-[clamp(18px,2.4vw,26px)] overflow-hidden rounded-[22px] border border-line">
            @foreach ($this->sellers as $seller)
                <x-seller-row wire:key="seller-{{ $seller->id }}" :seller="$seller" :cheapest="$loop->first" />
            @endforeach
        </div>

        <x-affiliate-note class="mt-3.5" />
    </section>

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(44px,5.6vw,80px)]">
        <x-section-heading eyebrow="Alternatif" title="Adu dengan HP lain" />

        <div class="mt-[clamp(18px,2.4vw,26px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,220px),1fr))] gap-3.5">
            @foreach ($this->rivals as $rival)
                <a
                    href="{{ route('hp.show', $rival->slug) }}"
                    wire:key="rival-{{ $rival->slug }}"
                    class="block rounded-[18px] border border-line bg-surface p-[18px] text-left text-ink transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:text-ink hover:shadow-[0_16px_30px_-22px_rgba(12,20,36,.5)]"
                >
                    <span class="block font-mono text-[10px] tracking-[.12em] text-muted uppercase">{{ $rival->brand->name }}</span>
                    <span class="mt-1.5 block font-display text-base leading-[1.3] font-extrabold tracking-[-.02em]">{{ $rival->name }}</span>
                    <span class="mt-1.5 block font-mono text-[13px] text-muted">{{ $rival->formattedPrice() }}</span>
                    <span class="mt-3 block text-[12.5px] font-semibold text-brand">Lihat HP ini →</span>
                </a>
            @endforeach
        </div>
    </section>

    <x-site-footer />
</div>
