<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cara kami menilai — Bandingin.id')]
class CaraMenilaiPage extends Component
{
    /** Highest weight in the list; the bars are drawn relative to it. */
    private const int MAX_WEIGHT = 25;

    public function render(): View
    {
        return view('livewire.cara-menilai-page', [
            'weights' => self::weights(),
            'maxWeight' => self::MAX_WEIGHT,
            'steps' => self::steps(),
            'exclusions' => self::exclusions(),
        ]);
    }

    /** @return list<array{label: string, pct: int, desc: string}> */
    private static function weights(): array
    {
        return [
            ['label' => 'Performa', 'pct' => 25, 'desc' => 'Skor benchmark, kelas chipset, kapasitas dan kecepatan RAM serta penyimpanan. Kami pakai rata-rata tiga kali pengujian pada suhu ruangan.'],
            ['label' => 'Layar', 'pct' => 20, 'desc' => 'Jenis panel, resolusi, refresh rate, dan kecerahan puncak terukur, bukan angka klaim pabrikan.'],
            ['label' => 'Kamera', 'pct' => 20, 'desc' => 'Hasil foto siang dan malam, stabilisasi, serta kualitas rekaman video. Resolusi sensor bukan penentu utama.'],
            ['label' => 'Baterai', 'pct' => 20, 'desc' => 'Ketahanan pemakaian campuran selama satu hari penuh, ditambah kecepatan pengisian sampai 100 persen.'],
            ['label' => 'Nilai per rupiah', 'pct' => 15, 'desc' => 'Skor empat kategori di atas dibagi harga pasar terendah hari itu. Karena itu skor bisa berubah saat harga turun.'],
        ];
    }

    /** @return list<array{n: string, title: string, text: string}> */
    private static function steps(): array
    {
        return [
            ['n' => '1', 'title' => 'Kumpulkan data resmi', 'text' => 'Spesifikasi ditarik dari situs resmi pabrikan dan lembar data distributor Indonesia, bukan dari agregator pihak ketiga.'],
            ['n' => '2', 'title' => 'Uji unit ritel', 'text' => 'Kami membeli unit dari toko biasa, bukan unit ulasan kiriman merek. Pengujian dilakukan setelah semua pembaruan sistem dipasang.'],
            ['n' => '3', 'title' => 'Hitung skor kategori', 'text' => 'Setiap hasil diubah ke skala 0–100 relatif terhadap HP lain di rentang harga yang sama, bukan terhadap seluruh pasar.'],
            ['n' => '4', 'title' => 'Perbarui saat harga berubah', 'text' => 'Skor nilai per rupiah dihitung ulang otomatis dua kali sehari mengikuti harga pasar terendah.'],
        ];
    }

    /** @return list<string> */
    private static function exclusions(): array
    {
        return [
            'Kerja sama iklan atau penempatan berbayar dari merek mana pun',
            'Komisi afiliasi dari tautan pembelian di halaman ini',
            'Unit pinjaman atau perjalanan peluncuran yang dibiayai merek',
            'Popularitas merek dan jumlah pencarian di mesin pencari',
        ];
    }
}
