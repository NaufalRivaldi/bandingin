<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ArticleCategory;
use App\Models\ScrapeSource;
use Illuminate\Database\Seeder;

class ScrapeSourceSeeder extends Seeder
{
    /**
     * tek.id's seven channels mapped onto our four categories.
     *
     * The mapping is a starting point, not a rule — it is editable in the
     * admin panel, which is the whole point of sources being data.
     *
     * @var list<array{string, string, ArticleCategory}>
     */
    private const array SOURCES = [
        ['tek.id — Tek', 'tek', ArticleCategory::Rilis],
        ['tek.id — Gadget', 'gadget', ArticleCategory::Rilis],
        ['tek.id — Review', 'review', ArticleCategory::Ulasan],
        ['tek.id — Culture', 'culture', ArticleCategory::Panduan],
        ['tek.id — Future', 'future', ArticleCategory::Rilis],
        ['tek.id — Insight', 'insight', ArticleCategory::Panduan],
        ['tek.id — Tektalk', 'tektalk', ArticleCategory::Panduan],
    ];

    public function run(): void
    {
        foreach (self::SOURCES as [$name, $path, $category]) {
            ScrapeSource::updateOrCreate(
                ['feed_url' => 'https://www.tek.id/'.$path.'/rss'],
                ['name' => $name, 'category' => $category],
            );
        }
    }
}
