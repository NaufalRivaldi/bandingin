<?php

declare(strict_types=1);

namespace App\News;

use Carbon\CarbonImmutable;

/**
 * One normalised item from a tek.id RSS feed. This is the boundary between
 * "XML from the internet" and anything that touches the database.
 *
 * $publishedAt is always UTC — the feed's Jakarta-local timestamp is converted
 * at the point of parsing, never here.
 */
final readonly class ScrapedArticle
{
    public function __construct(
        public string $externalId,
        public TekChannel $channel,
        public string $title,
        public string $excerpt,
        public string $url,
        public CarbonImmutable $publishedAt,
        public ?string $imageUrl = null,
    ) {}

    public function withImage(?string $imageUrl): self
    {
        return new self(
            externalId: $this->externalId,
            channel: $this->channel,
            title: $this->title,
            excerpt: $this->excerpt,
            url: $this->url,
            publishedAt: $this->publishedAt,
            imageUrl: $imageUrl,
        );
    }
}
