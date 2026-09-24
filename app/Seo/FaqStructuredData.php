<?php

declare(strict_types=1);

namespace App\Seo;

use App\Models\Faq;
use Artesaos\SEOTools\Facades\JsonLdMulti;
use Illuminate\Support\Collection;

class FaqStructuredData
{
    /** @param  Collection<int, Faq>  $faqs */
    public function handle(Collection $faqs): void
    {
        if ($faqs->isEmpty()) {
            return;
        }

        JsonLdMulti::setType('FAQPage');
        JsonLdMulti::addValue('mainEntity', $faqs->map(fn (Faq $faq): array => [
            '@type' => 'Question',
            'name' => $faq->question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $faq->answer,
            ],
        ])->values()->all());
    }
}
