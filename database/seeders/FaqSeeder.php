<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\FaqTopic;
use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * The questions. Previously lived in a static provider class.
     * Each row is [topic, question, answer].
     *
     * @var list<array{0: string, 1: string, 2: string}>
     */
    private const array FAQS = [
        ['Kartu Duel', 'Apa itu Kartu Duel?', 'Kartu Duel adalah tabel perbandingan dua HP yang menandai pemenang di setiap baris spesifikasi. Baris yang menang diberi label “Unggul”, dan di bagian bawah ada ringkasan berapa kategori yang dimenangkan masing-masing.'],
        ['Kartu Duel', 'Kenapa ada baris tanpa penanda unggul?', 'Kalau kedua HP punya angka yang sama persis, baris itu dianggap imbang dan tidak diberi penanda. Aktifkan “hanya tampilkan perbedaan” untuk menyembunyikan baris seperti ini.'],
        ['Kartu Duel', 'Bisa membandingkan lebih dari dua HP?', 'Belum. Saat ini perbandingan dibatasi dua HP agar tabel tetap terbaca di layar ponsel. Kamu bisa membuka beberapa duel berurutan lewat bagian “duel lain yang sering dicari”.'],
        ['Harga', 'Seberapa sering harga diperbarui?', 'Dua kali sehari untuk toko resmi, empat kali sehari untuk TikTok Shop yang sering mengadakan flash sale. Waktu penarikan terakhir tertulis di halaman pantau harga.'],
        ['Harga', 'Kenapa harga di sini beda dengan di aplikasi toko?', 'Kami menampilkan harga barang tanpa ongkos kirim dan tanpa kupon terbatas yang butuh kode khusus. Perbedaan juga bisa muncul kalau toko mengubah harga di antara dua waktu penarikan.'],
        ['Harga', 'Apa bedanya garansi resmi dan distributor?', 'Garansi resmi ditangani pusat servis pabrikan di Indonesia. Unit distributor biasanya Rp200–500 ribu lebih murah, tapi garansinya ditanggung toko dan masa berlakunya lebih pendek.'],
        ['Skor', 'Bagaimana skor dihitung?', 'Lima kategori dengan bobot tetap: performa 25 persen, layar 20, kamera 20, baterai 20, dan nilai per rupiah 15. Rinciannya ada di halaman cara kami menilai.'],
        ['Skor', 'Kenapa skor sebuah HP bisa berubah?', 'Karena harga masuk ke dalam perhitungan. Saat harga pasar turun, skor nilai per rupiah naik, dan skor akhirnya ikut naik tanpa ada perubahan pada perangkatnya.'],
        ['Data', 'Bisakah saya mengusulkan HP yang belum ada?', 'Bisa. Kirim nama dan varian lewat halaman usul HP baru. HP yang dijual resmi di Indonesia biasanya kami tambahkan dalam satu minggu.'],
        ['Data', 'Apakah kalian menerima ulasan berbayar?', 'Tidak. Pendapatan kami berasal dari komisi afiliasi marketplace, yang tidak memengaruhi skor maupun urutan toko. Penjelasan lengkapnya ada di halaman sumber data harga.'],
    ];

    public function run(): void
    {
        foreach (self::FAQS as $sort => [$topic, $question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                [
                    'topic' => FaqTopic::from($topic),
                    'answer' => $answer,
                    'sort' => $sort + 1,
                ],
            );
        }
    }
}
