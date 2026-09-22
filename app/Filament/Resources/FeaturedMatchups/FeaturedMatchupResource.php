<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeaturedMatchups;

use App\Filament\Resources\FeaturedMatchups\Pages\CreateFeaturedMatchup;
use App\Filament\Resources\FeaturedMatchups\Pages\EditFeaturedMatchup;
use App\Filament\Resources\FeaturedMatchups\Pages\ListFeaturedMatchups;
use App\Filament\Resources\FeaturedMatchups\Schemas\FeaturedMatchupForm;
use App\Filament\Resources\FeaturedMatchups\Tables\FeaturedMatchupsTable;
use App\Models\FeaturedMatchup;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class FeaturedMatchupResource extends Resource
{
    protected static ?string $model = FeaturedMatchup::class;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return FeaturedMatchupForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeaturedMatchupsTable::configure($table);
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
            'index' => ListFeaturedMatchups::route('/'),
            'create' => CreateFeaturedMatchup::route('/create'),
            'edit' => EditFeaturedMatchup::route('/{record}/edit'),
        ];
    }
}
