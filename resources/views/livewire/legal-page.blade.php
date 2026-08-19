<div class="overflow-x-hidden bg-white">
    <x-site-nav />

    <x-page-hero width="narrow" :title="$this->label" :items="[['label' => $this->label]]">
        <div class="mt-3 font-mono text-[11.5px] text-muted">{{ $effectiveFrom }}</div>

        <div class="mt-[clamp(18px,2.4vw,26px)] flex flex-wrap gap-2">
            @foreach ($labels as $key => $label)
                <x-chip
                    wire:key="doc-{{ $key }}"
                    :active="$document === $key"
                    wire:click="selectDocument('{{ $key }}')"
                >{{ $label }}</x-chip>
            @endforeach
        </div>
    </x-page-hero>

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(28px,3.6vw,48px)]">
        <div class="flex flex-col gap-[clamp(24px,3vw,36px)]">
            @foreach ($this->sections as $section)
                <div wire:key="section-{{ $document }}-{{ $loop->index }}">
                    <h2 class="font-display text-[clamp(18px,2.1vw,23px)] font-extrabold tracking-[-.025em]">{{ $section['title'] }}</h2>
                    <p class="mt-2.5 text-[15px] leading-[1.75] text-muted">{{ $section['body'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="mt-[clamp(28px,3.4vw,44px)] rounded-[20px] border border-line bg-surface p-[22px] text-sm leading-relaxed text-muted">
            Pertanyaan soal halaman ini bisa dikirim ke redaksi@bandingin.id, atau lewat <a href="{{ route('lapor') }}" class="text-brand hover:text-brand-dark">halaman masukan</a>.
        </div>
    </section>

    <x-site-footer />
</div>
