<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeaturedMatchups\Pages;

use App\Filament\Resources\FeaturedMatchups\FeaturedMatchupResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFeaturedMatchup extends EditRecord
{
    protected static string $resource = FeaturedMatchupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
