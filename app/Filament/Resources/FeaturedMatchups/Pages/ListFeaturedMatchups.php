<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeaturedMatchups\Pages;

use App\Filament\Resources\FeaturedMatchups\FeaturedMatchupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFeaturedMatchups extends ListRecords
{
    protected static string $resource = FeaturedMatchupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
