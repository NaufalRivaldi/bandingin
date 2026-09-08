<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ArticleCategory;
use App\Models\Article;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Seeds the articles from data inlined below, folding the design's three prose
 * fields (body, quote, bodyRest) into the single rich-text body admins edit.
 */
class ArticleSeeder extends Seeder
{
    /**
     * The articles. Previously lived in a static provider class.
     *
     * @var list<array<string, mixed>>
     */
    private const array ARTICLES = [
        [
            'id' => 1, 'slug' => 'galaxy-a57-bocor-layar-120-hz', 'cat' => 'Rilis',
            'date' => '12 Agu 2026', 'dateLong' => '12 Agustus 2026', 'read' => 5,
            'title' => 'Samsung Galaxy A57 bocor: layar 120 Hz turun ke kelas Rp4 jutaan',
            'excerpt' => 'Chipset Exynos baru dan panel 1.900 nits, dengan harga yang bisa menekan A56.',
            'body' => [
                'Bocoran terbaru dari basis data sertifikasi menyebut Samsung sedang menyiapkan Galaxy A57 dengan panel 120 Hz dan puncak kecerahan 1.900 nits — angka yang setahun lalu masih jadi milik seri S. Kalau benar, ini kali pertama Samsung menaruh panel sekelas itu di rentang Rp4 jutaan.',
                'Yang lebih menarik justru efek sampingnya. Setiap kali seri A baru masuk, harga generasi sebelumnya turun antara Rp300rb sampai Rp700rb dalam dua bulan pertama. Jadi kabar ini sebenarnya kabar bagus buat kamu yang sedang mengincar A56.',
                'Kami membandingkan bocoran spesifikasi A57 dengan A56 yang sekarang dijual Rp5.499.000. Bedanya tidak besar di atas kertas, tapi cukup terasa di dua hal yang paling sering dikeluhkan pembeli: kecerahan layar di bawah matahari dan kecepatan pengisian.',
            ],
            'quote' => 'Kalau kamu tidak butuh HP baru bulan ini, menunggu rilis A57 hampir selalu lebih untung — bukan karena A57-nya, tapi karena harga A56 ikut turun.',
            'bodyRest' => [
                'Samsung belum mengumumkan tanggal apa pun, dan semua angka di atas masih bocoran. Kami akan memperbarui artikel ini begitu ada sertifikasi resmi dari Postel.',
                'Sementara itu, A56 masih pilihan paling aman di kelasnya kalau kamu butuh sekarang. Cek harganya dulu di beberapa marketplace — selisih antar toko bisa sampai Rp300rb untuk unit garansi resmi yang sama.',
            ],
            'tags' => ['Samsung', 'Galaxy A57', 'Bocoran'],
            'specs' => [
                'title' => 'Galaxy A56 vs bocoran A57',
                'left' => 'Galaxy A56 5G',
                'right' => 'Galaxy A57 (bocoran)',
                'rows' => [
                    ['Chipset', 'Exynos 1580', 'Exynos 1680', 'right'],
                    ['Layar', '6,7" AMOLED · 1.900 nits', '6,7" AMOLED · 1.900 nits', null],
                    ['Refresh Rate', '120 Hz', '120 Hz', null],
                    ['Baterai', '5.000 mAh · 45W', '5.400 mAh · 45W', 'right'],
                    ['Harga', 'Rp5.499.000', 'Rp4.999.000 (perkiraan)', 'right'],
                ],
            ],
        ],
        [
            'id' => 2, 'slug' => 'harga-redmi-note-14-pro-turun', 'cat' => 'Harga',
            'date' => '11 Agu 2026', 'dateLong' => '11 Agustus 2026', 'read' => 3,
            'title' => 'Harga Redmi Note 14 Pro turun Rp400rb jelang rilis seri 15',
            'excerpt' => 'Potongan berlaku di tiga marketplace besar, termasuk unit garansi resmi.',
            'body' => [
                'Redmi Note 14 Pro 5G kini dibuka dari Rp3.999.000 untuk varian 8/256 GB, turun sekitar Rp400rb dari harga peluncuran. Potongan muncul serentak di tiga marketplace, yang biasanya menandakan penyesuaian dari distributor, bukan promo toko.',
                'Pola ini terjadi tiap tahun sekitar satu bulan sebelum generasi berikutnya masuk. Untuk kamu yang tidak mengejar seri terbaru, ini titik harga paling masuk akal sepanjang siklus hidup HP-nya.',
            ],
            'quote' => 'Di kelas Rp3–4 juta, selisih Rp400rb setara satu tingkat varian memori. Itu bukan potongan kecil.',
            'bodyRest' => [
                'Perhatikan satu hal: sebagian toko mencampur unit garansi distributor di listing yang sama. Selisihnya bisa Rp200–500rb, tapi klaim servisnya tidak lewat pusat layanan resmi.',
            ],
            'tags' => ['Xiaomi', 'Redmi Note 14 Pro', 'Harga turun'],
        ],
        [
            'id' => 3, 'slug' => 'lima-hp-rp3-jutaan-chipset-kencang', 'cat' => 'Panduan',
            'date' => '9 Agu 2026', 'dateLong' => '9 Agustus 2026', 'read' => 7,
            'title' => '5 HP Rp3 jutaan dengan chipset paling kencang bulan ini',
            'excerpt' => 'Kami urutkan berdasarkan skor benchmark, bukan klaim di brosur.',
            'body' => [
                'Di kelas Rp3 jutaan, perbedaan chipset jauh lebih menentukan daripada jumlah megapiksel. Kami mengurutkan lima HP yang beredar bulan ini berdasarkan skor AnTuTu rata-rata dari tiga kali pengujian, bukan angka rilisan pabrikan.',
                'Yang mengejutkan, HP termurah di daftar ini justru unggul di skor grafis. Tapi skor tinggi tidak otomatis nyaman dipakai — dua di antaranya turun performa cukup jauh setelah dua puluh menit bermain.',
            ],
            'quote' => 'Chipset menentukan berapa lama HP-mu masih terasa layak dipakai dua tahun dari sekarang.',
            'bodyRest' => [
                'Kalau kamu memakai HP lebih untuk media sosial dan kamera, prioritaskan layar dan pengisian cepat. Skor benchmark baru terasa bedanya kalau kamu bermain game berat atau merekam video panjang.',
            ],
            'tags' => ['Panduan beli', 'Chipset', 'Rp3 jutaan'],
        ],
        [
            'id' => 4, 'slug' => 'beda-garansi-resmi-dan-distributor', 'cat' => 'Panduan',
            'date' => '7 Agu 2026', 'dateLong' => '7 Agustus 2026', 'read' => 6,
            'title' => 'Beda garansi resmi dan distributor: mana yang layak diambil?',
            'excerpt' => 'Selisihnya Rp200–500rb. Ini yang kamu tukar dengan uang itu.',
            'body' => [
                'Garansi resmi berarti unitnya masuk lewat distributor yang ditunjuk pabrikan, terdaftar di Postel, dan bisa diservis di pusat layanan resmi. Garansi distributor berarti servisnya ditanggung toko atau importir, biasanya satu tahun, dan tidak selalu punya suku cadang asli.',
                'Selisih harganya nyata: Rp200rb sampai Rp500rb untuk unit yang secara fisik sama. Pertanyaannya bukan mana yang lebih murah, tapi seberapa besar risiko yang mau kamu tanggung sendiri.',
            ],
            'quote' => 'Untuk HP di bawah Rp3 juta, garansi distributor sering masih wajar. Di atas Rp7 juta, hampir tidak pernah.',
            'bodyRest' => [
                'Kalau kamu ambil unit distributor, minta nomor IMEI-nya sebelum bayar dan cek di situs Kemenperin. Unit yang tidak terdaftar bisa kehilangan sinyal setelah beberapa bulan.',
            ],
            'tags' => ['Garansi resmi', 'Panduan beli'],
        ],
        [
            'id' => 5, 'slug' => 'poco-x7-pro-update-hyperos-3', 'cat' => 'Rilis',
            'date' => '5 Agu 2026', 'dateLong' => '5 Agustus 2026', 'read' => 4,
            'title' => 'POCO X7 Pro dapat update HyperOS 3, kamera malam membaik',
            'excerpt' => 'Pemrosesan malam lebih bersih, tapi baterai boros di minggu pertama.',
            'body' => [
                'Pembaruan HyperOS 3 mulai bergulir untuk POCO X7 Pro. Perubahan paling terasa ada di mode malam: noise di area gelap turun cukup jauh dan warna lampu jalan tidak lagi menguning berlebihan.',
                'Seperti biasa setelah pembaruan besar, konsumsi baterai naik selama beberapa hari pertama karena proses indeks ulang. Kami mengukur penurunan sekitar 8 persen pada hari kedua, lalu kembali normal di hari kelima.',
            ],
            'quote' => 'Kalau baterai terasa boros setelah update, tunggu lima hari sebelum menyalahkan pembaruannya.',
            'bodyRest' => [
                'Pembaruan dibagikan bertahap per wilayah. Kalau belum muncul, jangan pasang paket dari sumber tidak resmi — risikonya kehilangan garansi.',
            ],
            'tags' => ['POCO', 'HyperOS', 'Update'],
        ],
        [
            'id' => 6, 'slug' => 'review-galaxy-a56-5g', 'cat' => 'Ulasan',
            'date' => '4 Agu 2026', 'dateLong' => '4 Agustus 2026', 'read' => 9,
            'title' => 'Review Galaxy A56 5G: naik kelas di layar, standar di kamera',
            'excerpt' => 'Dua minggu pemakaian harian, tanpa unit pinjaman dari brand.',
            'body' => [
                'Galaxy A56 5G adalah HP yang mudah direkomendasikan dan sulit dibuat menarik. Semuanya bekerja: layar 1.900 nits terbaca di bawah matahari, bodinya rapat, dan empat generasi pembaruan Android bikin harganya masuk akal untuk pemakaian panjang.',
                'Kameranya cerita lain. Di siang hari hasilnya bersih dan konsisten. Begitu cahaya turun, pemrosesannya menghaluskan detail terlalu agresif — wajar di kelas ini, tapi Redmi Note 14 Pro menangkap tekstur lebih baik di harga lebih murah.',
            ],
            'quote' => 'A56 bukan HP paling kencang di harganya. Tapi dua tahun dari sekarang, kemungkinan besar masih terasa enak dipakai.',
            'bodyRest' => [
                'Pengisian 45W mengisi dari kosong ke 60 persen dalam 30 menit. Cukup, tapi bukan yang tercepat: POCO X7 Pro dengan 90W selesai hampir dua kali lebih dulu.',
                'Kesimpulannya, ambil A56 kalau kamu mengutamakan layar, ketahanan, dan pembaruan panjang. Kalau prioritasmu performa per rupiah, ada pilihan yang lebih agresif di bawahnya.',
            ],
            'tags' => ['Samsung', 'Galaxy A56 5G', 'Review'],
            'specs' => [
                'title' => 'Galaxy A56 5G vs Redmi Note 14 Pro 5G',
                'left' => 'Galaxy A56 5G',
                'right' => 'Redmi Note 14 Pro 5G',
                'rows' => [
                    ['Chipset', 'Exynos 1580', 'Dimensity 7300-Ultra', 'left'],
                    ['RAM', '8 GB', '12 GB', 'right'],
                    ['Layar', '6,7" AMOLED · 1.900 nits', '6,67" AMOLED · 3.000 nits', 'right'],
                    ['Baterai', '5.000 mAh · 45W', '5.500 mAh · 45W', 'right'],
                    ['Harga', 'Rp5.499.000', 'Rp3.999.000', 'right'],
                ],
            ],
        ],
        [
            'id' => 7, 'slug' => 'pantauan-harga-agustus-iphone-16', 'cat' => 'Harga',
            'date' => '2 Agu 2026', 'dateLong' => '2 Agustus 2026', 'read' => 4,
            'title' => 'Pantauan harga Agustus: iPhone 16 mulai Rp13 jutaan di marketplace',
            'excerpt' => 'Harga resmi tidak berubah, tapi toko besar mulai memotong.',
            'body' => [
                'Harga resmi iPhone 16 128 GB masih Rp13.999.000, tapi beberapa toko besar sudah membukanya di kisaran Rp13,2 juta untuk unit garansi resmi. Selisihnya datang dari kupon marketplace, bukan potongan pabrikan.',
                'Kami mencatat harga setiap hari dari tiga marketplace. Pola bulan ini sama seperti Juli: harga terendah muncul di dua hari pertama tiap bulan, lalu naik lagi setelah kuota kupon habis.',
            ],
            'quote' => 'Untuk iPhone, harga terbaik hampir selalu soal timing kupon, bukan soal toko mana yang kamu pilih.',
            'bodyRest' => [
                'Kalau kamu tidak buru-buru, pantau dua hari pertama bulan depan. Kami kirim notifikasi harga turun lewat buletin mingguan.',
            ],
            'tags' => ['Apple', 'iPhone 16', 'Pantauan harga'],
        ],
        [
            'id' => 8, 'slug' => 'review-poco-x7-pro', 'cat' => 'Ulasan',
            'date' => '1 Agu 2026', 'dateLong' => '1 Agustus 2026', 'read' => 8,
            'title' => 'Review POCO X7 Pro: kencang, tapi panas di siang bolong',
            'excerpt' => 'Dimensity 8400-Ultra memang beda kelas. Suhunya juga.',
            'body' => [
                'POCO X7 Pro adalah HP tercepat yang bisa kamu beli di bawah Rp5 juta, dan angkanya tidak bohong: skor AnTuTu-nya hampir dua kali Galaxy A56. Untuk main game berat, tidak ada lawan di harga ini.',
                'Masalahnya panas. Setelah 25 menit bermain di dalam ruangan ber-AC, bodi bagian atas mencapai 43 derajat dan performanya turun sekitar 18 persen. Di luar ruangan siang hari, penurunannya lebih cepat.',
            ],
            'quote' => 'Performa puncaknya luar biasa untuk harganya. Yang perlu kamu tanya: seberapa lama kamu butuh performa itu bertahan.',
            'bodyRest' => [
                'Pengisian 90W dan baterai 6.000 mAh menutup banyak dosa. Dari kosong ke penuh 42 menit, dan pemakaian normal tembus satu setengah hari.',
            ],
            'tags' => ['POCO', 'POCO X7 Pro', 'Review'],
        ],
        [
            'id' => 9, 'slug' => 'cara-baca-spesifikasi-hp', 'cat' => 'Panduan',
            'date' => '30 Jul 2026', 'dateLong' => '30 Juli 2026', 'read' => 6,
            'title' => 'Cara baca spesifikasi HP tanpa ketipu angka besar',
            'excerpt' => '200 MP bukan berarti fotonya lebih bagus. Ini yang benar-benar penting.',
            'body' => [
                'Angka besar mudah dijual: 200 MP, 5.000 mAh, 120 Hz. Tapi tiga angka itu hampir tidak pernah menjelaskan pengalaman pakai sehari-hari. Yang menentukan justru hal-hal yang jarang dicetak besar di brosur.',
                'Untuk kamera, ukuran sensor dan keberadaan OIS lebih menentukan daripada megapiksel. Untuk baterai, angka watt pengisian dan efisiensi chipset lebih penting daripada kapasitas mAh.',
            ],
            'quote' => 'Spesifikasi hanya berguna kalau kamu tahu angka mana yang bisa kamu rasakan.',
            'bodyRest' => [
                'Cara paling cepat: buka Kartu Duel, taruh dua kandidatmu berdampingan, dan lihat baris mana yang benar-benar berbeda. Baris yang nilainya sama tidak perlu jadi bahan pertimbangan.',
            ],
            'tags' => ['Panduan beli', 'Spesifikasi'],
        ],
        [
            'id' => 10, 'slug' => 'realme-13-pro-plus-12-512-gb', 'cat' => 'Rilis',
            'date' => '28 Jul 2026', 'dateLong' => '28 Juli 2026', 'read' => 3,
            'title' => 'realme 13 Pro+ versi 12/512 GB resmi masuk Indonesia',
            'excerpt' => 'Varian memori tertinggi dibuka Rp6.799.000, beda Rp500rb dari 256 GB.',
            'body' => [
                'realme menambah varian 12/512 GB untuk 13 Pro+ 5G dengan harga Rp6.799.000. Spesifikasi lainnya identik dengan varian 256 GB, termasuk Snapdragon 7s Gen 2 dan telefoto 3x yang jadi nilai jual utamanya.',
                'Selisih Rp500rb untuk penyimpanan dua kali lipat termasuk wajar, terutama karena seri ini tidak punya slot microSD.',
            ],
            'quote' => 'Kalau kamu merekam video panjang, 512 GB tanpa slot memori adalah investasi yang masuk akal.',
            'bodyRest' => [
                'Penjualan dimulai minggu ini di marketplace resmi. Bonus perpanjangan garansi berlaku sampai akhir Agustus.',
            ],
            'tags' => ['realme', 'realme 13 Pro+', 'Rilis'],
        ],
        [
            'id' => 11, 'slug' => 'infinix-note-40-pro-flash-sale', 'cat' => 'Harga',
            'date' => '26 Jul 2026', 'dateLong' => '26 Juli 2026', 'read' => 3,
            'title' => 'Infinix Note 40 Pro dibanting jadi Rp2,9 juta di flash sale',
            'excerpt' => 'Harga terendah yang pernah kami catat untuk unit garansi resmi.',
            'body' => [
                'Infinix Note 40 Pro sempat dibuka Rp2.899.000 dalam flash sale dua hari, dari harga normal Rp3.199.000. Ini titik terendah sejak kami mulai memantau HP ini pada Februari.',
                'Di harga itu, ia jadi satu-satunya HP dengan AMOLED 120 Hz dan pengisian 45W di bawah Rp3 juta. Chipset Helio G99 Ultimate memang bukan yang tercepat, tapi cukup untuk pemakaian harian.',
            ],
            'quote' => 'Di bawah Rp3 juta, kompromi selalu ada. Pertanyaannya kompromi di bagian mana yang paling bisa kamu terima.',
            'bodyRest' => [
                'Stok flash sale terbatas per sesi. Kalau terlewat, harga normalnya masih kompetitif dibanding Redmi Note 14 versi 4G.',
            ],
            'tags' => ['Infinix', 'Flash sale', 'Harga turun'],
        ],
        [
            'id' => 12, 'slug' => 'adu-kamera-malam-redmi-vs-galaxy', 'cat' => 'Ulasan',
            'date' => '24 Jul 2026', 'dateLong' => '24 Juli 2026', 'read' => 7,
            'title' => 'Adu kamera malam: Redmi Note 14 Pro vs Galaxy A56',
            'excerpt' => 'Dua puluh foto di lima lokasi, tanpa filter dan tanpa retouch.',
            'body' => [
                'Kami memotret lima lokasi yang sama dengan dua HP secara berurutan: gang sempit, kafe berlampu hangat, jalan raya, panggung musik kecil, dan kamar dengan satu lampu meja. Semua diambil mode otomatis, karena itu yang dipakai sehari-hari.',
                'Hasilnya terbagi. Redmi Note 14 Pro menahan detail lebih baik di area gelap, sedangkan A56 lebih konsisten pada warna kulit dan tidak terlalu menaikkan kontras.',
            ],
            'quote' => 'Kalau kamu sering memotret orang, konsistensi warna lebih berharga daripada detail ekstra di bayangan.',
            'bodyRest' => [
                'Untuk video malam, A56 lebih stabil berkat stabilisasi yang lebih tenang. Redmi menang di foto statis, A56 menang begitu ada gerakan.',
            ],
            'tags' => ['Kamera', 'Redmi Note 14 Pro', 'Galaxy A56 5G'],
            'specs' => [
                'title' => 'Kamera utama berdampingan',
                'left' => 'Redmi Note 14 Pro 5G',
                'right' => 'Galaxy A56 5G',
                'rows' => [
                    ['Kamera Utama', '200 MP OIS', '50 MP OIS', 'left'],
                    ['Ultrawide', '8 MP', '12 MP', 'right'],
                    ['Video', '4K 30 fps', '4K 30 fps', null],
                    ['Harga', 'Rp3.999.000', 'Rp5.499.000', 'left'],
                ],
            ],
        ],
    ];

    /** Indonesian month names as they appear in the inlined "dateLong" field. */
    private const array MONTHS = [
        'Januari' => 1, 'Februari' => 2, 'Maret' => 3, 'April' => 4,
        'Mei' => 5, 'Juni' => 6, 'Juli' => 7, 'Agustus' => 8,
        'September' => 9, 'Oktober' => 10, 'November' => 11, 'Desember' => 12,
    ];

    public function run(): void
    {
        foreach (self::ARTICLES as $row) {
            Article::updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'title' => $row['title'],
                    'category' => ArticleCategory::from($row['cat']),
                    'excerpt' => $row['excerpt'],
                    'body' => $this->toHtml($row),
                    'tags' => $row['tags'],
                    'read_minutes' => $row['read'],
                    'published_at' => $this->parsePublishedAt($row['dateLong']),
                ],
            );
        }
    }

    /** "12 Agustus 2026" becomes 2026-08-12. */
    private function parsePublishedAt(string $dateLong): Carbon
    {
        [$day, $month, $year] = explode(' ', $dateLong);

        return Carbon::create((int) $year, self::MONTHS[$month], (int) $day)->startOfDay();
    }

    /** @param  array<string, mixed>  $row */
    private function toHtml(array $row): string
    {
        $html = $this->paragraphs($row['body']);

        if ($row['quote'] !== '') {
            $html .= '<blockquote><p>'.e($row['quote']).'</p></blockquote>';
        }

        $html .= $this->paragraphs($row['bodyRest']);

        if (isset($row['specs'])) {
            $html .= $this->specTable($row['specs']);
        }

        return $html;
    }

    /** @param  list<string>  $prose */
    private function paragraphs(array $prose): string
    {
        return collect($prose)
            ->filter(fn (string $paragraph): bool => trim($paragraph) !== '')
            ->map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>')
            ->implode('');
    }

    /**
     * The editor-authored comparison table, kept as plain HTML. New tables are
     * inserted with the RichEditor's phone comparison block instead.
     *
     * @param  array<string, mixed>  $table
     */
    private function specTable(array $table): string
    {
        $rows = collect($table['rows'])
            ->map(fn (array $row): string => '<tr><th>'.e($row[0]).'</th>'
                .'<td>'.e($row[1]).'</td>'
                .'<td>'.e($row[2]).'</td></tr>')
            ->implode('');

        return '<h3>'.e($table['title']).'</h3>'
            .'<table><thead><tr><th></th><th>'.e($table['left']).'</th>'
            .'<th>'.e($table['right']).'</th></tr></thead>'
            .'<tbody>'.$rows.'</tbody></table>';
    }
}
