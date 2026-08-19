<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneListings\Pages;

use App\Filament\Resources\PhoneListings\PhoneListingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPhoneListing extends EditRecord
{
    protected static string $resource = PhoneListingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
