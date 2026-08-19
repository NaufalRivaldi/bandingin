<div class="overflow-x-hidden bg-white">
    <x-site-nav />

    <x-page-hero
        width="narrow"
        title="Sumber data harga"
        lead="Dari mana angka harga di situs ini berasal, seberapa sering diperbarui, dan bagaimana kami menghasilkan pendapatan."
        :items="[['label' => 'Sumber data harga']]"
    />

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(30px,4vw,56px)]">
        <h2 class="font-display text-[clamp(21px,2.5vw,29px)] font-extrabold tracking-[-.028em]">Sumber yang kami pantau</h2>

        <div class="mt-5 overflow-hidden rounded-[20px] border border-line">
            @foreach ($sources as $source)
                <div wire:key="source-{{ $loop->index }}" class="flex flex-wrap items-center gap-3.5 border-b border-line-soft px-[18px] py-4">
                    <span class="min-w-0 flex-[1_1_180px]">
                        <span class="block text-[15px] font-semibold">{{ $source['name'] }}</span>
                        <span class="mt-1 block text-[12.5px] text-muted">{{ $source['note'] }}</span>
                    </span>

                    <span class="font-mono text-xs whitespace-nowrap text-muted">{{ $source['freq'] }}</span>

                    @if ($source['tag'])
                        <span class="inline-flex items-center rounded-full border border-brand/22 bg-brand/8 px-2.5 py-1 text-[11px] font-semibold whitespace-nowrap text-brand">{{ $source['tag'] }}</span>
                    @endif
                </div>
            @endforeach
        </div>
    </section>

    <section class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(34px,4.4vw,62px)]">
        <h2 class="font-display text-[clamp(21px,2.5vw,29px)] font-extrabold tracking-[-.028em]">Aturan yang kami pakai</h2>

        <div class="mt-5 flex flex-col gap-3.5">
            @foreach ($rules as $rule)
                <div wire:key="rule-{{ $loop->index }}" class="rounded-[18px] border border-line bg-surface p-5">
                    <div class="font-display text-base font-extrabold tracking-[-.02em]">{{ $rule['title'] }}</div>
                    <div class="mt-[7px] text-sm leading-relaxed text-muted">{{ $rule['text'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    <section id="afiliasi" class="mx-auto max-w-[820px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(34px,4.4vw,62px)]">
        <h2 class="font-display text-[clamp(21px,2.5vw,29px)] font-extrabold tracking-[-.028em]">Disclosure afiliasi</h2>

        <p class="mt-[18px] text-[15px] leading-[1.7] text-ink">
            Sebagian tautan “Cek Harga” di situs ini adalah tautan afiliasi. Kalau kamu membeli lewat tautan itu, kami menerima komisi kecil dari marketplace, dan harga yang kamu bayar tetap sama.
        </p>

        <p class="mt-3.5 text-[15px] leading-[1.7] text-ink">
            Komisi tidak memengaruhi urutan toko, skor HP, maupun isi ulasan. Urutan toko selalu diurutkan dari harga terendah. Metodologi penilaian dijelaskan di <a href="{{ route('cara-menilai') }}" class="text-brand hover:text-brand-dark">cara kami menilai</a>.
        </p>

        <div class="mt-[22px] rounded-[18px] border border-line bg-surface p-5 text-sm leading-relaxed text-muted">
            Menemukan harga yang tidak sesuai dengan yang tampil di toko? Kirim tautan dan tangkapan layarnya lewat <a href="{{ route('lapor') }}" class="text-brand hover:text-brand-dark">lapor harga salah</a>. Kami perbaiki dalam 24 jam.
        </div>
    </section>

    <x-site-footer />
</div>
