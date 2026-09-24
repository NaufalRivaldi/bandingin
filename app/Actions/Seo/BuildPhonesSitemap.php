<?php

declare(strict_types=1);

namespace App\Actions\Seo;

use App\Models\Phone;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class BuildPhonesSitemap
{
    public function handle(): Sitemap
    {
        $sitemap = Sitemap::create();

        Phone::published()
            ->select(['id', 'slug', 'updated_at'])
            ->chunkById(500, function ($phones) use ($sitemap): void {
                foreach ($phones as $phone) {
                    $sitemap->add(
                        Url::create(route('hp.show', $phone->slug))
                            ->setLastModificationDate($phone->updated_at)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                            ->setPriority(0.8)
                    );
                }
            });

        return $sitemap;
    }
}
