<?php

declare(strict_types=1);

namespace App\Seo;

use App\Seo\Concerns\WritesJsonLd;
use Artesaos\SEOTools\Facades\JsonLdMulti;

class BreadcrumbStructuredData
{
    use WritesJsonLd;

    /**
     * Appends a BreadcrumbList block. The home page is always the first crumb.
     *
     * @param  array<string, string>  $trail  label => url, in crumb order
     */
    public function handle(array $trail): void
    {
        $position = 1;
        $items = [];

        foreach (['Beranda' => route('home'), ...$trail] as $label => $url) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $label,
                'item' => $url,
            ];
        }

        $this->startBlock('BreadcrumbList');
        JsonLdMulti::addValue('itemListElement', $items);
    }
}
