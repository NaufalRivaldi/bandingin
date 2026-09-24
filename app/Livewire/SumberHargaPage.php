<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\SetsPageSeo;
use Illuminate\View\View;
use Livewire\Component;

class SumberHargaPage extends Component
{
    use SetsPageSeo;

    public function render(): View
    {
        $this->seo('Sumber data harga', 'Dari mana angka harga di Bandingin.id berasal, seberapa sering diperbarui, dan aturan yang kami pakai saat menampilkannya.');

        return view('livewire.sumber-harga-page', [
            'sources' => self::sources(),
            'rules' => self::rules(),
        ]);
    }

    /** @return list<array{name: string, note: string, freq: string, tag: string}> */
    private static function sources(): array
    {
        return [
            ['name' => 'Shopee Mall', 'note' => 'Hanya toko resmi dan Mall, bukan penjual perorangan', 'freq' => '2x sehari', 'tag' => 'Afiliasi'],
            ['name' => 'Tokopedia Official Store', 'note' => 'Toko resmi merek dan distributor terverifikasi', 'freq' => '2x sehari', 'tag' => 'Afiliasi'],
            ['name' => 'TikTok Shop', 'note' => 'Toko resmi merek, termasuk harga flash sale', 'freq' => '4x sehari', 'tag' => 'Afiliasi'],
            ['name' => 'Situs resmi pabrikan', 'note' => 'Daftar harga resmi sebagai patokan pembanding', 'freq' => 'Mingguan', 'tag' => ''],
            ['name' => 'Toko distributor', 'note' => 'Sampel lima toko besar untuk harga non-garansi resmi', 'freq' => 'Mingguan', 'tag' => ''],
        ];
    }

    /** @return list<array{title: string, text: string}> */
    private static function rules(): array
    {
        return [
            ['title' => 'Harga yang ditampilkan sudah bersih', 'text' => 'Angka yang kami tampilkan adalah harga barang tanpa ongkos kirim, dan sudah memperhitungkan potongan otomatis yang berlaku untuk semua pembeli.'],
            ['title' => 'Kupon terbatas tidak dihitung', 'text' => 'Potongan yang butuh kode khusus, kuota harian, atau syarat kartu kredit tertentu tidak kami masukkan, karena tidak semua orang bisa memakainya.'],
            ['title' => 'Garansi resmi dan distributor dipisah', 'text' => 'Harga distributor selalu diberi label sendiri. Selisihnya biasanya Rp200–500 ribu, dengan konsekuensi garansi toko, bukan pabrikan.'],
            ['title' => 'Riwayat harga tidak pernah dihapus', 'text' => 'Titik harga yang sudah tercatat tetap ada di grafik meski toko menaikkan harga kembali, sehingga pola diskon musiman bisa terlihat.'],
        ];
    }
}
