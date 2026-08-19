<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeaturedMatchups\Pages;

use App\Filament\Resources\FeaturedMatchups\FeaturedMatchupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeaturedMatchup extends CreateRecord
{
    protected static string $resource = FeaturedMatchupResource::class;
}
