<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Phone;
use App\Data\SpecValue;

/**
 * The demo catalogue that ships with the design.
 *
 * Six phones (a56, note14pro, x7pro, r13pp, ip16, s25) carry the manufacturer data
 * exactly as the design supplied it. The remaining six only appeared in the catalogue
 * grid there, so their detail-level rows are filled in consistently with the summary
 * values the design did provide.
 */
final class PhoneCatalog
{
    /** @var list<Phone>|null */
    private static ?array $phones = null;

    /** @return list<Phone> */
    public static function all(): array
    {
        return self::$phones ??= array_map(
            static fn (array $row): Phone => self::toPhone($row),
            self::rows(),
        );
    }

    public static function find(string $id): ?Phone
    {
        foreach (self::all() as $phone) {
            if ($phone->id === $id) {
                return $phone;
            }
        }

        return null;
    }

    public static function findOrFirst(string $id): Phone
    {
        return self::find($id) ?? self::all()[0];
    }

    /** @return list<string> */
    public static function brands(): array
    {
        return array_values(array_unique(array_map(
            static fn (Phone $phone): string => $phone->brand,
            self::all(),
        )));
    }

    /** @return list<Phone> */
    public static function forBrand(string $brand): array
    {
        return array_values(array_filter(
            self::all(),
            static fn (Phone $phone): bool => $phone->brand === $brand,
        ));
    }

    /** @return list<Phone> */
    public static function search(string $query, int $limit = 6): array
    {
        $matches = trim($query) === ''
            ? self::all()
            : array_filter(self::all(), static fn (Phone $phone): bool => $phone->matches($query));

        return array_slice(array_values($matches), 0, $limit);
    }

    /** @param array<string, mixed> $row */
    private static function toPhone(array $row): Phone
    {
        $specs = [];

        /** @var array<string, array{0: string, 1: int}> $rawSpecs */
        $rawSpecs = $row['specs'];

        foreach ($rawSpecs as $key => [$display, $comparable]) {
            $specs[$key] = new SpecValue($display, $comparable);
        }

        return new Phone(
            id: $row['id'],
            brand: $row['brand'],
            name: $row['name'],
            price: $row['price'],
            specs: $specs,
            scores: array_combine(
                ['Performa', 'Layar', 'Kamera', 'Baterai', 'Nilai per Rupiah'],
                $row['scores'],
            ),
            pros: $row['pros'],
            cons: $row['cons'],
            nfc: $row['nfc'],
            ip68: $row['ip68'],
            tag: $row['tag'],
            dimensi: $row['dimensi'],
            jaringan: $row['jaringan'],
            rilis: $row['rilis'],
        );
    }

    /** @return list<array<string, mixed>> */
    private static function rows(): array
    {
        return [
            [
                'id' => 'a56', 'brand' => 'Samsung', 'name' => 'Galaxy A56 5G', 'price' => 5499000,
                'nfc' => true, 'ip68' => false, 'tag' => '',
                'dimensi' => '162,2 x 77,5 x 7,4 mm', 'jaringan' => '5G Dual SIM · Wi-Fi 6 · NFC', 'rilis' => 'Maret 2025',
                'specs' => [
                    'chipset' => ['Exynos 1580', 849000],
                    'antutu' => ['± 720.000', 720000],
                    'ram' => ['8 GB', 8],
                    'storage' => ['256 GB UFS 3.1', 256],
                    'panel' => ['6,7" Super AMOLED', 1900],
                    'nits' => ['1.900 nits', 1900],
                    'layar' => ['6,7" AMOLED · 1.900 nits', 1900],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1080 x 2340 (FHD+)', 1080],
                    'kamera' => ['50 MP OIS f/1.8', 82],
                    'ultrawide' => ['12 MP f/2.2', 12],
                    'selfie' => ['12 MP f/2.2', 12],
                    'video' => ['4K 30fps', 30],
                    'baterai' => ['5.000 mAh', 5000],
                    'charge' => ['45W kabel', 45],
                    'ip' => ['IP67', 67],
                    'os' => ['One UI 7 · 6 tahun update', 6],
                    'osUpdate' => ['6 tahun update OS', 6],
                    'berat' => ['198 g', -198],
                ],
                'scores' => [72, 80, 78, 74, 70],
                'pros' => [
                    'Dukungan update OS enam tahun, terlama di kelasnya',
                    'Layar AMOLED 120 Hz yang enak dilihat di luar ruangan',
                    'Bodi tipis dengan rating IP67',
                    'Antarmuka One UI rapi dan minim bloatware',
                ],
                'cons' => [
                    'Pengisian daya 45W kalah cepat dari rival',
                    'Harga resmi paling mahal di segmennya',
                ],
            ],
            [
                'id' => 'note14pro', 'brand' => 'Xiaomi', 'name' => 'Redmi Note 14 Pro 5G', 'price' => 3999000,
                'nfc' => true, 'ip68' => false, 'tag' => 'Terlaris',
                'dimensi' => '162,4 x 74,7 x 8,4 mm', 'jaringan' => '5G Dual SIM · Wi-Fi 6 · NFC', 'rilis' => 'Januari 2025',
                'specs' => [
                    'chipset' => ['Dimensity 7300-Ultra', 722000],
                    'antutu' => ['± 640.000', 640000],
                    'ram' => ['12 GB', 12],
                    'storage' => ['256 GB UFS 2.2', 256],
                    'panel' => ['6,67" AMOLED melengkung', 3000],
                    'nits' => ['3.000 nits', 3000],
                    'layar' => ['6,67" AMOLED · 3.000 nits', 3000],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1220 x 2712 (1.5K)', 1220],
                    'kamera' => ['200 MP OIS f/1.65', 79],
                    'ultrawide' => ['8 MP f/2.2', 8],
                    'selfie' => ['20 MP f/2.2', 20],
                    'video' => ['4K 30fps', 30],
                    'baterai' => ['5.500 mAh', 5500],
                    'charge' => ['45W kabel', 45],
                    'ip' => ['IP67', 67],
                    'os' => ['HyperOS 2 · 3 tahun update', 3],
                    'osUpdate' => ['3 tahun update OS', 3],
                    'berat' => ['190 g', -190],
                ],
                'scores' => [70, 88, 82, 84, 90],
                'pros' => [
                    'Layar 1.5K dengan kecerahan puncak 3.000 nits',
                    'Kamera 200 MP dengan OIS di harga Rp4 jutaan',
                    'Baterai 5.500 mAh, tahan lebih dari sehari',
                    'Nilai per rupiah paling agresif di kelas ini',
                ],
                'cons' => [
                    'Penyimpanan masih UFS 2.2, bukan yang tercepat',
                    'Update OS hanya tiga tahun',
                    'Ultrawide 8 MP terasa seadanya',
                ],
            ],
            [
                'id' => 'x7pro', 'brand' => 'POCO', 'name' => 'POCO X7 Pro', 'price' => 4499000,
                'nfc' => true, 'ip68' => true, 'tag' => 'Performa terbaik',
                'dimensi' => '160,8 x 75,2 x 8,3 mm', 'jaringan' => '5G Dual SIM · Wi-Fi 6 · NFC', 'rilis' => 'Januari 2025',
                'specs' => [
                    'chipset' => ['Dimensity 8400-Ultra', 1485000],
                    'antutu' => ['± 1.480.000', 1480000],
                    'ram' => ['12 GB', 12],
                    'storage' => ['256 GB UFS 4.0', 256],
                    'panel' => ['6,67" AMOLED datar', 3200],
                    'nits' => ['3.200 nits', 3200],
                    'layar' => ['6,67" AMOLED · 3.200 nits', 3200],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1220 x 2712 (1.5K)', 1220],
                    'kamera' => ['50 MP OIS f/1.5', 77],
                    'ultrawide' => ['8 MP f/2.2', 8],
                    'selfie' => ['20 MP f/2.2', 20],
                    'video' => ['4K 60fps', 60],
                    'baterai' => ['6.000 mAh', 6000],
                    'charge' => ['90W kabel', 90],
                    'ip' => ['IP68', 68],
                    'os' => ['HyperOS 2 · 3 tahun update', 3],
                    'osUpdate' => ['3 tahun update OS', 3],
                    'berat' => ['195 g', -195],
                ],
                'scores' => [94, 89, 76, 92, 86],
                'pros' => [
                    'Chipset kelas flagship di harga menengah',
                    'Isi daya 90W penuh sekitar 40 menit',
                    'Baterai 6.000 mAh dengan bodi tetap tipis',
                    'Sertifikasi IP68 tahan air dan debu',
                ],
                'cons' => [
                    'Kamera ultrawide masih 8 MP',
                    'Tanpa telefoto khusus',
                ],
            ],
            [
                'id' => 'r13pp', 'brand' => 'realme', 'name' => 'realme 13 Pro+ 5G', 'price' => 6299000,
                'nfc' => true, 'ip68' => false, 'tag' => '',
                'dimensi' => '161,6 x 74,0 x 8,7 mm', 'jaringan' => '5G Dual SIM · Wi-Fi 6 · NFC', 'rilis' => 'Agustus 2024',
                'specs' => [
                    'chipset' => ['Snapdragon 7s Gen 2', 612000],
                    'antutu' => ['± 610.000', 610000],
                    'ram' => ['12 GB', 12],
                    'storage' => ['512 GB UFS 3.1', 512],
                    'panel' => ['6,7" AMOLED melengkung', 2000],
                    'nits' => ['2.000 nits', 2000],
                    'layar' => ['6,7" AMOLED · 2.000 nits', 2000],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1272 x 2800 (1.5K)', 1272],
                    'kamera' => ['50 MP OIS + Telefoto 3x', 88],
                    'ultrawide' => ['8 MP f/2.2', 8],
                    'selfie' => ['32 MP f/2.4', 32],
                    'video' => ['4K 30fps', 30],
                    'baterai' => ['5.200 mAh', 5200],
                    'charge' => ['80W kabel', 80],
                    'ip' => ['IP65', 65],
                    'os' => ['realme UI 5 · 3 tahun update', 3],
                    'osUpdate' => ['3 tahun update OS', 3],
                    'berat' => ['196 g', -196],
                ],
                'scores' => [68, 82, 90, 78, 66],
                'pros' => [
                    'Satu-satunya di kelasnya dengan telefoto periskop 3x',
                    'Penyimpanan 512 GB sebagai standar',
                    'Isi daya 80W',
                    'Desain punggung kulit vegan yang khas',
                ],
                'cons' => [
                    'Chipset tertinggal dari rival sekelas harga',
                    'Rating IP65 saja',
                    'Harga resmi menembus Rp6 juta',
                ],
            ],
            [
                'id' => 'ip16', 'brand' => 'Apple', 'name' => 'iPhone 16', 'price' => 13999000,
                'nfc' => true, 'ip68' => true, 'tag' => '',
                'dimensi' => '147,6 x 71,6 x 7,8 mm', 'jaringan' => '5G · Wi-Fi 7 · NFC', 'rilis' => 'September 2024',
                'specs' => [
                    'chipset' => ['Apple A18', 1680000],
                    'antutu' => ['± 1.680.000', 1680000],
                    'ram' => ['8 GB', 8],
                    'storage' => ['128 GB NVMe', 128],
                    'panel' => ['6,1" Super Retina XDR', 2000],
                    'nits' => ['2.000 nits', 2000],
                    'layar' => ['6,1" OLED · 2.000 nits', 2000],
                    'refresh' => ['60 Hz', 60],
                    'resolusi' => ['1179 x 2556', 1179],
                    'kamera' => ['48 MP OIS f/1.6', 94],
                    'ultrawide' => ['12 MP f/2.2', 12],
                    'selfie' => ['12 MP f/1.9', 12],
                    'video' => ['4K 60fps Dolby Vision', 60],
                    'baterai' => ['3.561 mAh', 3561],
                    'charge' => ['20W kabel · MagSafe 25W', 20],
                    'ip' => ['IP68', 68],
                    'os' => ['iOS 18 · 7 tahun update', 7],
                    'osUpdate' => ['7 tahun update OS', 7],
                    'berat' => ['170 g', -170],
                ],
                'scores' => [92, 74, 94, 72, 48],
                'pros' => [
                    'Performa dan efisiensi chip A18',
                    'Rekaman video terbaik di kelas mana pun',
                    'Dukungan software tujuh tahun',
                    'Nilai jual kembali paling stabil',
                ],
                'cons' => [
                    'Layar masih 60 Hz',
                    'Pengisian daya lambat',
                    'Penyimpanan dasar hanya 128 GB',
                ],
            ],
            [
                'id' => 's25', 'brand' => 'Samsung', 'name' => 'Galaxy S25', 'price' => 13499000,
                'nfc' => true, 'ip68' => true, 'tag' => '',
                'dimensi' => '146,9 x 70,5 x 7,2 mm', 'jaringan' => '5G Dual SIM · Wi-Fi 7 · NFC', 'rilis' => 'Februari 2025',
                'specs' => [
                    'chipset' => ['Snapdragon 8 Elite', 2290000],
                    'antutu' => ['± 2.290.000', 2290000],
                    'ram' => ['12 GB', 12],
                    'storage' => ['256 GB UFS 4.0', 256],
                    'panel' => ['6,2" Dynamic AMOLED 2X', 2600],
                    'nits' => ['2.600 nits', 2600],
                    'layar' => ['6,2" AMOLED · 2.600 nits', 2600],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1080 x 2340 (FHD+)', 1080],
                    'kamera' => ['50 MP OIS + Telefoto 3x', 92],
                    'ultrawide' => ['12 MP f/2.2', 12],
                    'selfie' => ['12 MP f/2.2', 12],
                    'video' => ['8K 30fps', 80],
                    'baterai' => ['4.000 mAh', 4000],
                    'charge' => ['25W kabel · 15W nirkabel', 25],
                    'ip' => ['IP68', 68],
                    'os' => ['One UI 7 · 7 tahun update', 7],
                    'osUpdate' => ['7 tahun update OS', 7],
                    'berat' => ['162 g', -162],
                ],
                'scores' => [98, 90, 92, 70, 52],
                'pros' => [
                    'Chipset tercepat yang dijual di Indonesia saat ini',
                    'Kompak tapi lengkap dengan telefoto 3x',
                    'Update software tujuh tahun',
                    'Bobot hanya 162 g',
                ],
                'cons' => [
                    'Baterai 4.000 mAh paling kecil di daftar ini',
                    'Pengisian daya 25W',
                    'Harga premium',
                ],
            ],
            [
                'id' => 'note40pro', 'brand' => 'Infinix', 'name' => 'Infinix Note 40 Pro', 'price' => 3199000,
                'nfc' => true, 'ip68' => false, 'tag' => '',
                'dimensi' => '164,4 x 74,5 x 7,9 mm', 'jaringan' => '4G Dual SIM · Wi-Fi 6 · NFC', 'rilis' => 'April 2024',
                'specs' => [
                    'chipset' => ['Helio G99 Ultimate', 412000],
                    'antutu' => ['± 410.000', 410000],
                    'ram' => ['8 GB', 8],
                    'storage' => ['256 GB UFS 2.2', 256],
                    'panel' => ['6,78" AMOLED', 1300],
                    'nits' => ['1.300 nits', 1300],
                    'layar' => ['6,78" AMOLED · 1.300 nits', 1300],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1080 x 2436 (FHD+)', 1080],
                    'kamera' => ['108 MP OIS', 68],
                    'ultrawide' => ['2 MP', 2],
                    'selfie' => ['32 MP', 32],
                    'video' => ['2K 30fps', 20],
                    'baterai' => ['5.000 mAh', 5000],
                    'charge' => ['45W + 20W nirkabel', 45],
                    'ip' => ['IP53', 53],
                    'os' => ['XOS 14 · 2 tahun update', 2],
                    'osUpdate' => ['2 tahun update OS', 2],
                    'berat' => ['196 g', -196],
                ],
                'scores' => [58, 74, 66, 76, 82],
                'pros' => [
                    'Pengisian nirkabel 20W di harga Rp3 jutaan',
                    'Layar AMOLED 120 Hz berukuran lega',
                    'Kamera utama 108 MP dengan OIS',
                ],
                'cons' => [
                    'Update software hanya dua tahun',
                    'Ketahanan air sebatas IP53',
                    'Ultrawide 2 MP sekadar pelengkap',
                ],
            ],
            [
                'id' => 'a16', 'brand' => 'Samsung', 'name' => 'Galaxy A16 5G', 'price' => 2599000,
                'nfc' => false, 'ip68' => false, 'tag' => '',
                'dimensi' => '164,4 x 77,9 x 7,9 mm', 'jaringan' => '5G Dual SIM · Wi-Fi 5', 'rilis' => 'Oktober 2024',
                'specs' => [
                    'chipset' => ['Exynos 1330', 402000],
                    'antutu' => ['± 400.000', 400000],
                    'ram' => ['8 GB', 8],
                    'storage' => ['128 GB UFS 2.2', 128],
                    'panel' => ['6,7" Super AMOLED', 800],
                    'nits' => ['800 nits', 800],
                    'layar' => ['6,7" AMOLED · 800 nits', 800],
                    'refresh' => ['90 Hz', 90],
                    'resolusi' => ['1080 x 2340 (FHD+)', 1080],
                    'kamera' => ['50 MP f/1.8', 62],
                    'ultrawide' => ['5 MP f/2.2', 5],
                    'selfie' => ['13 MP f/2.0', 13],
                    'video' => ['1080p 30fps', 10],
                    'baterai' => ['5.000 mAh', 5000],
                    'charge' => ['25W kabel', 25],
                    'ip' => ['IP54', 54],
                    'os' => ['One UI 6 · 6 tahun update', 6],
                    'osUpdate' => ['6 tahun update OS', 6],
                    'berat' => ['200 g', -200],
                ],
                'scores' => [52, 70, 58, 72, 74],
                'pros' => [
                    'Update OS enam tahun, langka di harga Rp2 jutaan',
                    'Layar Super AMOLED lega',
                    'Baterai 5.000 mAh tahan seharian',
                ],
                'cons' => [
                    'Tanpa NFC',
                    'Refresh rate berhenti di 90 Hz',
                    'Pengisian daya 25W',
                ],
            ],
            [
                'id' => 'note14', 'brand' => 'Xiaomi', 'name' => 'Redmi Note 14', 'price' => 2399000,
                'nfc' => true, 'ip68' => false, 'tag' => 'Hemat',
                'dimensi' => '162,4 x 75,7 x 7,9 mm', 'jaringan' => '4G Dual SIM · Wi-Fi 5 · NFC', 'rilis' => 'Desember 2024',
                'specs' => [
                    'chipset' => ['Helio G99 Ultra', 408000],
                    'antutu' => ['± 405.000', 405000],
                    'ram' => ['8 GB', 8],
                    'storage' => ['128 GB UFS 2.2', 128],
                    'panel' => ['6,67" AMOLED', 1800],
                    'nits' => ['1.800 nits', 1800],
                    'layar' => ['6,67" AMOLED · 1.800 nits', 1800],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1080 x 2400 (FHD+)', 1080],
                    'kamera' => ['108 MP f/1.7', 66],
                    'ultrawide' => ['2 MP f/2.4', 2],
                    'selfie' => ['16 MP f/2.4', 16],
                    'video' => ['1080p 60fps', 12],
                    'baterai' => ['5.500 mAh', 5500],
                    'charge' => ['33W kabel', 33],
                    'ip' => ['IP54', 54],
                    'os' => ['HyperOS 2 · 3 tahun update', 3],
                    'osUpdate' => ['3 tahun update OS', 3],
                    'berat' => ['190 g', -190],
                ],
                'scores' => [54, 78, 62, 82, 86],
                'pros' => [
                    'Layar AMOLED 120 Hz di harga Rp2 jutaan',
                    'Baterai 5.500 mAh, salah satu yang terbesar di kelasnya',
                    'Sudah dilengkapi NFC',
                ],
                'cons' => [
                    'Chipset hanya cukup untuk pemakaian harian',
                    'Pengisian daya 33W',
                    'Rekam video terbatas di 1080p',
                ],
            ],
            [
                'id' => 'c75', 'brand' => 'realme', 'name' => 'realme C75', 'price' => 1999000,
                'nfc' => false, 'ip68' => true, 'tag' => '',
                'dimensi' => '165,7 x 76,2 x 7,9 mm', 'jaringan' => '4G Dual SIM · Wi-Fi 5', 'rilis' => 'November 2024',
                'specs' => [
                    'chipset' => ['Helio G92 Max', 258000],
                    'antutu' => ['± 255.000', 255000],
                    'ram' => ['8 GB', 8],
                    'storage' => ['128 GB eMMC 5.1', 128],
                    'panel' => ['6,72" IPS LCD', 625],
                    'nits' => ['625 nits', 625],
                    'layar' => ['6,72" IPS LCD · 625 nits', 625],
                    'refresh' => ['90 Hz', 90],
                    'resolusi' => ['1080 x 2400 (FHD+)', 1080],
                    'kamera' => ['50 MP f/1.8', 55],
                    'ultrawide' => ['—', 0],
                    'selfie' => ['8 MP f/2.0', 8],
                    'video' => ['1080p 30fps', 10],
                    'baterai' => ['6.000 mAh', 6000],
                    'charge' => ['45W kabel', 45],
                    'ip' => ['IP68', 68],
                    'os' => ['realme UI 5 · 2 tahun update', 2],
                    'osUpdate' => ['2 tahun update OS', 2],
                    'berat' => ['201 g', -201],
                ],
                'scores' => [42, 56, 48, 88, 78],
                'pros' => [
                    'Rating IP68 di harga di bawah Rp2 juta',
                    'Baterai 6.000 mAh dengan pengisian 45W',
                    'Bodi tahan banting',
                ],
                'cons' => [
                    'Masih memakai panel IPS LCD',
                    'Tanpa NFC dan tanpa ultrawide',
                    'Performa sebatas pemakaian ringan',
                ],
            ],
            [
                'id' => 'hot50', 'brand' => 'Infinix', 'name' => 'Infinix Hot 50 Pro+', 'price' => 1799000,
                'nfc' => false, 'ip68' => false, 'tag' => '',
                'dimensi' => '164,3 x 74,5 x 6,8 mm', 'jaringan' => '4G Dual SIM · Wi-Fi 5', 'rilis' => 'September 2024',
                'specs' => [
                    'chipset' => ['Helio G100', 415000],
                    'antutu' => ['± 412.000', 412000],
                    'ram' => ['8 GB', 8],
                    'storage' => ['256 GB UFS 2.2', 256],
                    'panel' => ['6,78" AMOLED melengkung', 1300],
                    'nits' => ['1.300 nits', 1300],
                    'layar' => ['6,78" AMOLED · 1.300 nits', 1300],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1080 x 2436 (FHD+)', 1080],
                    'kamera' => ['50 MP f/1.6', 60],
                    'ultrawide' => ['2 MP f/2.4', 2],
                    'selfie' => ['32 MP f/2.2', 32],
                    'video' => ['1440p 30fps', 14],
                    'baterai' => ['5.000 mAh', 5000],
                    'charge' => ['33W kabel', 33],
                    'ip' => ['IP54', 54],
                    'os' => ['XOS 14 · 2 tahun update', 2],
                    'osUpdate' => ['2 tahun update OS', 2],
                    'berat' => ['170 g', -170],
                ],
                'scores' => [56, 76, 58, 72, 80],
                'pros' => [
                    'Layar AMOLED 120 Hz di bawah Rp2 juta',
                    'Bodi paling tipis di kelasnya',
                    'Penyimpanan 256 GB sebagai standar',
                ],
                'cons' => [
                    'Tanpa NFC',
                    'Ketahanan air hanya IP54',
                    'Update software dua tahun',
                ],
            ],
            [
                'id' => 'm7pro', 'brand' => 'POCO', 'name' => 'POCO M7 Pro 5G', 'price' => 2899000,
                'nfc' => true, 'ip68' => false, 'tag' => '',
                'dimensi' => '162,4 x 75,7 x 7,9 mm', 'jaringan' => '5G Dual SIM · Wi-Fi 5 · NFC', 'rilis' => 'Desember 2024',
                'specs' => [
                    'chipset' => ['Dimensity 7025-Ultra', 445000],
                    'antutu' => ['± 440.000', 440000],
                    'ram' => ['8 GB', 8],
                    'storage' => ['256 GB UFS 3.1', 256],
                    'panel' => ['6,67" AMOLED', 2100],
                    'nits' => ['2.100 nits', 2100],
                    'layar' => ['6,67" AMOLED · 2.100 nits', 2100],
                    'refresh' => ['120 Hz', 120],
                    'resolusi' => ['1080 x 2400 (FHD+)', 1080],
                    'kamera' => ['50 MP OIS f/1.5', 70],
                    'ultrawide' => ['—', 0],
                    'selfie' => ['20 MP f/2.2', 20],
                    'video' => ['1080p 60fps', 12],
                    'baterai' => ['5.110 mAh', 5110],
                    'charge' => ['45W kabel', 45],
                    'ip' => ['IP64', 64],
                    'os' => ['HyperOS 2 · 3 tahun update', 3],
                    'osUpdate' => ['3 tahun update OS', 3],
                    'berat' => ['190 g', -190],
                ],
                'scores' => [60, 80, 64, 78, 84],
                'pros' => [
                    'Kamera utama sudah ber-OIS di harga Rp2 jutaan',
                    'Layar AMOLED 120 Hz dengan puncak 2.100 nits',
                    'Sudah 5G dan dilengkapi NFC',
                ],
                'cons' => [
                    'Tanpa kamera ultrawide',
                    'Rekam video terbatas di 1080p',
                    'Bukan yang tercepat di kelas harganya',
                ],
            ],
        ];
    }
}
