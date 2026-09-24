<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seeds lookup data only. The catalogue itself arrives through the CSV
     * importer on the phones page, so no dummy phones, articles, FAQs,
     * highlights or matchups are planted here. The seeders for those still
     * exist and are used as fixtures by tests/Pest.php::seedCatalog().
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
            'password' => Hash::make('password'),
        ]);

        $this->call([
            SpecDefinitionSeeder::class,
            StoreSeeder::class,
            BrandSeeder::class,
            ScrapeSourceSeeder::class,
        ]);
    }
}
