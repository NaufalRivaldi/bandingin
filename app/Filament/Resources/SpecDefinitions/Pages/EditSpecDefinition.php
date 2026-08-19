<?php

declare(strict_types=1);

namespace App\Filament\Resources\SpecDefinitions\Pages;

use App\Filament\Resources\SpecDefinitions\SpecDefinitionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSpecDefinition extends EditRecord
{
    protected static string $resource = SpecDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
