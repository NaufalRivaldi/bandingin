<?php

declare(strict_types=1);

namespace App\Filament\Resources\Ads\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AdForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(190)
                    ->helperText('Internal label. Visitors never see this.'),
                TextInput::make('url')
                    ->label('Destination URL')
                    ->required()
                    ->url()
                    ->maxLength(2048)
                    ->placeholder('https://example.com/offer'),
                TextInput::make('weight')
                    ->required()
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(100)
                    ->default(1)
                    ->helperText('Relative share of traffic against the other active ads.'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Inactive ads are never served.'),
            ])
            ->columns(1);
    }
}
