<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SpecDefinition;
use Illuminate\Database\Seeder;

class SpecDefinitionSeeder extends Seeder
{
    /**
     * Keys match PhoneSeeder::PHONES spec arrays exactly; the loader in
     * PhoneSeeder resolves them by key, so renaming one breaks that seeder.
     *
     * @var list<array{key: string, label: string, group: string}>
     */
    private const array DEFINITIONS = [
        ['key' => 'chipset', 'label' => 'Chipset', 'group' => 'Performa'],
        ['key' => 'antutu', 'label' => 'Skor AnTuTu', 'group' => 'Performa'],
        ['key' => 'ram', 'label' => 'RAM', 'group' => 'Performa'],
        ['key' => 'storage', 'label' => 'Penyimpanan', 'group' => 'Performa'],
        ['key' => 'panel', 'label' => 'Panel', 'group' => 'Layar'],
        ['key' => 'nits', 'label' => 'Kecerahan', 'group' => 'Layar'],
        ['key' => 'layar', 'label' => 'Panel & Kecerahan', 'group' => 'Layar'],
        ['key' => 'refresh', 'label' => 'Refresh Rate', 'group' => 'Layar'],
        ['key' => 'resolusi', 'label' => 'Resolusi', 'group' => 'Layar'],
        ['key' => 'kamera', 'label' => 'Kamera Utama', 'group' => 'Kamera'],
        ['key' => 'ultrawide', 'label' => 'Ultrawide', 'group' => 'Kamera'],
        ['key' => 'selfie', 'label' => 'Kamera Depan', 'group' => 'Kamera'],
        ['key' => 'video', 'label' => 'Rekam Video', 'group' => 'Kamera'],
        ['key' => 'baterai', 'label' => 'Kapasitas', 'group' => 'Baterai & Daya'],
        ['key' => 'charge', 'label' => 'Pengisian Daya', 'group' => 'Baterai & Daya'],
        ['key' => 'ip', 'label' => 'IP Rating', 'group' => 'Bodi & Lainnya'],
        ['key' => 'os', 'label' => 'Software', 'group' => 'Bodi & Lainnya'],
        ['key' => 'osUpdate', 'label' => 'Dukungan Software', 'group' => 'Bodi & Lainnya'],
        ['key' => 'berat', 'label' => 'Berat', 'group' => 'Bodi & Lainnya'],
    ];

    public function run(): void
    {
        foreach (self::DEFINITIONS as $sort => $definition) {
            SpecDefinition::updateOrCreate(
                ['key' => $definition['key']],
                [
                    'label' => $definition['label'],
                    'group' => $definition['group'],
                    'sort' => $sort + 1,
                ],
            );
        }
    }
}
