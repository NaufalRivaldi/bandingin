<?php

declare(strict_types=1);

namespace App\Data;

final readonly class DuelGroup
{
    /** @param list<DuelRow> $rows */
    public function __construct(
        public string $title,
        public array $rows,
    ) {}
}
