<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneListings;

use App\Filament\Resources\PhoneListings\Pages\CreatePhoneListing;
use App\Filament\Resources\PhoneListings\Pages\EditPhoneListing;
use App\Filament\Resources\PhoneListings\Pages\ListPhoneListings;
use App\Filament\Resources\PhoneListings\Schemas\PhoneListingForm;
use App\Filament\Resources\PhoneListings\Tables\PhoneListingsTable;
use App\Models\PhoneListing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PhoneListingResource extends Resource
{
    protected static ?string $model = PhoneListing::class;

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?string $navigationLabel = 'Harga toko';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PhoneListingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PhoneListingsTable::configure($table);
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
            'index' => ListPhoneListings::route('/'),
            'create' => CreatePhoneListing::route('/create'),
            'edit' => EditPhoneListing::route('/{record}/edit'),
        ];
    }
}
