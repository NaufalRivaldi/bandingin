<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneListings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PhoneListingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('phone_id')
                    ->relationship('phone', 'name')
                    ->required(),
                Select::make('store_id')
                    ->relationship('store', 'name')
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('product_url')
                    ->url(),
                TextInput::make('note'),
                TextInput::make('warranty_tag'),
            ]);
    }
}
