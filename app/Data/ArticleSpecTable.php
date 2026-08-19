<?php

declare(strict_types=1);

namespace App\Data;

/**
 * The small "Kartu Duel" table some articles embed in their body.
 *
 * Winners are authored by the editor rather than computed, because these tables can
 * compare against unreleased phones with estimated figures.
 */
final readonly class ArticleSpecTable
{
    /** @param list<array{label: string, left: string, right: string, winner: ?string}> $rows */
    public function __construct(
        public string $title,
        public string $left,
        public string $right,
        public array $rows,
    ) {}
}
