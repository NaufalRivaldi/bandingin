@php
    $links = [
        ['href' => route('katalog'), 'title' => 'Katalog HP', 'text' => 'Semua HP dengan filter harga dan spesifikasi'],
        ['href' => route('bandingkan'), 'title' => 'Kartu Duel', 'text' => 'Adu dua HP baris demi baris'],
        ['href' => route('merek'), 'title' => 'Merek', 'text' => 'Telusuri HP per merek yang dijual di Indonesia'],
        ['href' => route('berita'), 'title' => 'Berita & ulasan', 'text' => 'Kabar terbaru dari pasar HP Indonesia'],
    ];
@endphp

{{-- The app layout lives in `layouts/`, not `components/`, so `<x-layouts.app>` cannot resolve it. --}}
@component('layouts.app', ['title' => 'Halaman tidak ditemukan — Bandingin.id'])
    <div class="flex min-h-screen flex-col bg-white">
        <x-site-nav />

        <div class="flex-1 bg-[linear-gradient(180deg,#DBE6FB_0%,#EDF3FD_55%,#FFFFFF_100%)]">
            <div class="mx-auto max-w-[720px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(48px,8vw,110px)] pb-[clamp(40px,6vw,80px)] text-center">
                <div class="anim-fade-up inline-grid size-[74px] place-items-center rounded-3xl border border-line bg-white font-mono text-[19px] font-semibold text-brand shadow-[0_18px_40px_-26px_rgba(12,20,36,.5)]">404</div>

                <h1 class="mt-[clamp(20px,2.6vw,28px)] font-display text-[clamp(28px,4.6vw,50px)] leading-[1.05] font-extrabold tracking-[-.035em] text-balance">
                    Halaman ini tidak ditemukan
                </h1>

                <p class="mx-auto mt-3.5 max-w-[460px] text-[clamp(15px,1.4vw,17px)] leading-[1.65] text-muted">
                    Mungkin HP-nya sudah tidak dijual, atau tautannya salah ketik. Coba cari langsung di bawah ini.
                </p>

                <form action="{{ route('katalog') }}" method="get" class="mx-auto mt-[clamp(22px,3vw,32px)] flex max-w-[460px] flex-wrap gap-2.5">
                    <label for="cari404" class="sr-only">Cari HP</label>

                    <input
                        id="cari404"
                        type="text"
                        name="q"
                        placeholder="Cari nama HP atau merek..."
                        class="min-w-0 flex-[1_1_220px] rounded-[14px] border border-line bg-white px-4 py-[15px] font-sans text-[15px] text-ink shadow-[0_10px_26px_-16px_rgba(12,20,36,.4)]"
                    />

                    <button
                        type="submit"
                        class="flex-none cursor-pointer rounded-[14px] bg-brand px-[22px] py-[15px] font-sans text-[15px] font-semibold text-white hover:bg-brand-dark"
                    >Cari</button>
                </form>

                <div class="mt-[clamp(28px,3.6vw,44px)] grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-3 text-left">
                    @foreach ($links as $link)
                        <a
                            href="{{ $link['href'] }}"
                            class="block rounded-[18px] border border-line bg-white p-[18px] text-ink transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:text-ink hover:shadow-[0_16px_30px_-22px_rgba(12,20,36,.5)]"
                        >
                            <span class="block font-display text-[15.5px] font-extrabold tracking-[-.02em]">{{ $link['title'] }}</span>
                            <span class="mt-1.5 block text-[13px] leading-[1.5] text-muted">{{ $link['text'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <x-site-footer />
    </div>
@endcomponent
