<?php

declare(strict_types=1);

namespace App\Data;

final readonly class DuelRow
{
    public function __construct(
        public string $key,
        public string $label,
        public string $leftValue,
        public string $rightValue,
        public bool $leftWins,
        public bool $rightWins,
    ) {}

    public function isTie(): bool
    {
        return ! $this->leftWins && ! $this->rightWins;
    }
}
