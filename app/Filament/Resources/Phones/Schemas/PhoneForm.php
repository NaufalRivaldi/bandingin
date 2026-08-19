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
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PhoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columns(2)
                    ->schema([
                        Select::make('brand_id')
                            ->label('Merek')
                            ->relationship('brand', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Nama')
                            ->required(),
                        TextInput::make('slug')
                            ->required()
                            ->helperText('Dipakai di URL /hp/{slug} — mengubahnya memutus tautan lama.'),
                        TextInput::make('price')
                            ->label('Harga resmi')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        TextInput::make('tag')
                            ->label('Label')
                            ->helperText('Misal "Terlaris" atau "Hemat". Kosongkan kalau tidak ada.'),
                        DateTimePicker::make('published_at')
                            ->label('Tayang sejak')
                            ->helperText('Kosong berarti draf.'),
                    ]),
                Section::make('Bodi & jaringan')
                    ->columns(2)
                    ->schema([
                        Toggle::make('nfc')
                            ->label('NFC'),
                        Toggle::make('ip68')
                            ->label('IP68'),
                        TextInput::make('dimensions')
                            ->label('Dimensi')
                            ->required(),
                        TextInput::make('network')
                            ->label('Jaringan')
                            ->required(),
                        TextInput::make('released_label')
                            ->label('Rilis (teks)')
                            ->required()
                            ->helperText('Seperti yang dibaca pengunjung, misal "Maret 2025".'),
                        DatePicker::make('released_at')
                            ->label('Rilis (tanggal urut)')
                            ->required(),
                    ]),
                Section::make('Skor')
                    ->columns(5)
                    ->schema([
                        TextInput::make('score_performa')->label('Performa')->required()->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('score_layar')->label('Layar')->required()->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('score_kamera')->label('Kamera')->required()->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('score_baterai')->label('Baterai')->required()->numeric()->minValue(0)->maxValue(100),
                        TextInput::make('score_nilai')->label('Nilai per Rupiah')->required()->numeric()->minValue(0)->maxValue(100),
                    ]),
                Section::make('Plus & minus')
                    ->columns(2)
                    ->schema([
                        Repeater::make('pros')
                            ->label('Kelebihan')
                            ->simple(TextInput::make('item')->required())
                            ->defaultItems(1)
                            ->reorderable(),
                        Repeater::make('cons')
                            ->label('Kekurangan')
                            ->simple(TextInput::make('item')->required())
                            ->defaultItems(1)
                            ->reorderable(),
                    ]),
                Section::make('Spesifikasi')
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
                                    ->searchable(),
                                TextInput::make('display_value')
                                    ->label('Nilai tampil')
                                    ->required()
                                    ->helperText('Persis seperti yang dibaca pengunjung, misal "5.000 mAh".'),
                                TextInput::make('comparable_value')
                                    ->label('Nilai pembanding')
                                    ->numeric()
                                    ->required()
                                    ->helperText('Selalu "makin tinggi makin menang". Metrik terbalik ditulis negatif — 198 g ditulis -198.'),
                            ])
                            ->columns(3)
                            ->addActionLabel('Tambah spesifikasi')
                            ->defaultItems(0),
                    ]),
            ]);
    }
}
