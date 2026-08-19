<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeaturedMatchups\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class FeaturedMatchupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('left_phone_id')
                    ->label('HP kiri')
                    ->relationship('leftPhone', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('right_phone_id')
                    ->label('HP kanan')
                    ->relationship('rightPhone', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('sort')
                    ->label('Urutan')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
