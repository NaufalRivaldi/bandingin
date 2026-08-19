<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecDefinitions\Pages;

use App\Filament\Resources\SpecDefinitions\SpecDefinitionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSpecDefinition extends CreateRecord
{
    protected static string $resource = SpecDefinitionResource::class;
}
