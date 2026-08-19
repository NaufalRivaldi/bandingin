<div class="bg-white">
    <x-site-nav active="katalog" />

    <x-page-hero
        :items="[['label' => 'Katalog HP']]"
        title="Semua HP, satu daftar"
        lead="Saring berdasarkan harga, merek, dan spesifikasi. Centang dua HP untuk langsung mengadu keduanya."
        width="wide"
    >
        <div class="relative mt-[clamp(18px,2.4vw,26px)] max-w-[520px]">
            <label for="cari" class="sr-only">Cari HP</label>

            <input
                id="cari"
                type="text"
                autocomplete="off"
                placeholder="Cari nama HP atau merek..."
                wire:model.live.debounce.200ms="query"
                class="w-full rounded-[15px] border border-line bg-white px-[17px] py-[15px] font-sans text-[15px] font-medium text-ink shadow-[0_10px_26px_-14px_rgba(12,20,36,.3)]"
            />
        </div>
    </x-page-hero>

    <div id="katalog" class="mx-auto flex max-w-[1240px] flex-wrap items-start gap-[clamp(20px,2.6vw,36px)] px-[clamp(16px,2.4vw,24px)] pt-[clamp(26px,3.4vw,44px)]">
        <aside
            class="static w-full flex-[1_1_100%] min-[901px]:sticky min-[901px]:top-[88px] min-[901px]:w-auto min-[901px]:max-w-[300px] min-[901px]:min-w-[230px] min-[901px]:flex-[1_1_240px]"
        >
            <div class="overflow-hidden rounded-[20px] border border-line">
                <div class="flex items-center justify-between gap-2.5 border-b border-line bg-surface px-[18px] py-3.5">
                    <x-eyebrow>Filter</x-eyebrow>

                    <span class="flex items-center gap-3.5">
                        <button
                            type="button"
                            wire:click="resetFilters"
                            class="cursor-pointer border-0 bg-transparent p-0 font-sans text-xs font-semibold text-brand"
                        >Reset</button>

                        <button
                            type="button"
                            wire:click="toggleFilters"
                            aria-expanded="{{ $filtersOpen ? 'true' : 'false' }}"
                            class="cursor-pointer rounded-full border border-line bg-white px-[13px] py-[7px] font-sans text-xs font-semibold text-ink min-[901px]:hidden"
                        >{{ $filtersOpen ? 'Tutup' : 'Atur filter' }}</button>
                    </span>
                </div>

                <div class="{{ $filtersOpen ? '' : 'max-[900px]:hidden' }}">
                    <div class="p-[18px]">
                        <div class="text-[13px] font-semibold">Rentang harga</div>

                        <div class="mt-3 flex flex-col gap-[9px]">
                            @foreach ($priceRanges as $range)
                                @php($active = in_array($range['id'], $prices, true))

                                <button
                                    type="button"
                                    wire:key="price-{{ $range['id'] }}"
                                    wire:click="togglePrice('{{ $range['id'] }}')"
                                    @class([
                                        'flex w-full min-h-[34px] cursor-pointer items-center gap-2.5 border-0 bg-transparent px-0 py-0.5 text-left font-sans text-[13.5px] text-ink',
                                        'font-semibold' => $active,
                                    ])
                                >
                                    <span @class([
                                        'block size-4 flex-none rounded-[5px] border-[1.5px]',
                                        'border-brand bg-brand shadow-[inset_0_0_0_3px_#FFFFFF]' => $active,
                                        'border-line-strong bg-white' => ! $active,
                                    ])></span>

                                    <span class="flex-1 text-left">{{ $range['label'] }}</span>

                                    <span class="font-mono text-[11px] text-muted">{{ $this->priceCounts[$range['id']] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-line-soft px-[18px] pt-[18px] pb-[18px]">
                        <div class="text-[13px] font-semibold">Merek</div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($brandNames as $brand)
                                <x-chip
                                    wire:key="brand-{{ $brand }}"
                                    :active="in_array($brand, $brands, true)"
                                    wire:click="toggleBrand('{{ $brand }}')"
                                >{{ $brand }}</x-chip>
                            @endforeach
                        </div>
                    </div>

                    <div class="border-t border-line-soft px-[18px] pt-[18px] pb-[18px]">
                        <div class="text-[13px] font-semibold">Spesifikasi minimum</div>

                        <div class="mt-3 flex flex-col gap-[9px]">
                            @foreach ($specFilters as $filter)
                                @php($active = in_array($filter['id'], $specs, true))

                                <button
                                    type="button"
                                    wire:key="spec-{{ $filter['id'] }}"
                                    wire:click="toggleSpec('{{ $filter['id'] }}')"
                                    @class([
                                        'flex w-full min-h-[34px] cursor-pointer items-center gap-2.5 border-0 bg-transparent px-0 py-0.5 text-left font-sans text-[13.5px] text-ink',
                                        'font-semibold' => $active,
                                    ])
                                >
                                    <span @class([
                                        'block size-4 flex-none rounded-[5px] border-[1.5px]',
                                        'border-brand bg-brand shadow-[inset_0_0_0_3px_#FFFFFF]' => $active,
                                        'border-line-strong bg-white' => ! $active,
                                    ])></span>

                                    <span class="flex-1 text-left">{{ $filter['label'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <main class="min-w-[min(100%,280px)] flex-[3_1_560px]">
            <div class="flex flex-wrap items-center justify-between gap-3.5">
                <div class="text-[13.5px] text-muted">{{ count($this->results) }} HP ditemukan dari {{ $totalPhones }} total</div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-[12.5px] text-muted">Urutkan</span>

                    @foreach ($sorts as $option)
                        <x-chip
                            wire:key="sort-{{ $option['id'] }}"
                            tone="soft"
                            :active="$sort === $option['id']"
                            wire:click="setSort('{{ $option['id'] }}')"
                        >{{ $option['label'] }}</x-chip>
                    @endforeach
                </div>
            </div>

            @if ($this->results !== [])
                <div class="mt-[clamp(16px,2.2vw,24px)] grid grid-cols-[repeat(auto-fill,minmax(min(100%,240px),1fr))] gap-4">
                    @foreach ($this->results as $phone)
                        @php($picked = in_array($phone->id, $this->picked, true))

                        <x-phone-card wire:key="card-{{ $phone->id }}" :phone="$phone" :selected="$picked">
                            <div class="mt-3.5 flex items-center gap-2">
                                <button
                                    type="button"
                                    wire:click="toggleCompare('{{ $phone->id }}')"
                                    @class([
                                        'flex-1 cursor-pointer rounded-[11px] border px-3 py-2.5 font-sans text-[12.5px] font-semibold',
                                        'border-brand bg-brand text-white' => $picked,
                                        'border-line bg-surface text-ink' => ! $picked,
                                    ])
                                >{{ $picked ? 'Dipilih ✓' : 'Bandingkan' }}</button>

                                <a href="{{ route('hp.show', $phone->id) }}" class="text-[12.5px] font-semibold">Detail →</a>
                            </div>
                        </x-phone-card>
                    @endforeach
                </div>
            @else
                <div class="mt-6 rounded-[20px] border border-dashed border-line-strong px-[clamp(16px,2.4vw,24px)] py-[clamp(34px,5vw,60px)] text-center">
                    <div class="font-display text-[19px] font-extrabold tracking-[-.02em]">Tidak ada HP yang cocok</div>

                    <div class="mx-auto mt-2.5 max-w-[380px] text-sm leading-[1.55] text-muted">
                        Coba longgarkan filter harga atau hapus sebagian filter spesifikasi.
                    </div>

                    <button
                        type="button"
                        wire:click="resetFilters"
                        class="mt-[18px] cursor-pointer rounded-xl border-0 bg-brand px-5 py-3 font-sans text-sm font-semibold text-white hover:bg-brand-dark"
                    >Reset semua filter</button>
                </div>
            @endif
        </main>
    </div>

    @if ($this->pickedPhones !== [])
        <div class="sticky bottom-0 z-25 mt-7 border-t border-line bg-white/94 shadow-[0_-10px_30px_-20px_rgba(12,20,36,.4)] backdrop-blur-[14px]">
            <div class="mx-auto flex max-w-[1240px] flex-wrap items-center justify-between gap-3.5 px-[clamp(16px,2.4vw,24px)] py-3.5">
                <div class="flex flex-wrap items-center gap-3">
                    <x-eyebrow>Duel</x-eyebrow>
                    <span class="text-[14.5px] font-semibold">{{ $this->selectionText }}</span>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        type="button"
                        wire:click="clearSelection"
                        class="cursor-pointer border-0 bg-transparent font-sans text-[13px] font-semibold text-muted"
                    >Kosongkan</button>

                    @php($ready = count($this->pickedPhones) === $maxPicks)

                    <a
                        href="{{ $ready ? route('bandingkan', ['left' => $this->pickedPhones[0]->id, 'right' => $this->pickedPhones[1]->id]) : '#katalog' }}"
                        @if (! $ready) aria-disabled="true" @endif
                        @class([
                            'inline-flex items-center gap-[7px] rounded-xl px-5 py-3 text-sm font-semibold text-white',
                            'bg-brand hover:bg-brand-dark hover:text-white' => $ready,
                            'pointer-events-none bg-line-strong' => ! $ready,
                        ])
                    >Bandingkan →</a>
                </div>
            </div>
        </div>
    @endif

    <x-site-footer />
</div>
