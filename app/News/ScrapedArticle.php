<?php

declare(strict_types=1);

namespace App\News;

use Carbon\CarbonImmutable;

/**
 * One normalised item from a source feed, optionally carrying the article body
 * and hero image once the article page has been fetched.
 *
 * $publishedAt is always UTC — the feed's Jakarta-local timestamp is converted
 * at the point of parsing, never here.
 */
final readonly class ScrapedArticle
{
    public function __construct(
        public string $externalId,
        public string $title,
        public string $excerpt,
        public string $url,
        public CarbonImmutable $publishedAt,
        public ?string $body = null,
        public ?string $imageUrl = null,
    ) {}

    public function withContent(?string $body, ?string $imageUrl): self
    {
        return new self(
            externalId: $this->externalId,
            title: $this->title,
            excerpt: $this->excerpt,
            url: $this->url,
            publishedAt: $this->publishedAt,
            body: $body,
            imageUrl: $imageUrl,
        );
    }
}
