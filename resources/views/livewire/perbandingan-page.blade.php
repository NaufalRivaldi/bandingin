@php
    $phones = ['left' => $this->leftPhone, 'right' => $this->rightPhone];
    $scores = ['left' => $this->leftPhone->averageScore(), 'right' => $this->rightPhone->averageScore()];
    $suggestions = ['left' => $this->leftSuggestions, 'right' => $this->rightSuggestions];
@endphp

<div class="bg-white">
    <x-site-nav active="bandingkan" />

    <x-page-hero
        :title="$this->pairTitle"
        :lead="'Perbandingan lengkap ' . $specCount . ' spesifikasi, harga terkini di tiga marketplace, dan rekomendasi siapa yang cocok untuk kamu.'"
        :items="[['label' => 'Bandingkan', 'url' => route('home')], ['label' => $this->pairTitle]]"
        width="medium"
    >
        <div
            class="anim-fade-up mt-[clamp(22px,3vw,34px)] flex flex-wrap items-stretch gap-[clamp(10px,1.6vw,18px)]"
            x-on:click.outside="$wire.closePickers()"
        >
            @foreach ($phones as $side => $phone)
                @if ($side === 'right')
                    <div class="grid w-full flex-none place-items-center self-center nav:w-auto">
                        <button
                            type="button"
                            wire:click="swap"
                            aria-label="Tukar posisi"
                            class="grid size-14 cursor-pointer place-items-center rounded-full border border-line bg-white font-mono text-sm font-semibold text-brand shadow-[0_8px_22px_rgba(12,20,36,.12)] transition-colors duration-200 hover:border-brand"
                        >VS</button>
                        <span class="mt-2 text-[11px] text-muted">Klik untuk tukar</span>
                    </div>
                @endif

                <div
                    wire:key="picker-{{ $side }}"
                    class="relative min-w-[min(100%,240px)] flex-[1_1_300px] rounded-[20px] border border-line bg-white p-[clamp(14px,2.2vw,18px)] shadow-[0_18px_40px_-26px_rgba(12,20,36,.35)]"
                >
                    <div class="flex items-start gap-4">
                        <x-device-mock class="h-[86px] w-[62px] flex-none rounded-[10px]" inset="inset-[4px]" face-radius="rounded-[7px]" />

                        <div class="min-w-0 flex-1">
                            <x-eyebrow>{{ $phone->brand }}</x-eyebrow>

                            <a
                                href="{{ route('hp.show', $phone->id) }}"
                                class="mt-[5px] block font-display text-[19px] font-extrabold tracking-[-.025em] text-ink hover:text-brand"
                            >{{ $phone->name }}</a>

                            <div class="mt-1.5 font-mono text-[15px] font-medium">{{ $phone->formattedPrice() }}</div>

                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span @class([
                                    'inline-flex items-center gap-1 rounded-full border px-[11px] py-[5px] font-sans text-xs font-semibold',
                                    'border-win/28 bg-win/12 text-win-ink' => $scores[$side] >= $scores[$side === 'left' ? 'right' : 'left'],
                                    'border-line bg-surface text-muted' => $scores[$side] < $scores[$side === 'left' ? 'right' : 'left'],
                                ])>Skor {{ $scores[$side] }}</span>

                                <button
                                    type="button"
                                    wire:click="openPicker('{{ $side }}')"
                                    class="min-h-[38px] cursor-pointer rounded-full border border-line bg-surface px-3.5 py-[9px] font-sans text-xs font-medium text-ink transition-colors duration-200 hover:bg-[#E9EEF8]"
                                >Ganti HP</button>
                            </div>
                        </div>
                    </div>

                    @if ($open === $side)
                        <div
                            role="listbox"
                            class="absolute top-[calc(100%+8px)] right-0 left-0 z-12 max-h-[300px] overflow-auto rounded-[18px] border border-line bg-white p-1.5 shadow-[0_24px_50px_-18px_rgba(12,20,36,.28)]"
                        >
                            <label for="ganti-{{ $side }}" class="sr-only">Cari HP</label>

                            <input
                                id="ganti-{{ $side }}"
                                type="text"
                                autocomplete="off"
                                placeholder="Cari HP..."
                                wire:model.live.debounce.200ms="{{ $side }}Query"
                                class="mb-1.5 w-full rounded-[13px] border border-line bg-white px-3 py-2.5 font-sans text-[13.5px] font-medium text-ink"
                            />

                            @forelse ($suggestions[$side] as $suggestion)
                                <button
                                    type="button"
                                    wire:key="sugg-{{ $side }}-{{ $suggestion->id }}"
                                    wire:click="pick('{{ $side }}', '{{ $suggestion->id }}')"
                                    class="flex w-full cursor-pointer items-center gap-3 rounded-[13px] border-0 bg-transparent px-2.5 py-[9px] text-left hover:bg-surface"
                                >
                                    <span class="min-w-0 flex-1">
                                        <span class="block text-[14.5px] font-medium text-ink">{{ $suggestion->name }}</span>
                                        <span class="block text-[11.5px] text-muted">{{ $suggestion->brand }}</span>
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
    </x-page-hero>

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(34px,4.6vw,62px)]">
        <div class="rounded-3xl border border-line bg-surface p-[clamp(20px,2.6vw,32px)]">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <x-eyebrow>Putusan</x-eyebrow>
                    <div class="mt-1.5 font-display text-[clamp(19px,2.3vw,27px)] font-extrabold tracking-[-.028em]">{{ $this->duel->summary('unggul') }}</div>
                </div>

                <div class="flex items-center gap-2.5 font-mono text-[12.5px] text-muted">
                    <span>{{ $this->duel->leftWins }}</span>
                    <span class="flex h-[9px] w-[clamp(110px,22vw,200px)] overflow-hidden rounded-full bg-line">
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

            <div class="mt-[clamp(20px,2.4vw,28px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,260px),1fr))] gap-4">
                @foreach ([['phone' => $this->leftPhone, 'reasons' => $this->duel->leftReasons], ['phone' => $this->rightPhone, 'reasons' => $this->duel->rightReasons]] as $verdict)
                    <div wire:key="verdict-{{ $verdict['phone']->id }}" class="rounded-[18px] border border-line bg-white p-5">
                        <div class="text-[13px] font-semibold text-muted">Pilih {{ $verdict['phone']->name }} kalau</div>

                        <div class="mt-3.5 flex flex-col gap-2.5">
                            @foreach ($verdict['reasons'] as $reason)
                                <div wire:key="reason-{{ $verdict['phone']->id }}-{{ $loop->index }}" class="flex gap-2.5 text-sm leading-relaxed">
                                    <span class="flex-none font-mono text-[13px] text-win">+</span>
                                    <span>{{ $reason }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section id="duel-lengkap" class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(44px,5.6vw,80px)]">
        <x-section-heading eyebrow="Spesifikasi" title="Adu baris demi baris">
            <x-toggle-switch
                :on="$onlyDifferences"
                label="Hanya tampilkan perbedaan"
                wire:click="toggleOnlyDifferences"
            />
        </x-section-heading>

        <div class="mt-[clamp(20px,2.6vw,30px)] overflow-hidden rounded-[22px] border border-line">
            <table class="w-full table-fixed border-collapse">
                <caption class="sr-only">Perbandingan spesifikasi lengkap</caption>
                <colgroup>
                    <col class="w-[26%]" />
                    <col class="w-[37%]" />
                    <col class="w-[37%]" />
                </colgroup>
                <thead>
                    <tr class="bg-surface">
                        <th scope="col" class="border-b border-line px-2 py-3 pl-3 text-left font-mono text-[10.5px] font-normal tracking-[.12em] text-muted uppercase">Spesifikasi</th>
                        <th scope="col" class="border-b border-line px-2 py-3 text-left font-display text-[clamp(12.5px,2.6vw,14.5px)] font-extrabold tracking-[-.02em]">{{ $this->leftPhone->name }}</th>
                        <th scope="col" class="border-b border-line px-2 py-3 pr-3 text-left font-display text-[clamp(12.5px,2.6vw,14.5px)] font-extrabold tracking-[-.02em]">{{ $this->rightPhone->name }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->duel->groups as $group)
                        <tr wire:key="group-{{ $loop->index }}">
                            <th colspan="3" scope="colgroup" class="border-t border-b border-line bg-[#FBFCFE] p-3 text-left font-mono text-[10.5px] font-medium tracking-[.14em] text-brand uppercase">{{ $group->title }}</th>
                        </tr>

                        @foreach ($group->rows as $row)
                            <tr wire:key="row-{{ $row->key }}">
                                <th scope="row" class="border-t border-line-soft px-2 py-3 pl-3 text-left align-top font-sans text-[clamp(11px,2.3vw,13.5px)] font-medium text-muted">{{ $row->label }}</th>

                                <td @class([
                                    'border-t border-line-soft px-2.5 py-3.5 align-top font-mono text-[clamp(11px,2.3vw,13.5px)] leading-[1.45] tracking-[-.01em] break-words text-ink',
                                    'bg-win/7' => $row->leftWins,
                                ])>
                                    {{ $row->leftValue }}
                                    @if ($row->leftWins)
                                        <x-win-badge :delay="0.2 + $loop->index * 0.06" />
                                    @endif
                                </td>

                                <td @class([
                                    'border-t border-line-soft px-2.5 py-3.5 align-top font-mono text-[clamp(11px,2.3vw,13.5px)] leading-[1.45] tracking-[-.01em] break-words text-ink',
                                    'bg-win/7' => $row->rightWins,
                                ])>
                                    {{ $row->rightValue }}
                                    @if ($row->rightWins)
                                        <x-win-badge :delay="0.2 + $loop->index * 0.06" />
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-3 text-[11.5px] text-muted">Spesifikasi dari sumber resmi pabrikan. Skor dihitung dari performa, layar, kamera, baterai, dan harga.</div>
    </section>

    <section id="harga" class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(44px,5.6vw,80px)]">
        <x-eyebrow>Harga hari ini</x-eyebrow>
        <h2 class="mt-2.5 font-display text-[clamp(24px,3vw,36px)] font-extrabold tracking-[-.028em]">Beli di mana paling murah</h2>

        <div class="mt-[clamp(20px,2.6vw,30px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,270px),1fr))] gap-[18px]">
            @foreach ($this->priceCards as $card)
                <div wire:key="price-{{ $card['phone']->id }}" class="overflow-hidden rounded-[20px] border border-line">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line bg-surface px-[18px] py-4">
                        <div class="font-display text-base font-extrabold tracking-[-.02em]">{{ $card['phone']->name }}</div>
                        <div class="font-mono text-xs text-muted">mulai {{ $card['lowest'] }}</div>
                    </div>

                    <div class="flex flex-col">
                        @foreach ($card['sellers'] as $seller)
                            <x-seller-row
                                wire:key="seller-{{ $card['phone']->id }}-{{ $loop->index }}"
                                :seller="$seller"
                                :compact="true"
                            />
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <x-affiliate-note class="mt-3.5" />
    </section>

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(44px,5.6vw,80px)]">
        <x-eyebrow>Lanjutkan</x-eyebrow>
        <h2 class="mt-2.5 font-display text-[clamp(24px,3vw,36px)] font-extrabold tracking-[-.028em]">Duel lain yang sering dicari</h2>

        <div class="mt-[clamp(18px,2.4vw,26px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,230px),1fr))] gap-3.5">
            @foreach ($related as $matchup)
                <button
                    type="button"
                    wire:key="related-{{ $loop->index }}"
                    wire:click="setMatchup('{{ $matchup['left'] }}', '{{ $matchup['right'] }}')"
                    class="cursor-pointer rounded-[18px] border border-line bg-surface p-5 text-left font-sans transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_16px_30px_-22px_rgba(12,20,36,.5)]"
                >
                    <span class="block font-display text-base leading-[1.3] font-extrabold tracking-[-.02em]">{{ $matchup['label'] }}</span>
                    <span class="mt-2.5 block text-[12.5px] font-semibold text-brand">Lihat duel →</span>
                </button>
            @endforeach
        </div>
    </section>

    <x-site-footer />
</div>
