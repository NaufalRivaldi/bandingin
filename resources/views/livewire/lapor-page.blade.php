@php($fieldClass = 'mt-2 w-full rounded-xl border border-line bg-white px-3.5 py-[13px] font-sans text-sm text-ink placeholder:text-faint')

<div class="overflow-x-hidden bg-white">
    <x-site-nav />

    <x-page-hero
        width="narrow"
        title="Bantu kami akurat"
        lead="Laporkan harga yang tidak sesuai, usulkan HP yang belum ada di katalog, atau kirim koreksi spesifikasi."
        :items="[['label' => 'Kirim masukan']]"
    >
        <div class="mt-[clamp(18px,2.4vw,26px)] flex flex-wrap gap-2">
            @foreach ($types as $tab)
                <x-chip
                    wire:key="tab-{{ $tab->value }}"
                    :active="$type === $tab"
                    wire:click="selectType('{{ $tab->value }}')"
                >{{ $tab->tabLabel() }}</x-chip>
            @endforeach
        </div>
    </x-page-hero>

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(26px,3.4vw,44px)]">
        @if ($sent)
            <div class="rounded-[20px] border border-win/28 bg-win/7 p-[clamp(26px,3.4vw,40px)] text-center">
                <div class="font-display text-xl font-extrabold tracking-[-.025em]">Terima kasih, laporannya masuk</div>
                <div class="mx-auto mt-2.5 max-w-[420px] text-[14.5px] leading-relaxed text-win-ink">{{ $type->confirmation() }}</div>

                <button
                    type="button"
                    wire:click="backToForm"
                    class="mt-[18px] cursor-pointer rounded-xl border border-line bg-white px-5 py-3 font-sans text-sm font-semibold text-ink transition-colors hover:border-brand hover:text-brand"
                >Kirim laporan lain</button>
            </div>
        @else
            <form wire:submit="save" class="rounded-[22px] border border-line p-[clamp(20px,2.6vw,32px)]">
                <div class="font-display text-[clamp(18px,2.1vw,23px)] font-extrabold tracking-[-.025em]">{{ $type->formTitle() }}</div>
                <div class="mt-2 text-sm leading-relaxed text-muted">{{ $type->formNote() }}</div>

                <div class="mt-6 grid grid-cols-[repeat(auto-fit,minmax(min(100%,210px),1fr))] gap-4">
                    <div>
                        <label for="f-subject" class="block text-[13px] font-semibold">{{ $type->subjectLabel() }}</label>
                        <input
                            id="f-subject"
                            type="text"
                            wire:model="form.subject"
                            placeholder="{{ $type->subjectPlaceholder() }}"
                            class="{{ $fieldClass }}"
                        />
                        @error('form.subject')
                            <div class="mt-1.5 text-xs text-lose">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="f-email" class="block text-[13px] font-semibold">Email kamu</label>
                        <input
                            id="f-email"
                            type="email"
                            wire:model="form.email"
                            placeholder="nama@email.com"
                            class="{{ $fieldClass }}"
                        />
                        @error('form.email')
                            <div class="mt-1.5 text-xs text-lose">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                @if ($type->requiresStoreDetails())
                    <div class="mt-4 grid grid-cols-[repeat(auto-fit,minmax(min(100%,210px),1fr))] gap-4">
                        <div>
                            <label for="f-store" class="block text-[13px] font-semibold">Toko</label>
                            <select id="f-store" wire:model="form.store" class="{{ $fieldClass }}">
                                @foreach ($stores as $store)
                                    <option wire:key="store-{{ $loop->index }}" value="{{ $store }}">{{ $store }}</option>
                                @endforeach
                            </select>
                            @error('form.store')
                                <div class="mt-1.5 text-xs text-lose">{{ $message }}</div>
                            @enderror
                        </div>

                        <div>
                            <label for="f-price" class="block text-[13px] font-semibold">Harga yang benar</label>
                            <input
                                id="f-price"
                                type="text"
                                wire:model="form.claimedPrice"
                                placeholder="Rp3.799.000"
                                class="mt-2 w-full rounded-xl border border-line bg-white px-3.5 py-[13px] font-mono text-sm text-ink placeholder:text-faint"
                            />
                            @error('form.claimedPrice')
                                <div class="mt-1.5 text-xs text-lose">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="f-link" class="block text-[13px] font-semibold">Tautan halaman produk</label>
                        <input
                            id="f-link"
                            type="url"
                            wire:model="form.productUrl"
                            placeholder="https://..."
                            class="{{ $fieldClass }}"
                        />
                        @error('form.productUrl')
                            <div class="mt-1.5 text-xs text-lose">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <div class="mt-4">
                    <label for="f-note" class="block text-[13px] font-semibold">Catatan tambahan</label>
                    <textarea
                        id="f-note"
                        rows="4"
                        wire:model="form.note"
                        placeholder="{{ $type->notePlaceholder() }}"
                        class="{{ $fieldClass }} resize-y leading-relaxed"
                    ></textarea>
                    @error('form.note')
                        <div class="mt-1.5 text-xs text-lose">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mt-[22px] flex flex-wrap items-center justify-between gap-3.5">
                    <div class="max-w-[380px] text-xs text-muted">Kami hanya memakai emailmu untuk mengabari hasil tinjauan. Tidak ada kiriman promosi.</div>

                    <button
                        type="submit"
                        class="cursor-pointer rounded-xl border-0 bg-brand px-[clamp(16px,2.4vw,24px)] py-3.5 font-sans text-[15px] font-semibold text-white transition-colors hover:bg-brand-dark"
                    >Kirim laporan</button>
                </div>
            </form>
        @endif

        <div class="mt-[clamp(26px,3.4vw,40px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,210px),1fr))] gap-3.5">
            @foreach ($slas as $sla)
                <x-stat-card wire:key="sla-{{ $loop->index }}" :label="$sla['label']" :value="$sla['value']" />
            @endforeach
        </div>
    </section>

    <x-site-footer />
</div>
