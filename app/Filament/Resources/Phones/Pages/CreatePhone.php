<?php

declare(strict_types=1);

namespace App\Filament\Resources\Phones\Pages;

use App\Filament\Resources\Phones\PhoneResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePhone extends CreateRecord
{
    protected static string $resource = PhoneResource::class;
}
