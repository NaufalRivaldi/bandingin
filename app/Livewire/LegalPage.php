<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Legal — Bandingin.id')]
class LegalPage extends Component
{
    #[Url(as: 'dok')]
    public string $document = 'privasi';

    public const string EFFECTIVE_FROM = 'Berlaku sejak 1 Agustus 2026';

    /** @var array<string, string> */
    private const array LABELS = [
        'privasi' => 'Kebijakan Privasi',
        'syarat' => 'Syarat & Ketentuan',
    ];

    public function selectDocument(string $document): void
    {
        $this->document = $document;
    }

    #[Computed]
    public function label(): string
    {
        return self::LABELS[$this->document] ?? self::LABELS['privasi'];
    }

    /** @return list<array{title: string, body: string}> */
    #[Computed]
    public function sections(): array
    {
        $sections = $this->document === 'syarat' ? self::termsSections() : self::privacySections();

        return array_map(
            static fn (array $row): array => ['title' => $row[0], 'body' => $row[1]],
            $sections,
        );
    }

    public function render(): View
    {
        return view('livewire.legal-page', [
            'labels' => self::LABELS,
            'effectiveFrom' => self::EFFECTIVE_FROM,
        ]);
    }

    /** @return list<array{0: string, 1: string}> */
    private static function privacySections(): array
    {
        return [
            ['Data yang kami kumpulkan', 'Kami menyimpan alamat email hanya kalau kamu mendaftar buletin atau memasang notifikasi harga. Selain itu kami mencatat data kunjungan agregat: halaman yang dibuka, jenis perangkat, dan sumber rujukan, tanpa mengaitkannya dengan identitas pribadi.'],
            ['Cara data dipakai', 'Email dipakai untuk mengirim pemberitahuan yang kamu minta dan ringkasan mingguan. Data kunjungan agregat dipakai untuk menentukan HP mana yang perlu diuji lebih dulu dan bagian mana dari situs yang perlu diperbaiki.'],
            ['Cookie', 'Kami memakai cookie untuk mengingat preferensi tampilan, misalnya pilihan “hanya tampilkan perbedaan” pada Kartu Duel, dan untuk pengukuran kunjungan. Cookie afiliasi dipasang oleh marketplace saat kamu mengeklik tautan “Cek Harga”, dan tunduk pada kebijakan masing-masing marketplace.'],
            ['Berbagi data', 'Kami tidak menjual atau menukar data pembaca. Data hanya dibagikan kepada penyedia layanan yang kami pakai untuk mengirim email dan mengukur kunjungan, sebatas yang diperlukan untuk menjalankan layanan itu.'],
            ['Hak kamu', 'Kamu bisa berhenti berlangganan lewat tautan di setiap email, dan meminta penghapusan data dengan mengirim permintaan ke redaksi@bandingin.id. Permintaan penghapusan diproses dalam tujuh hari kerja.'],
            ['Retensi', 'Alamat email disimpan selama langganan aktif dan dihapus dalam 30 hari setelah kamu berhenti. Data kunjungan agregat disimpan maksimal 24 bulan.'],
        ];
    }

    /** @return list<array{0: string, 1: string}> */
    private static function termsSections(): array
    {
        return [
            ['Sifat informasi', 'Harga dan spesifikasi di situs ini bersifat informatif dan bisa berubah sewaktu-waktu. Angka yang tampil adalah hasil penarikan terakhir dari toko, bukan penawaran yang mengikat. Selalu periksa harga akhir di halaman toko sebelum membeli.'],
            ['Transaksi dengan toko', 'Bandingin tidak menjual perangkat dan tidak menjadi pihak dalam transaksi antara kamu dan marketplace atau toko. Urusan pembayaran, pengiriman, retur, dan garansi mengikuti ketentuan penjual masing-masing.'],
            ['Tautan afiliasi', 'Sebagian tautan pembelian adalah tautan afiliasi yang memberi kami komisi tanpa menambah harga yang kamu bayar. Komisi tidak memengaruhi skor maupun urutan toko. Rinciannya ada di halaman sumber data harga.'],
            ['Hak cipta', 'Teks, tabel perbandingan, dan hasil pengujian di situs ini adalah milik Bandingin. Kutipan singkat diperbolehkan dengan mencantumkan tautan sumber. Penyalinan tabel atau basis data secara utuh memerlukan izin tertulis.'],
            ['Batas tanggung jawab', 'Kami berusaha menjaga akurasi data, tapi tidak menjamin situs bebas dari kekeliruan. Keputusan pembelian sepenuhnya ada di tanganmu. Laporkan kesalahan lewat halaman lapor agar bisa segera diperbaiki.'],
            ['Perubahan ketentuan', 'Ketentuan ini bisa diperbarui sewaktu-waktu. Perubahan yang berdampak besar akan diumumkan di halaman ini dengan tanggal berlaku yang baru.'],
        ];
    }
}
