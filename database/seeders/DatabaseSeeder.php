<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->call([
            SpecDefinitionSeeder::class,
            StoreSeeder::class,
            BrandSeeder::class,
            PhoneSeeder::class,
            BrandHighlightSeeder::class,
            ArticleSeeder::class,
            FaqSeeder::class,
            FeaturedMatchupSeeder::class,
            ScrapeSourceSeeder::class,
        ]);
    }
}
