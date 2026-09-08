<?php

declare(strict_types=1);

namespace App\Duel;

use App\Models\Phone;

final readonly class DuelResult
{
    /**
     * @param  list<DuelGroup>  $groups
     * @param  list<string>  $leftReasons
     * @param  list<string>  $rightReasons
     */
    public function __construct(
        public Phone $left,
        public Phone $right,
        public array $groups,
        public int $leftWins,
        public int $rightWins,
        public array $leftReasons,
        public array $rightReasons,
    ) {}

    /** @return list<DuelRow> */
    public function rows(): array
    {
        return array_merge(...array_map(
            static fn (DuelGroup $group): array => $group->rows,
            $this->groups,
        ));
    }

    public function decidedCount(): int
    {
        return $this->leftWins + $this->rightWins;
    }

    public function winner(): ?Phone
    {
        if ($this->leftWins === $this->rightWins) {
            return null;
        }

        return $this->leftWins > $this->rightWins ? $this->left : $this->right;
    }

    /**
     * The one-line headline above the duel table, e.g.
     * "Galaxy A56 5G menang di 4 dari 7 kategori".
     */
    public function summary(string $verb = 'menang'): string
    {
        $winner = $this->winner();

        if (! $winner instanceof Phone) {
            return 'Imbang — '.$this->leftWins.' kategori masing-masing';
        }

        return $winner->name.' '.$verb.' di '.max($this->leftWins, $this->rightWins)
            .' dari '.$this->decidedCount().' kategori';
    }

    public function leftShare(): float
    {
        return $this->share($this->leftWins);
    }

    public function rightShare(): float
    {
        return $this->share($this->rightWins);
    }

    private function share(int $wins): float
    {
        $total = $this->decidedCount();

        return $total === 0 ? 50.0 : ($wins / $total) * 100;
    }
}
