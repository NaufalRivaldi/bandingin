<?php

declare(strict_types=1);

namespace App\Filament\Resources\Brands\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BrandForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('blurb')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('bestselling_range')
                    ->label('Rentang terlaris')
                    ->required(),
                TextInput::make('os_update_note')
                    ->label('Update OS')
                    ->required(),
                Repeater::make('pros')
                    ->label('Kelebihan')
                    ->simple(TextInput::make('item')->required())
                    ->defaultItems(1)
                    ->reorderable()
                    ->columnSpanFull(),
                Repeater::make('cons')
                    ->label('Kekurangan')
                    ->simple(TextInput::make('item')->required())
                    ->defaultItems(1)
                    ->reorderable()
                    ->columnSpanFull(),
                Select::make('highlightedPhones')
                    ->label('Model unggulan')
                    ->relationship('highlightedPhones', 'name')
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->helperText('Empat model yang tampil di halaman merek.')
                    ->columnSpanFull(),
            ]);
    }
}
