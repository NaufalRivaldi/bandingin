<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeaturedMatchups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeaturedMatchupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Duel')
                    ->columnSpanFull()
                    ->description('Pasangan HP yang tampil sebagai kartu duel di halaman depan.')
                    ->columns(3)
                    ->schema([
                        Select::make('left_phone_id')
                            ->label('HP kiri')
                            ->relationship('leftPhone', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih HP kiri'),
                        Select::make('right_phone_id')
                            ->label('HP kanan')
                            ->relationship('rightPhone', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih HP kanan'),
                        TextInput::make('sort')
                            ->label('Urutan')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->placeholder('10')
                            ->helperText('Makin kecil makin awal tampil.'),
                    ]),
            ]);
    }
}
