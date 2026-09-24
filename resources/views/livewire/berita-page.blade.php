<div class="bg-white">
    <x-site-nav active="berita" />

    <header class="border-b border-line bg-[linear-gradient(180deg,#F4F6FB,#FFFFFF)]">
        <div class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(28px,4vw,56px)] pb-[clamp(26px,3vw,40px)]">
            <x-breadcrumb :items="[['label' => 'Berita']]" />

            <h1 class="anim-fade-up mt-4 font-display text-[clamp(34px,5.6vw,68px)] leading-none font-extrabold tracking-[-.032em]">
                Berita &amp; Ulasan
            </h1>

            <p class="mt-4 max-w-[560px] text-[clamp(14.5px,1.2vw,16.5px)] leading-[1.65] text-muted">
                Rilis baru, harga turun, dan panduan beli yang ditulis setelah kami adu spesifikasinya sendiri. Tanpa ulasan berbayar.
            </p>

            <div class="mt-[clamp(22px,2.6vw,34px)] flex flex-wrap items-center gap-2">
                @foreach ($categories as $option)
                    <x-chip
                        wire:key="cat-{{ $option }}"
                        tone="solid"
                        :active="$option === $category"
                        wire:click="setCategory('{{ $option }}')"
                    >{{ $option }}</x-chip>
                @endforeach
            </div>
        </div>
    </header>

    @if ($this->showsFeatured)
        <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(32px,4vw,56px)]">
            <article class="grid grid-cols-[repeat(auto-fit,minmax(min(100%,300px),1fr))] items-center gap-[clamp(24px,3.4vw,48px)]">
                <a href="{{ route('berita.show', $this->featured->slug) }}" class="block w-full">
                    @if ($this->featured->image_url)
                        <img
                            src="{{ $this->featured->image_url }}"
                            alt="{{ $this->featured->title }}"
                            title="{{ $this->featured->title }}"
                            referrerpolicy="no-referrer"
                            class="aspect-16/10 w-full rounded-[22px] object-cover"
                        >
                    @else
                        <span class="photo-slot relative block aspect-16/10 rounded-[22px]">
                            <span class="absolute inset-0 grid place-items-center font-mono text-[10.5px] tracking-[.14em] text-faint uppercase">Foto artikel</span>
                        </span>
                    @endif
                </a>

                <div>
                    <div class="flex items-center gap-2.5 font-mono text-[10.5px] tracking-[.14em] uppercase">
                        <span class="text-brand">{{ $this->featured->category->value }}</span>
                        <span class="text-[#c3cee3]">·</span>
                        <span class="text-muted">Sorotan</span>
                    </div>

                    <h2 class="mt-3 font-display text-[clamp(24px,3vw,40px)] leading-[1.12] font-extrabold tracking-[-.028em]">
                        <a href="{{ route('berita.show', $this->featured->slug) }}" class="text-ink hover:text-brand">{{ $this->featured->title }}</a>
                    </h2>

                    <p class="mt-4 max-w-[520px] text-[clamp(14.5px,1.2vw,16.5px)] leading-[1.7] text-muted">{{ $this->featured->excerpt }}</p>

                    <div class="mt-[18px] flex items-center gap-3 font-mono text-[11.5px] text-muted">
                        <span>{{ $this->featured->dateLong() }}</span>
                        <span class="text-[#c3cee3]">·</span>
                        <span>{{ $this->featured->read_minutes }} menit baca</span>
                    </div>
                </div>
            </article>
        </section>
    @endif

    <section class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(40px,5vw,72px)]">
        <div class="flex flex-wrap items-baseline justify-between gap-3.5 border-b border-line pb-[18px]">
            <h2 class="font-display text-[clamp(20px,2.2vw,26px)] font-extrabold tracking-[-.026em]">{{ $this->listHeading }}</h2>
            <span class="font-mono text-[11.5px] text-muted">{{ $this->total }} artikel</span>
        </div>

        @if ($this->total > 0)
            <div class="mt-[clamp(24px,3vw,36px)] grid grid-cols-[repeat(auto-fit,minmax(min(100%,280px),1fr))] gap-[clamp(18px,2.2vw,28px)]">
                @foreach ($this->articles as $article)
                    <x-article-card wire:key="article-{{ $article->id }}" :article="$article" />
                @endforeach
            </div>
        @else
            <div class="mt-6 rounded-[20px] border border-dashed border-line-strong px-[clamp(16px,2.4vw,24px)] py-[clamp(34px,5vw,60px)] text-center">
                <div class="font-display text-[19px] font-extrabold tracking-[-.02em]">Belum ada artikel</div>

                <div class="mx-auto mt-2.5 max-w-[380px] text-sm leading-[1.55] text-muted">
                    {{ $this->emptyMessage }}
                </div>
            </div>
        @endif

        @if ($this->hasMore)
            <div class="mt-[clamp(36px,4vw,56px)] flex items-center justify-center gap-2.5">
                <button
                    type="button"
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    class="cursor-pointer rounded-xl border border-line bg-white px-[18px] py-3 font-sans text-sm font-semibold text-ink hover:border-line-strong disabled:opacity-60"
                >
                    <span wire:loading.remove wire:target="loadMore">Muat lebih banyak</span>
                    <span wire:loading wire:target="loadMore">Memuat…</span>
                </button>
            </div>
        @endif
    </section>

    <section class="mx-auto mt-[clamp(56px,7vw,96px)] max-w-[1140px] px-[clamp(16px,2.4vw,24px)]">
        <div class="rounded-3xl border border-line bg-surface p-[clamp(24px,3vw,38px)]">
            <div class="mx-auto max-w-[560px]">
                <livewire:newsletter-subscribe />
            </div>
        </div>

        <div class="mt-2.5 text-[11.5px] text-muted">Kami tidak pernah menjual alamat emailmu.</div>
    </section>

    <x-site-footer />
</div>
