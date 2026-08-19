<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecDefinitions;

use App\Filament\Resources\SpecDefinitions\Pages\CreateSpecDefinition;
use App\Filament\Resources\SpecDefinitions\Pages\EditSpecDefinition;
use App\Filament\Resources\SpecDefinitions\Pages\ListSpecDefinitions;
use App\Filament\Resources\SpecDefinitions\Schemas\SpecDefinitionForm;
use App\Filament\Resources\SpecDefinitions\Tables\SpecDefinitionsTable;
use App\Models\SpecDefinition;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class SpecDefinitionResource extends Resource
{
    protected static ?string $model = SpecDefinition::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    public static function form(Schema $schema): Schema
    {
        return SpecDefinitionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SpecDefinitionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSpecDefinitions::route('/'),
            'create' => CreateSpecDefinition::route('/create'),
            'edit' => EditSpecDefinition::route('/{record}/edit'),
        ];
    }
}
