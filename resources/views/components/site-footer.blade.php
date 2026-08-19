@php
    $columns = [
        'Tentang' => [
            ['label' => 'Cara kami menilai', 'route' => 'cara-menilai'],
            ['label' => 'Sumber data harga', 'route' => 'sumber-harga'],
            ['label' => 'Syarat & ketentuan', 'route' => 'legal'],
        ],
        'Bantuan' => [
            ['label' => 'Cara pakai Kartu Duel', 'route' => 'faq'],
            ['label' => 'Lapor harga salah', 'route' => 'lapor'],
            ['label' => 'Usul HP baru', 'route' => 'lapor'],
            ['label' => 'FAQ', 'route' => 'faq'],
        ],
        'Jelajahi' => [
            ['label' => 'Katalog HP', 'route' => 'katalog'],
            ['label' => 'Bandingkan HP', 'route' => 'bandingkan'],
            ['label' => 'Merek', 'route' => 'merek'],
            ['label' => 'Berita & ulasan', 'route' => 'berita'],
        ],
    ];
@endphp

<div>
    <section class="mt-[clamp(44px,6.5vw,90px)] border-y border-line bg-surface">
        <div class="mx-auto flex max-w-[1140px] flex-wrap items-center gap-x-[clamp(14px,3vw,44px)] gap-y-2.5 px-[clamp(16px,2.4vw,24px)] py-4 text-[12.5px] text-muted">
            <span>Harga diperbarui setiap hari</span>
            <span>Spesifikasi dari sumber resmi pabrikan</span>
            <span>Tanpa ulasan berbayar</span>
        </div>
    </section>

    <footer class="mx-auto max-w-[1140px] px-[clamp(16px,2.4vw,24px)] pt-[clamp(32px,5vw,72px)] pb-[clamp(24px,3vw,40px)]">
        <div class="grid grid-cols-1 gap-[clamp(24px,3vw,40px)] sm:grid-cols-[repeat(auto-fit,minmax(min(100%,150px),1fr))]">
            @foreach ($columns as $heading => $items)
                <div>
                    <div class="font-mono text-[10.5px] tracking-[.14em] text-muted uppercase">{{ $heading }}</div>
                    <div class="mt-3.5 flex flex-col gap-3 text-sm">
                        @foreach ($items as $item)
                            <a href="{{ route($item['route']) }}" class="text-ink hover:text-brand">{{ $item['label'] }}</a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <livewire:newsletter-subscribe />
        </div>

        <div class="mt-[clamp(28px,4vw,52px)] flex flex-wrap items-center justify-between gap-3.5 border-t border-line pt-5 text-[12.5px] text-muted">
            <div class="flex items-center gap-[9px]">
                <span class="grid size-[22px] place-items-center rounded-[7px] bg-brand font-mono text-[9px] font-semibold text-white">VS</span>
                © {{ now()->year }} Bandingin.id
            </div>
            <div class="flex flex-wrap items-center gap-x-[18px] gap-y-2.5">
                <a href="{{ route('legal') }}" class="text-muted hover:text-brand">Kebijakan Privasi</a>
                <a href="{{ route('sumber-harga') }}#afiliasi" class="text-muted hover:text-brand">Disclosure Afiliasi</a>
                <a href="{{ route('lapor') }}" class="text-muted hover:text-brand">Kontak</a>
            </div>
        </div>
    </footer>
</div>
