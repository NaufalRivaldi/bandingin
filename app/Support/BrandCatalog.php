<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Brand;

final class BrandCatalog
{
    /** @var array<string, Brand>|null */
    private static ?array $brands = null;

    /** @return array<string, Brand> */
    public static function all(): array
    {
        return self::$brands ??= array_map(
            static fn (array $row): Brand => new Brand(
                name: $row['name'],
                blurb: $row['blurb'],
                stats: $row['stats'],
                models: array_map(
                    static fn (array $model): array => [
                        'name' => $model[0],
                        'price' => $model[1],
                        'spec' => $model[2],
                    ],
                    $row['models'],
                ),
                pros: $row['pros'],
                cons: $row['cons'],
            ),
            self::rows(),
        );
    }

    /** @return list<string> */
    public static function names(): array
    {
        return array_keys(self::all());
    }

    public static function find(string $name): ?Brand
    {
        return self::all()[$name] ?? null;
    }

    public static function findOrFirst(string $name): Brand
    {
        return self::find($name) ?? self::all()[self::names()[0]];
    }

    /** @return array<string, array<string, mixed>> */
    private static function rows(): array
    {
        return [
            'Xiaomi' => [
                'name' => 'Xiaomi',
                'blurb' => 'Merek dengan pangsa pasar terbesar di segmen Rp2–5 juta. Kekuatannya ada di layar dan kamera beresolusi tinggi pada harga yang sulit ditandingi.',
                'stats' => ['Model aktif' => '14', 'Harga terendah' => 'Rp1,4 jt', 'Rentang terlaris' => 'Rp2–4 jt', 'Update OS' => '3 tahun'],
                'models' => [
                    ['Redmi Note 14 Pro 5G', 'Rp3.999.000', 'Dimensity 7300-Ultra · 200 MP · 5.500 mAh'],
                    ['Redmi Note 14', 'Rp2.399.000', 'Helio G99 Ultra · AMOLED 120 Hz'],
                    ['Redmi 14C', 'Rp1.399.000', 'Helio G81 · 5.160 mAh'],
                    ['Xiaomi 14T', 'Rp7.999.000', 'Dimensity 8300-Ultra · Leica'],
                ],
                'pros' => [
                    'Spesifikasi kertas paling agresif di tiap rentang harga',
                    'Layar AMOLED cerah bahkan di model termurah',
                    'Jaringan servis resmi luas sampai kota kabupaten',
                ],
                'cons' => [
                    'Dukungan update OS hanya tiga tahun',
                    'Masih ada aplikasi bawaan yang mengirim notifikasi promosi',
                ],
            ],
            'Samsung' => [
                'name' => 'Samsung',
                'blurb' => 'Pilihan paling aman untuk pemakaian jangka panjang. Dukungan software terpanjang di industri dan nilai jual kembali yang stabil.',
                'stats' => ['Model aktif' => '11', 'Harga terendah' => 'Rp1,8 jt', 'Rentang terlaris' => 'Rp4–7 jt', 'Update OS' => '6–7 tahun'],
                'models' => [
                    ['Galaxy A56 5G', 'Rp5.499.000', 'Exynos 1580 · IP67 · 6 tahun update'],
                    ['Galaxy A16 5G', 'Rp2.599.000', 'Exynos 1330 · Super AMOLED'],
                    ['Galaxy S25', 'Rp13.499.000', 'Snapdragon 8 Elite · telefoto 3x'],
                    ['Galaxy A36 5G', 'Rp4.499.000', 'Snapdragon 6 Gen 3 · 45W'],
                ],
                'pros' => [
                    'Update OS enam sampai tujuh tahun, terlama di pasar',
                    'One UI konsisten dari kelas menengah sampai flagship',
                    'Harga bekas turun paling lambat',
                ],
                'cons' => [
                    'Performa per rupiah kalah dari merek Tiongkok di kelas menengah',
                    'Pengisian daya relatif lambat',
                ],
            ],
            'POCO' => [
                'name' => 'POCO',
                'blurb' => 'Sub-merek Xiaomi yang fokus ke performa. Chipset kelas atas dengan kompromi di kamera pendukung.',
                'stats' => ['Model aktif' => '7', 'Harga terendah' => 'Rp1,6 jt', 'Rentang terlaris' => 'Rp3–5 jt', 'Update OS' => '3 tahun'],
                'models' => [
                    ['POCO X7 Pro', 'Rp4.499.000', 'Dimensity 8400-Ultra · 90W · IP68'],
                    ['POCO M7 Pro 5G', 'Rp2.899.000', 'Dimensity 7025-Ultra · AMOLED'],
                    ['POCO F7 Pro', 'Rp6.999.000', 'Snapdragon 8 Gen 3 · 6.000 mAh'],
                    ['POCO C75', 'Rp1.599.000', 'Helio G81 Ultra · 5.160 mAh'],
                ],
                'pros' => [
                    'Chipset kelas flagship di harga menengah',
                    'Pengisian daya tercepat di kelasnya',
                    'Layar 1.5K sudah standar di seri X',
                ],
                'cons' => [
                    'Kamera ultrawide dan makro sekadar pelengkap',
                    'Purna jual bergantung pada gerai Xiaomi',
                ],
            ],
            'realme' => [
                'name' => 'realme',
                'blurb' => 'Bermain di desain dan fitur kamera. Beberapa model membawa telefoto periskop yang jarang ada di kelas menengah.',
                'stats' => ['Model aktif' => '9', 'Harga terendah' => 'Rp1,5 jt', 'Rentang terlaris' => 'Rp2–4 jt', 'Update OS' => '3 tahun'],
                'models' => [
                    ['realme 13 Pro+ 5G', 'Rp6.299.000', 'Telefoto periskop 3x · 512 GB'],
                    ['realme C75', 'Rp1.999.000', 'Helio G92 Max · IP68 · 6.000 mAh'],
                    ['realme 14 Pro 5G', 'Rp4.999.000', 'Dimensity 7300 · desain ubah warna'],
                    ['realme Note 60', 'Rp1.499.000', 'Unisoc T612 · layar tahan banting'],
                ],
                'pros' => [
                    'Telefoto periskop di harga yang belum ditandingi',
                    'Desain paling berani di antara merek sekelasnya',
                    'Rating IP68 bahkan di model entry',
                ],
                'cons' => [
                    'Chipset sering tertinggal dari rival sekelas harga',
                    'Iklan di antarmuka masih ada di beberapa model',
                ],
            ],
            'Infinix' => [
                'name' => 'Infinix',
                'blurb' => 'Bermain di harga paling bawah. Kompromi ada di software dan ketahanan, tapi spesifikasi dasarnya lengkap.',
                'stats' => ['Model aktif' => '8', 'Harga terendah' => 'Rp1,1 jt', 'Rentang terlaris' => 'Rp1–2 jt', 'Update OS' => '2 tahun'],
                'models' => [
                    ['Infinix Note 40 Pro', 'Rp3.199.000', 'Helio G99 Ultimate · 20W nirkabel'],
                    ['Infinix Hot 50 Pro+', 'Rp1.799.000', 'Helio G100 · AMOLED 120 Hz'],
                    ['Infinix Zero 40 5G', 'Rp4.499.000', 'Dimensity 8020 · 4K 60fps'],
                    ['Infinix Smart 9', 'Rp1.099.000', 'Unisoc T7250 · 5.000 mAh'],
                ],
                'pros' => [
                    'Fitur seperti pengisian nirkabel muncul di harga Rp3 jutaan',
                    'Layar AMOLED sudah ada di bawah Rp2 juta',
                    'Kapasitas baterai besar di semua lini',
                ],
                'cons' => [
                    'Update software paling singkat',
                    'Ketahanan air minim di sebagian besar model',
                ],
            ],
            'Apple' => [
                'name' => 'Apple',
                'blurb' => 'Ekosistem tertutup dengan dukungan software terpanjang dan rekaman video terbaik. Harga masuk paling tinggi di daftar ini.',
                'stats' => ['Model aktif' => '6', 'Harga terendah' => 'Rp8,4 jt', 'Rentang terlaris' => 'Rp13 jt+', 'Update OS' => '7 tahun'],
                'models' => [
                    ['iPhone 16', 'Rp13.999.000', 'A18 · 4K 60fps Dolby Vision'],
                    ['iPhone 15', 'Rp11.999.000', 'A16 Bionic · Dynamic Island'],
                    ['iPhone 16 Pro', 'Rp19.999.000', 'A18 Pro · telefoto 5x'],
                    ['iPhone SE 3', 'Rp8.399.000', 'A15 Bionic · Touch ID'],
                ],
                'pros' => [
                    'Dukungan iOS sampai tujuh tahun',
                    'Kualitas video tidak tertandingi di harga berapa pun',
                    'Nilai jual kembali tertinggi',
                ],
                'cons' => [
                    'Model dasar masih 60 Hz dan penyimpanan 128 GB',
                    'Harga masuk jauh di atas rata-rata pasar Indonesia',
                ],
            ],
        ];
    }
}
