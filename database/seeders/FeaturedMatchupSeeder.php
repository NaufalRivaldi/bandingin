<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\FeaturedMatchup;
use App\Models\Phone;
use Illuminate\Database\Seeder;

/**
 * The curated duel shortcuts shown on the home page.
 *
 * The label is derived from the phone names, so only the two slugs and the
 * ordering are stored.
 */
class FeaturedMatchupSeeder extends Seeder
{
    /** @var list<array{left: string, right: string}> */
    private const array MATCHUPS = [
        ['left' => 'a56', 'right' => 'note14pro'],
        ['left' => 'x7pro', 'right' => 'r13pp'],
        ['left' => 'ip16', 'right' => 's25'],
        ['left' => 'note40pro', 'right' => 'note14pro'],
    ];

    public function run(): void
    {
        $phones = Phone::pluck('id', 'slug');

        foreach (self::MATCHUPS as $sort => $matchup) {
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
