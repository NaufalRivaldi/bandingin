<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\SetsPageSeo;
use App\Models\Brand;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class KatalogPage extends Component
{
    use SetsPageSeo;

    /** How many phones the sticky duel bar accepts before it starts dropping the oldest pick. */
    private const int MAX_PICKS = 2;

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

    /** @var list<string> Phone slugs queued for the duel, oldest first. */
    public array $picked = [];

    /** Only meaningful below the 900px breakpoint, where the filter body collapses. */
    public bool $filtersOpen = false;

    /** @return Collection<int, Phone> */
    #[Computed]
    public function results(): Collection
    {
        return $this->baseQuery()
            ->with(['brand', 'specs.definition'])
            ->sortedBy($this->sort)
            ->get();
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

        foreach (array_keys(Phone::PRICE_BANDS) as $bandId) {
            $counts[$bandId] = Phone::published()
                ->search($this->query)
                ->ofBrands($this->brands)
                ->matchingSpecFilters($this->specs)
                ->inPriceBands([$bandId])
                ->count();
        }

        return $counts;
    }

    /** @return Collection<int, Phone> */
    #[Computed]
    public function pickedPhones(): Collection
    {
        if ($this->picked === []) {
            return new Collection;
        }

        $bySlug = Phone::published()->whereIn('slug', $this->picked)->get()->keyBy('slug');

        // Preserve the order the visitor picked them in.
        return new Collection(array_values(array_filter(
            array_map(static fn (string $slug): ?Phone => $bySlug->get($slug), $this->picked),
        )));
    }

    #[Computed]
    public function selectionText(): string
    {
        $names = $this->pickedPhones()->pluck('name')->all();

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

    public function toggleCompare(string $slug): void
    {
        if (in_array($slug, $this->picked, true)) {
            $this->picked = $this->toggled($this->picked, $slug);

            return;
        }

        $this->picked = count($this->picked) >= self::MAX_PICKS
            ? [$this->picked[count($this->picked) - 1], $slug]
            : [...$this->picked, $slug];
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
        $this->seo('Katalog HP', 'Telusuri katalog HP di Indonesia. Saring berdasarkan merek, rentang harga, RAM, layar, baterai, dan NFC, lalu bandingkan pilihanmu berdampingan.');
        $this->noindexWhenFiltered('q', 'harga', 'merek', 'spek', 'urut');

        $specFilters = Phone::SPEC_FILTERS;

        foreach (Phone::FLAG_FILTERS as $id => $label) {
            $specFilters[$id] = ['label' => $label];
        }

        return view('livewire.katalog-page', [
            'priceRanges' => Phone::PRICE_BANDS,
            'specFilters' => $specFilters,
            'sorts' => self::SORTS,
            'brandNames' => Brand::orderBy('name')->pluck('name')->all(),
            'totalPhones' => Phone::published()->count(),
            'maxPicks' => self::MAX_PICKS,
        ]);
    }

    /** @return Builder<Phone> */
    private function baseQuery(): Builder
    {
        return Phone::published()
            ->search($this->query)
            ->inPriceBands($this->prices)
            ->ofBrands($this->brands)
            ->matchingSpecFilters($this->specs);
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
