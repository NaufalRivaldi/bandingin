<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas')
                    ->columnSpanFull()
                    ->description('Nama dan alamat halaman merek.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->placeholder('Samsung'),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->placeholder('samsung')
                            ->helperText('Dipakai di URL /merek/{slug} — mengubahnya memutus tautan lama.'),
                        Textarea::make('blurb')
                            ->label('Deskripsi')
                            ->required()
                            ->rows(3)
                            ->placeholder('Satu paragraf singkat yang membuka halaman merek.')
                            ->columnSpanFull(),
                    ]),
                Section::make('Posisi di pasar')
                    ->columnSpanFull()
                    ->description('Dua baris ringkas yang tampil di kartu merek.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('bestselling_range')
                            ->label('Rentang terlaris')
                            ->required()
                            ->placeholder('Rp2–4 juta'),
                        TextInput::make('os_update_note')
                            ->label('Update OS')
                            ->required()
                            ->placeholder('4 tahun update OS + 5 tahun patch keamanan'),
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
                                ->placeholder('Dukungan software paling panjang'))
                            ->defaultItems(1)
                            ->reorderable()
                            ->addActionLabel('Tambah kelebihan'),
                        Repeater::make('cons')
                            ->label('Kekurangan')
                            ->simple(TextInput::make('item')
                                ->required()
                                ->placeholder('Harga rilis relatif tinggi'))
                            ->defaultItems(1)
                            ->reorderable()
                            ->addActionLabel('Tambah kekurangan'),
                    ]),
                Section::make('Model unggulan')
                    ->columnSpanFull()
                    ->description('Empat model yang tampil di halaman merek. Model draf pun boleh dipilih.')
                    ->schema([
                        Select::make('highlightedPhones')
                            ->hiddenLabel()
                            ->relationship('highlightedPhones', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih model unggulan'),
                    ]),
            ]);
    }
}
