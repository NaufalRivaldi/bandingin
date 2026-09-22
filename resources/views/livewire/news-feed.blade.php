<div>
    @if ($this->items->isNotEmpty())
        <section class="mx-auto mt-[clamp(56px,7vw,96px)] max-w-[1140px] px-[clamp(16px,2.4vw,24px)]">
            <div class="flex flex-wrap items-baseline justify-between gap-3.5 border-b border-line pb-[18px]">
                <h2 class="font-display text-[clamp(20px,2.2vw,26px)] font-extrabold tracking-[-.026em]">Kabar Teknologi</h2>
                <span class="font-mono text-[11.5px] text-muted">Dari tek.id</span>
            </div>

            <p class="mt-3.5 max-w-[560px] text-sm leading-[1.65] text-muted">
                Berita teknologi pilihan dari media lain. Klik untuk membaca di situs aslinya.
            </p>

            <div class="mt-[clamp(24px,3vw,36px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,280px),1fr))] gap-[clamp(18px,2.2vw,28px)]">
                @foreach ($this->items as $item)
                    <x-news-card wire:key="news-{{ $item->id }}" :item="$item" />
                @endforeach
            </div>
        </section>
    @endif
</div>
