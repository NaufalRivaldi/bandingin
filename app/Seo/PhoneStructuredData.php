<?php

declare(strict_types=1);

namespace App\Seo;

use App\Models\Phone;
use Artesaos\SEOTools\Facades\JsonLdMulti;

class PhoneStructuredData
{
    private const string CURRENCY = 'IDR';

    public function __construct(private readonly BreadcrumbStructuredData $breadcrumbs) {}

    public function handle(Phone $phone): void
    {
        JsonLdMulti::setType('Product');
        JsonLdMulti::setTitle($phone->name);
        JsonLdMulti::addValues(array_filter([
            'sku' => $phone->slug,
            'brand' => $phone->brand === null ? null : [
                '@type' => 'Brand',
                'name' => $phone->brand->name,
            ],
            'releaseDate' => $phone->released_at?->toDateString(),
            'offers' => $this->offers($phone),
        ]));

        if ($phone->primaryImage() !== null) {
            JsonLdMulti::setImage($phone->primaryImage());
        }

        $this->breadcrumbs->handle([
            'Katalog HP' => route('katalog'),
            $phone->name => route('hp.show', $phone->slug),
        ]);
    }

    /**
     * Real marketplace listings become an AggregateOffer; with none, the
     * official price stands on its own.
     *
     * @return array<string, mixed>
     */
    private function offers(Phone $phone): array
    {
        $prices = $phone->listings->pluck('price');

        if ($prices->isEmpty()) {
            return [
                '@type' => 'Offer',
                'price' => (string) $phone->price,
                'priceCurrency' => self::CURRENCY,
                'availability' => 'https://schema.org/InStock',
                'url' => route('hp.show', $phone->slug),
            ];
        }

        return [
            '@type' => 'AggregateOffer',
            'lowPrice' => (string) $prices->min(),
            'highPrice' => (string) $prices->max(),
            'offerCount' => $prices->count(),
            'priceCurrency' => self::CURRENCY,
            'availability' => 'https://schema.org/InStock',
            'url' => route('hp.show', $phone->slug),
        ];
    }
}
