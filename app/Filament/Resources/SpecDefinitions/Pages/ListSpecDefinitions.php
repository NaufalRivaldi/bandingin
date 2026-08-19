<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecDefinitions\Pages;

use App\Filament\Resources\SpecDefinitions\SpecDefinitionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSpecDefinitions extends ListRecords
{
    protected static string $resource = SpecDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
