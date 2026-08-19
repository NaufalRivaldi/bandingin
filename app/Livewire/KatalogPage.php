<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\Phone;
use App\Support\PhoneCatalog;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Katalog HP — Bandingin.id')]
class KatalogPage extends Component
{
    /** How many phones the sticky duel bar accepts before it starts dropping the oldest pick. */
    private const int MAX_PICKS = 2;

    /** @var list<array{id: string, label: string, min: int, max: int}> */
    public const array PRICE_RANGES = [
        ['id' => 'p1', 'label' => 'Rp1–2 juta', 'min' => 1000000, 'max' => 2000000],
        ['id' => 'p2', 'label' => 'Rp2–4 juta', 'min' => 2000000, 'max' => 4000000],
        ['id' => 'p3', 'label' => 'Rp4–7 juta', 'min' => 4000000, 'max' => 7000000],
        ['id' => 'p4', 'label' => 'Rp7 juta ke atas', 'min' => 7000000, 'max' => PHP_INT_MAX],
    ];

    /** @var list<array{id: string, label: string}> */
    public const array SPEC_FILTERS = [
        ['id' => 'ram12', 'label' => 'RAM 12 GB'],
        ['id' => 'hz120', 'label' => 'Layar 120 Hz'],
        ['id' => 'bat5k', 'label' => 'Baterai 5.000 mAh+'],
        ['id' => 'fast', 'label' => 'Isi daya 45W+'],
        ['id' => 'nfc', 'label' => 'Ada NFC'],
        ['id' => 'ip68', 'label' => 'Tahan air IP68'],
    ];

    /** @var list<array{id: string, label: string}> */
    public const array SORTS = [
        ['id' => 'populer', 'label' => 'Populer'],
        ['id' => 'murah', 'label' => 'Termurah'],
        ['id' => 'mahal', 'label' => 'Termahal'],
    ];

    #[Url(as: 'q', except: '')]
    public string $query = '';

    /** @var list<string> */
    #[Url(as: 'harga', except: [])]
    public array $prices = [];

    /** @var list<string> */
    #[Url(as: 'merek', except: [])]
    public array $brands = [];

    /** @var list<string> */
    #[Url(as: 'spek', except: [])]
    public array $specs = [];

    #[Url(as: 'urut', except: 'populer')]
    public string $sort = 'populer';

    /** @var list<string> Phone ids queued for the duel, oldest first. */
    public array $picked = [];

    /** Only meaningful below the 900px breakpoint, where the filter body collapses. */
    public bool $filtersOpen = false;

    /** @return list<Phone> */
    #[Computed]
    public function results(): array
    {
        $results = array_values(array_filter(
            PhoneCatalog::all(),
            fn (Phone $phone): bool => $this->passesAllFilters($phone),
        ));

        if ($this->sort === 'murah') {
            usort($results, static fn (Phone $a, Phone $b): int => $a->price <=> $b->price);
        }

        if ($this->sort === 'mahal') {
            usort($results, static fn (Phone $a, Phone $b): int => $b->price <=> $a->price);
        }

        return $results;
    }

    /**
     * Range counts preview what a price box would add, so they ignore the price filter itself.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function priceCounts(): array
    {
        $counts = [];

        foreach (self::PRICE_RANGES as $range) {
            $counts[$range['id']] = count(array_filter(
                PhoneCatalog::all(),
                fn (Phone $phone): bool => $this->isInRange($phone, $range)
                    && $this->passesQuery($phone)
                    && $this->passesBrands($phone)
                    && $this->passesSpecs($phone),
            ));
        }

        return $counts;
    }

    /** @return list<Phone> */
    #[Computed]
    public function pickedPhones(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $id): ?Phone => PhoneCatalog::find($id),
            $this->picked,
        )));
    }

    #[Computed]
    public function selectionText(): string
    {
        $names = array_map(static fn (Phone $phone): string => $phone->name, $this->pickedPhones());

        return count($names) === self::MAX_PICKS
            ? implode('  vs  ', $names)
            : ($names[0] ?? '').' — pilih satu lagi';
    }

    public function togglePrice(string $id): void
    {
        $this->prices = $this->toggled($this->prices, $id);
    }

    public function toggleBrand(string $brand): void
    {
        $this->brands = $this->toggled($this->brands, $brand);
    }

    public function toggleSpec(string $id): void
    {
        $this->specs = $this->toggled($this->specs, $id);
    }

    public function setSort(string $sort): void
    {
        $this->sort = $sort;
    }

    public function resetFilters(): void
    {
        $this->query = '';
        $this->prices = [];
        $this->brands = [];
        $this->specs = [];
        $this->sort = 'populer';
    }

    public function toggleCompare(string $id): void
    {
        if (in_array($id, $this->picked, true)) {
            $this->picked = $this->toggled($this->picked, $id);

            return;
        }

        $this->picked = count($this->picked) >= self::MAX_PICKS
            ? [$this->picked[count($this->picked) - 1], $id]
            : [...$this->picked, $id];
    }

    public function clearSelection(): void
    {
        $this->picked = [];
    }

    public function toggleFilters(): void
    {
        $this->filtersOpen = ! $this->filtersOpen;
    }

    public function render(): View
    {
        return view('livewire.katalog-page', [
            'priceRanges' => self::PRICE_RANGES,
            'specFilters' => self::SPEC_FILTERS,
            'sorts' => self::SORTS,
            'brandNames' => PhoneCatalog::brands(),
            'totalPhones' => count(PhoneCatalog::all()),
            'maxPicks' => self::MAX_PICKS,
        ]);
    }

    private function passesAllFilters(Phone $phone): bool
    {
        return $this->passesQuery($phone)
            && $this->passesPrices($phone)
            && $this->passesBrands($phone)
            && $this->passesSpecs($phone);
    }

    private function passesQuery(Phone $phone): bool
    {
        return trim($this->query) === '' || $phone->matches($this->query);
    }

    private function passesPrices(Phone $phone): bool
    {
        if ($this->prices === []) {
            return true;
        }

        foreach (self::PRICE_RANGES as $range) {
            if (in_array($range['id'], $this->prices, true) && $this->isInRange($phone, $range)) {
                return true;
            }
        }

        return false;
    }

    private function passesBrands(Phone $phone): bool
    {
        return $this->brands === [] || in_array($phone->brand, $this->brands, true);
    }

    private function passesSpecs(Phone $phone): bool
    {
        foreach ($this->specs as $id) {
            if (! $this->meetsSpec($phone, $id)) {
                return false;
            }
        }

        return true;
    }

    private function meetsSpec(Phone $phone, string $id): bool
    {
        return match ($id) {
            'ram12' => $phone->comparable('ram') >= 12,
            'hz120' => $phone->comparable('refresh') >= 120,
            'bat5k' => $phone->comparable('baterai') >= 5000,
            'fast' => $phone->comparable('charge') >= 45,
            'nfc' => $phone->nfc,
            'ip68' => $phone->ip68,
            default => true,
        };
    }

    /** @param array{id: string, label: string, min: int, max: int} $range */
    private function isInRange(Phone $phone, array $range): bool
    {
        return $phone->price >= $range['min'] && $phone->price < $range['max'];
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function toggled(array $values, string $value): array
    {
        return in_array($value, $values, true)
            ? array_values(array_filter($values, static fn (string $item): bool => $item !== $value))
            : [...$values, $value];
    }
}
