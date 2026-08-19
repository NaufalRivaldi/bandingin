<div class="overflow-x-hidden bg-white">
    <x-site-nav />

    <x-page-hero
        width="narrow"
        title="Pusat bantuan"
        lead="Cara memakai Kartu Duel, dari mana angka harga berasal, dan hal lain yang paling sering ditanyakan."
        :items="[['label' => 'Bantuan']]"
    />

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(30px,4vw,52px)]">
        <h2 class="font-display text-[clamp(21px,2.5vw,29px)] font-extrabold tracking-[-.028em]">Cara pakai Kartu Duel</h2>

        <div class="mt-[22px] grid grid-cols-[repeat(auto-fit,minmax(min(100%,210px),1fr))] gap-3.5">
            @foreach ($steps as $step)
                <div wire:key="step-{{ $step['n'] }}" class="rounded-[20px] border border-line bg-surface p-5">
                    <span class="grid size-[30px] place-items-center rounded-[10px] bg-brand font-mono text-[13px] font-semibold text-white">{{ $step['n'] }}</span>
                    <div class="mt-3.5 font-display text-[15.5px] font-extrabold tracking-[-.02em]">{{ $step['title'] }}</div>
                    <div class="mt-[7px] text-[13.5px] leading-relaxed text-muted">{{ $step['text'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(34px,4.4vw,62px)]">
        <div class="flex flex-wrap items-center justify-between gap-3.5">
            <h2 class="font-display text-[clamp(21px,2.5vw,29px)] font-extrabold tracking-[-.028em]">Pertanyaan umum</h2>

            <div class="flex flex-wrap gap-2">
                @foreach ($topics as $topicOption)
                    <x-chip
                        wire:key="topic-{{ $loop->index }}"
                        tone="soft"
                        :active="$topic === $topicOption"
                        wire:click="selectTopic('{{ $topicOption }}')"
                    >{{ $topicOption }}</x-chip>
                @endforeach
            </div>
        </div>

        <div class="mt-5 overflow-hidden rounded-[20px] border border-line">
            @foreach ($this->faqs as $faq)
                @php($isOpen = $open === $loop->index)

                <div wire:key="faq-{{ $topic }}-{{ $loop->index }}" class="border-b border-line-soft">
                    <button
                        type="button"
                        wire:click="toggle({{ $loop->index }})"
                        aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                        class="flex w-full cursor-pointer items-center gap-3.5 border-0 bg-transparent p-[18px] text-left"
                    >
                        <span class="flex-1 font-display text-[15.5px] font-extrabold tracking-[-.02em] text-ink">{{ $faq['question'] }}</span>
                        <span
                            @class([
                                'grid size-[26px] flex-none place-items-center rounded-[9px] border border-line bg-surface font-mono text-sm text-brand transition-transform duration-200',
                                'rotate-45' => $isOpen,
                            ])
                        >+</span>
                    </button>

                    @if ($isOpen)
                        <div class="max-w-[640px] px-[18px] pb-5 text-[14.5px] leading-[1.7] text-muted">{{ $faq['answer'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-[20px] border border-line bg-surface p-[22px]">
            <div>
                <div class="font-display text-base font-extrabold tracking-[-.02em]">Belum terjawab?</div>
                <div class="mt-1.5 text-sm text-muted">Kirim pertanyaan atau koreksi data ke tim kami.</div>
            </div>

            <a
                href="{{ route('lapor') }}"
                class="rounded-xl bg-brand px-5 py-[13px] text-sm font-semibold text-white transition-colors hover:bg-brand-dark hover:text-white"
            >Hubungi kami</a>
        </div>
    </section>

    <x-site-footer />
</div>
