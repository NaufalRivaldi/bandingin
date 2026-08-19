<?php

declare(strict_types=1);

namespace App\Data;

final readonly class Phone
{
    /**
     * @param  array<string, SpecValue>  $specs  Keyed by spec key, e.g. "chipset", "baterai".
     * @param  array<string, int>  $scores  Category name => 0-100 score.
     * @param  list<string>  $pros
     * @param  list<string>  $cons
     */
    public function __construct(
        public string $id,
        public string $brand,
        public string $name,
        public int $price,
        public array $specs,
        public array $scores,
        public array $pros,
        public array $cons,
        public bool $nfc,
        public bool $ip68,
        public string $tag,
        public string $dimensi,
        public string $jaringan,
        public string $rilis,
    ) {}

    public function spec(string $key): SpecValue
    {
        return $this->specs[$key] ?? new SpecValue('—', 0);
    }

    public function display(string $key): string
    {
        return $this->spec($key)->display;
    }

    public function comparable(string $key): int
    {
        return $this->spec($key)->comparable;
    }

    public function formattedPrice(): string
    {
        return self::formatRupiah($this->price);
    }

    public function averageScore(): int
    {
        return (int) round(array_sum($this->scores) / max(count($this->scores), 1));
    }

    public function matches(string $query): bool
    {
        return str_contains(mb_strtolower($this->name.' '.$this->brand), mb_strtolower(trim($query)));
    }

    public static function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
