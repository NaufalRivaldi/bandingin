<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Livewire\HomePage;
use App\Models\FeaturedMatchup;
use App\Models\Phone;
use Illuminate\Database\Seeder;

/**
 * Ports the hardcoded HomePage::MATCHUPS list. The label is derived from the
 * phone names, so only the two slugs and the ordering are stored.
 */
class FeaturedMatchupSeeder extends Seeder
{
    public function run(): void
    {
        $phones = Phone::pluck('id', 'slug');

        foreach (HomePage::MATCHUPS as $sort => $matchup) {
            FeaturedMatchup::updateOrCreate(
                [
                    'left_phone_id' => $phones[$matchup['left']],
                    'right_phone_id' => $phones[$matchup['right']],
                ],
                ['sort' => $sort + 1],
            );
        }
    }
}
