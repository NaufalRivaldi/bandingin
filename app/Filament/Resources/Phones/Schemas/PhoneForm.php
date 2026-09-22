<?php

declare(strict_types=1);

namespace App\Filament\Resources\Phones\Schemas;

use App\Models\SpecDefinition;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PhoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columnSpanFull()
                    ->description('Nama, alamat halaman, dan status tayang.')
                    ->columns(2)
                    ->schema([
                        Select::make('brand_id')
                            ->label('Merek')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih merek'),
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->placeholder('Galaxy A57 5G'),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->placeholder('galaxy-a57-5g')
                            ->helperText('Dipakai di URL /hp/{slug} — mengubahnya memutus tautan lama.'),
                        TextInput::make('price')
                            ->label('Harga resmi')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('5999000')
                            ->helperText('Rupiah bulat tanpa titik. Harga toko diatur terpisah di tab Harga toko.'),
                        TextInput::make('tag')
                            ->label('Label')
                            ->placeholder('Terlaris')
                            ->helperText('Misal "Terlaris" atau "Hemat". Kosongkan kalau tidak ada.'),
                        DateTimePicker::make('published_at')
                            ->label('Tayang sejak')
                            ->placeholder('Kosongkan untuk draf')
                            ->helperText('Kosong berarti draf.'),
                    ]),
                Section::make('Bodi & jaringan')
                    ->columnSpanFull()
                    ->description('Data fisik yang tampil di tabel spesifikasi ringkas.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('dimensions')
                            ->label('Dimensi')
                            ->required()
                            ->placeholder('162,0 x 77,0 x 7,4 mm'),
                        TextInput::make('network')
                            ->label('Jaringan')
                            ->required()
                            ->placeholder('5G Dual SIM · Wi-Fi 6 · NFC'),
                        TextInput::make('released_label')
                            ->label('Rilis (teks)')
                            ->required()
                            ->placeholder('Maret 2025')
                            ->helperText('Seperti yang dibaca pengunjung, misal "Maret 2025".'),
                        DatePicker::make('released_at')
                            ->label('Rilis (tanggal urut)')
                            ->required()
                            ->helperText('Dipakai untuk mengurutkan, bukan untuk ditampilkan.'),
                        Grid::make(2)
                            ->schema([
                                Toggle::make('nfc')
                                    ->label('NFC')
                                    ->helperText('Tampil sebagai filter di katalog.'),
                                Toggle::make('ip68')
                                    ->label('IP68')
                                    ->helperText('Tahan air dan debu bersertifikat.'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Skor')
                    ->columnSpanFull()
                    ->description('Skala 0–100. Skor ini yang menentukan pemenang tiap kategori di kartu duel.')
                    ->schema([
                        Grid::make(5)
                            ->schema([
                                TextInput::make('score_performa')->label('Performa')->required()->numeric()->minValue(0)->maxValue(100)->placeholder('75'),
                                TextInput::make('score_layar')->label('Layar')->required()->numeric()->minValue(0)->maxValue(100)->placeholder('82'),
                                TextInput::make('score_kamera')->label('Kamera')->required()->numeric()->minValue(0)->maxValue(100)->placeholder('79'),
                                TextInput::make('score_baterai')->label('Baterai')->required()->numeric()->minValue(0)->maxValue(100)->placeholder('76'),
                                TextInput::make('score_nilai')->label('Nilai per Rupiah')->required()->numeric()->minValue(0)->maxValue(100)->placeholder('71'),
                            ]),
                    ]),
                Section::make('Plus & minus')
                    ->columnSpanFull()
                    ->description('Poin-poin pendek, satu kalimat per baris.')
                    ->columns(2)
                    ->schema([
                        Repeater::make('pros')
                            ->label('Kelebihan')
                            ->simple(TextInput::make('item')
                                ->required()
                                ->placeholder('Layar cerah di bawah matahari'))
                            ->defaultItems(1)
                            ->reorderable()
                            ->addActionLabel('Tambah kelebihan'),
                        Repeater::make('cons')
                            ->label('Kekurangan')
                            ->simple(TextInput::make('item')
                                ->required()
                                ->placeholder('Pengisian daya lambat'))
                            ->defaultItems(1)
                            ->reorderable()
                            ->addActionLabel('Tambah kekurangan'),
                    ]),
                Section::make('Foto produk')
                    ->columnSpanFull()
                    ->description('Tautan langsung ke gambar. Foto pertama dipakai di kartu katalog; sisanya jadi galeri di halaman detail.')
                    ->schema([
                        Repeater::make('image_urls')
                            ->label('Foto')
                            ->simple(TextInput::make('url')
                                ->required()
                                ->url()
                                ->placeholder('https://contoh.com/galaxy-a57.jpg'))
                            ->defaultItems(0)
                            ->reorderable()
                            ->addActionLabel('Tambah foto'),
                    ]),
                Section::make('Spesifikasi')
                    ->columnSpanFull()
                    ->description('Semua baris spesifikasi untuk HP ini.')
                    ->schema([
                        Repeater::make('specs')
                            ->hiddenLabel()
                            ->relationship('specs')
                            ->schema([
                                Select::make('spec_definition_id')
                                    ->label('Spesifikasi')
                                    ->options(fn (): array => SpecDefinition::orderBy('sort')
                                        ->pluck('label', 'id')
                                        ->all())
                                    ->required()
                                    ->distinct()
                                    ->searchable()
                                    ->placeholder('Pilih baris spesifikasi'),
                                TextInput::make('display_value')
                                    ->label('Nilai tampil')
                                    ->required()
                                    ->placeholder('5.000 mAh')
                                    ->helperText('Persis seperti yang dibaca pengunjung, misal "5.000 mAh".'),
                                TextInput::make('comparable_value')
                                    ->label('Nilai pembanding')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('5000')
                                    ->helperText('Selalu "makin tinggi makin menang". Metrik terbalik ditulis negatif — 198 g ditulis -198.'),
                            ])
                            ->columns(3)
                            ->addActionLabel('Tambah spesifikasi')
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
