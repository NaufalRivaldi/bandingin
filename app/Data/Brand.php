<?php

declare(strict_types=1);

namespace App\Data;

final readonly class Brand
{
    /**
     * @param  array<string, string>  $stats  Label => value, e.g. "Model aktif" => "14".
     * @param  list<array{name: string, price: string, spec: string}>  $models
     * @param  list<string>  $pros
     * @param  list<string>  $cons
     */
    public function __construct(
        public string $name,
        public string $blurb,
        public array $stats,
        public array $models,
        public array $pros,
        public array $cons,
    ) {}
}
