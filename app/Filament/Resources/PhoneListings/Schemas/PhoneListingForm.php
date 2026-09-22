<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneListings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PhoneListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Sumber harga')
                    ->columnSpanFull()
                    ->description('Kombinasi HP dan toko yang harganya dicatat.')
                    ->columns(2)
                    ->schema([
                        Select::make('phone_id')
                            ->label('HP')
                            ->relationship('phone', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih HP'),
                        Select::make('store_id')
                            ->label('Toko')
                            ->relationship('store', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih toko'),
                    ]),
                Section::make('Harga & catatan')
                    ->columnSpanFull()
                    ->description('Harga tertinggi jadi pembanding, harga terendah tampil sebagai "Termurah".')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price')
                            ->label('Harga')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->placeholder('4299000')
                            ->helperText('Rupiah bulat tanpa titik. Mengubah harga otomatis mencatat riwayat harga baru.'),
                        TextInput::make('warranty_tag')
                            ->label('Garansi')
                            ->maxLength(255)
                            ->placeholder('Garansi resmi')
                            ->helperText('Label pendek yang tampil sebagai badge.'),
                        TextInput::make('product_url')
                            ->label('Tautan produk')
                            ->url()
                            ->maxLength(2000)
                            ->placeholder('https://tokopedia.com/...')
                            ->helperText('Dipakai untuk verifikasi saat ada laporan harga salah.')
                            ->columnSpanFull(),
                        TextInput::make('note')
                            ->label('Catatan')
                            ->maxLength(255)
                            ->placeholder('Harga flash sale sampai akhir bulan')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
