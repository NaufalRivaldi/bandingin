<?php

declare(strict_types=1);

namespace App\Filament\Resources\PhoneListings\Pages;

use App\Filament\Resources\PhoneListings\PhoneListingResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhoneListing extends CreateRecord
{
    protected static string $resource = PhoneListingResource::class;
}
