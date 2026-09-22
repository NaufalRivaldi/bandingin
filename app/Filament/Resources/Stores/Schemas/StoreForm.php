<?php

declare(strict_types=1);

namespace App\Filament\Resources\Stores\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class StoreForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Toko')
                    ->columnSpanFull()
                    ->description('Tempat listing harga menempel.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->placeholder('Tokopedia'),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->placeholder('tokopedia')
                            ->helperText('Penanda tetap toko — dipakai untuk mencocokkan listing.'),
                        Toggle::make('is_official')
                            ->label('Toko resmi')
                            ->helperText('Aktif kalau ini toko/brand store resmi, bukan reseller.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
