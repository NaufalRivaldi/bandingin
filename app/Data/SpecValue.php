<?php

declare(strict_types=1);

namespace App\Data;

/**
 * A single specification: what the user reads, plus the number the duel compares on.
 *
 * Higher `comparable` always means better, so inverted metrics (weight, price) are
 * stored negated by whoever builds the value.
 */
final readonly class SpecValue
{
    public function __construct(
        public string $display,
        public int $comparable,
    ) {}
}
