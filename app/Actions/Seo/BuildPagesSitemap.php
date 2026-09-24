<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class BuildPagesSitemap
{
    /**
     * Static routes, with the home page weighted above the rest.
     *
     * @var array<string, float>
     */
    private const array ROUTES = [
        'home' => 1.0,
        'katalog' => 0.9,
        'bandingkan' => 0.9,
        'merek' => 0.7,
        'berita' => 0.7,
        'faq' => 0.5,
        'cara-menilai' => 0.4,
        'sumber-harga' => 0.4,
        'lapor' => 0.3,
        'legal' => 0.2,
    ];

    public function handle(): Sitemap
    {
        $sitemap = Sitemap::create();

        foreach (self::ROUTES as $name => $priority) {
            $sitemap->add(
                Url::create(route($name))
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                    ->setPriority($priority)
            );
        }

        return $sitemap;
    }
}
