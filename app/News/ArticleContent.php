<?php

declare(strict_types=1);

namespace App\News;

/** The publishable part of a scraped article page. */
final readonly class ArticleContent
{
    public function __construct(
        public string $body,
        public ?string $imageUrl = null,
    ) {}
}
