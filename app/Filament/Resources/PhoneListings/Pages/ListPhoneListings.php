<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneListings\Pages;

use App\Filament\Resources\PhoneListings\PhoneListingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPhoneListings extends ListRecords
{
    protected static string $resource = PhoneListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
