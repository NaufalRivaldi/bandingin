<?php

declare(strict_types=1);

namespace App\Support;

final class FaqRepository
{
    public const string ALL_TOPICS = 'Semua';

    /** @return list<string> */
    public static function topics(): array
    {
        return [self::ALL_TOPICS, 'Kartu Duel', 'Harga', 'Skor', 'Data'];
    }

    /** @return list<array{n: string, title: string, text: string}> */
    public static function steps(): array
    {
        return [
            ['n' => '1', 'title' => 'Pilih dua HP', 'text' => 'Ketik nama HP di dua kolom pencarian, atau pakai pintasan duel populer di bawah tombol bandingkan.'],
            ['n' => '2', 'title' => 'Baca penanda unggul', 'text' => 'Setiap baris menandai HP yang menang. Nyalakan “hanya perbedaan” kalau ingin melihat yang berbeda saja.'],
            ['n' => '3', 'title' => 'Cek harga sebelum beli', 'text' => 'Buka daftar penjual untuk melihat harga per toko hari ini, lalu pasang notifikasi kalau ingin menunggu harga turun.'],
        ];
    }

    /** @return list<array{topic: string, question: string, answer: string}> */
    public static function all(): array
    {
        return array_map(
            static fn (array $row): array => ['topic' => $row[0], 'question' => $row[1], 'answer' => $row[2]],
            self::rows(),
        );
    }

    /** @return list<array{topic: string, question: string, answer: string}> */
    public static function forTopic(string $topic): array
    {
        if ($topic === self::ALL_TOPICS) {
            return self::all();
        }

        return array_values(array_filter(
            self::all(),
            static fn (array $item): bool => $item['topic'] === $topic,
        ));
    }

    /** @return list<array{0: string, 1: string, 2: string}> */
    private static function rows(): array
    {
        return [
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
    }
}
