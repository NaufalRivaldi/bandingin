<?php

declare(strict_types=1);

namespace App\Filament\Resources\ScrapeSources;

use App\Filament\Resources\ScrapeSources\Pages\CreateScrapeSource;
use App\Filament\Resources\ScrapeSources\Pages\EditScrapeSource;
use App\Filament\Resources\ScrapeSources\Pages\ListScrapeSources;
use App\Filament\Resources\ScrapeSources\Schemas\ScrapeSourceForm;
use App\Filament\Resources\ScrapeSources\Tables\ScrapeSourcesTable;
use App\Models\ScrapeSource;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ScrapeSourceResource extends Resource
{
    protected static ?string $model = ScrapeSource::class;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Sumber Scraping';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return ScrapeSourceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ScrapeSourcesTable::configure($table);
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
            'index' => ListScrapeSources::route('/'),
            'create' => CreateScrapeSource::route('/create'),
            'edit' => EditScrapeSource::route('/{record}/edit'),
        ];
    }
}
