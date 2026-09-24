<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use Artesaos\SEOTools\Facades\SEOMeta;
use Artesaos\SEOTools\Facades\SEOTools;

/**
 * Head tags for a full-page Livewire component.
 *
 * Livewire renders the component before the layout, so state set here reaches
 * layouts/app.blade.php. Titles therefore live in render(), not in #[Title].
 */
trait SetsPageSeo
{
    protected function seo(string $title, string $description): void
    {
        SEOTools::setTitle($title);
        SEOTools::setDescription($description);
    }

    /**
     * Mark a faceted variant of this page as noindex.
     *
     * Only the page's own filter parameters count, so a campaign tag such as
     * utm_source can never deindex the clean URL.
     */
    protected function noindexWhenFiltered(string ...$parameters): void
    {
        if (array_intersect($parameters, array_keys(request()->query())) === []) {
            return;
        }

        SEOMeta::setRobots(config('seo.robots.noindex'));
    }
}
