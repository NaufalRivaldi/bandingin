<?php

declare(strict_types=1);

namespace App\Seo\Concerns;

use Artesaos\SEOTools\Facades\JsonLdMulti;

trait WritesJsonLd
{
    /**
     * Opens a second JSON-LD block of the given type.
     *
     * A new block inherits the config defaults, so the page-level name,
     * description and url are cleared before the block describes its own thing.
     */
    protected function startBlock(string $type): void
    {
        JsonLdMulti::newJsonLd();
        JsonLdMulti::setType($type);
        JsonLdMulti::setTitle('');
        JsonLdMulti::setDescription('');
        JsonLdMulti::setUrl(false);
    }
}
