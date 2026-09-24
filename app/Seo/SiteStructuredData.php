<?php

declare(strict_types=1);

namespace App\Seo;

use App\Seo\Concerns\WritesJsonLd;
use Artesaos\SEOTools\Facades\JsonLdMulti;

class SiteStructuredData
{
    use WritesJsonLd;

    public function handle(): void
    {
        JsonLdMulti::setType('WebSite');
        JsonLdMulti::addValue('potentialAction', [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('katalog').'?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ]);

        $this->startBlock('Organization');
        JsonLdMulti::addValues(array_filter([
            'name' => config('seo.organization.name'),
            'url' => route('home'),
            'logo' => config('seo.organization.logo'),
        ]));
    }
}
