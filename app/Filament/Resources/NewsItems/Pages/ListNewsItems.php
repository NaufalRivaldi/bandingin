<?php

declare(strict_types=1);

namespace App\Filament\Resources\NewsItems\Pages;

use App\Filament\Resources\NewsItems\NewsItemResource;
use Filament\Resources\Pages\ListRecords;

class ListNewsItems extends ListRecords
{
    protected static string $resource = NewsItemResource::class;

    /** No CreateAction: these rows come from news:import-tek, not from a human. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
