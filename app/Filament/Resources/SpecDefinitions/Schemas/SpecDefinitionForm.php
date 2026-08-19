<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecDefinitions\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SpecDefinitionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->required()
                    ->disabledOn('edit')
                    ->helperText('PhoneSeeder dan CompareDuel mencari spesifikasi lewat key ini — mengubahnya memutus keduanya.'),
                TextInput::make('label')
                    ->required(),
                TextInput::make('group'),
                TextInput::make('sort')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
