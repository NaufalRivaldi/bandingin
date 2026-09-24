<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\SetsPageSeo;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Models\PhoneVariant;
use App\Money\Rupiah;
use App\Seo\PhoneStructuredData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PhoneDetailPage extends Component
{
    use SetsPageSeo;

    /** Placeholder squares the design shows when a phone has no photos yet. */
    private const int PLACEHOLDER_THUMBNAIL_COUNT = 4;

    private const int RIVAL_COUNT = 4;

    /** The three rows the design closes the spec table with, taken from the phone itself. */
    private const array EXTRA_BODY_ROWS = [
        'Dimensi' => 'dimensions',
        'Jaringan' => 'network',
        'Rilis' => 'released_label',
    ];

    public string $slug = '';

    public int $variant = 0;

    public int $thumb = 0;

    public function mount(string $phone): void
    {
        abort_unless(Phone::published()->where('slug', $phone)->exists(), 404);

        $this->slug = $phone;
    }

    #[Computed]
    public function phone(): Phone
    {
        return Phone::published()
            ->with(['brand', 'specs.definition', 'variants', 'listings.store'])
            ->where('slug', $this->slug)
            ->firstOrFail();
    }

    /** @return Collection<int, PhoneVariant> */
    #[Computed]
    public function variants(): Collection
    {
        return $this->phone()->variants;
    }

    /** The variant the visitor is looking at, or null when the phone lists none. */
    #[Computed]
    public function selectedVariant(): ?PhoneVariant
    {
        return $this->variants()->get($this->variant);
    }

    /** Official price of the variant the visitor is looking at. */
    #[Computed]
    public function variantPrice(): int
    {
        return $this->selectedVariant()?->price ?? $this->phone()->price;
    }

    #[Computed]
    public function officialPriceLabel(): string
    {
        return Rupiah::format($this->variantPrice());
    }

    /**
     * The cheapest real listing.
     *
     * Listings are stored per phone rather than per variant, so this does not move
     * with the variant pills — shifting it would invent a marketplace price we do
     * not have.
     */
    #[Computed]
    public function lowestPriceLabel(): string
    {
        return Rupiah::format($this->sellers()->min('price') ?? $this->phone()->price);
    }

    /** @return Collection<int, PhoneListing> */
    #[Computed]
    public function sellers(): Collection
    {
        return $this->phone()->listings->sortBy('price')->values();
    }

    /** @return list<array{label: string, value: string}> */
    #[Computed]
    public function highlights(): array
    {
        $phone = $this->phone();

        return [
            ['label' => 'Chipset', 'value' => $phone->display('chipset')],
            ['label' => 'Layar', 'value' => $phone->display('refresh').' · '.$phone->display('nits')],
            ['label' => 'Baterai', 'value' => $phone->display('baterai')],
            ['label' => 'Kamera', 'value' => $phone->display('kamera')],
        ];
    }

    /** @return list<array{title: string, rows: list<array{label: string, value: string}>}> */
    #[Computed]
    public function specGroups(): array
    {
        $phone = $this->phone();

        $groups = $phone->specs
            ->filter(fn ($spec): bool => $spec->definition !== null)
            ->sortBy(fn ($spec): int => $spec->definition->sort)
            ->groupBy(fn ($spec): string => $spec->definition->group ?? 'Lainnya')
            ->map(fn ($specs, string $title): array => [
                'title' => $title,
                'rows' => $specs->map(fn ($spec): array => [
                    'label' => $spec->definition->label,
                    'value' => $spec->display_value,
                ])->values()->all(),
            ])
            ->values()
            ->all();

        return $this->appendBodyRows($groups, $phone);
    }

    /** @return Collection<int, Phone> */
    #[Computed]
    public function rivals(): Collection
    {
        $phone = $this->phone();

        $sameBrand = Phone::published()
            ->with(['brand', 'specs.definition'])
            ->where('brand_id', $phone->brand_id)
            ->whereKeyNot($phone->getKey())
            ->take(self::RIVAL_COUNT)
            ->get();

        if ($sameBrand->count() >= self::RIVAL_COUNT) {
            return $sameBrand;
        }

        $others = Phone::published()
            ->with(['brand', 'specs.definition'])
            ->whereKeyNot($phone->getKey())
            ->whereNotIn('id', $sameBrand->pluck('id'))
            ->take(self::RIVAL_COUNT - $sameBrand->count())
            ->get();

        return $sameBrand->concat($others);
    }

    public function selectVariant(int $index): void
    {
        $this->variant = $index;
    }

    /**
     * Gallery photos, in the order they were entered.
     *
     * @return list<string>
     */
    #[Computed]
    public function images(): array
    {
        return $this->phone()->images();
    }

    /** The frame currently in the big slot, or null when the phone has no photos. */
    #[Computed]
    public function selectedImage(): ?string
    {
        return $this->images()[$this->thumb] ?? $this->images()[0] ?? null;
    }

    public function selectThumb(int $index): void
    {
        // Clamped so a stale or crafted index cannot blank the slot.
        $this->thumb = max(0, min($index, count($this->images()) - 1));
    }

    public function render(): View
    {
        $phone = $this->phone();

        $this->seo(
            $phone->name.' — Spesifikasi & Harga',
            'Spesifikasi lengkap, harga resmi, dan harga pasar '.$phone->name.'. Bandingkan chipset, layar, kamera, dan baterainya dengan HP lain.',
        );

        app(PhoneStructuredData::class)->handle($phone);

        return view('livewire.phone-detail-page', [
            'placeholderThumbnails' => range(0, self::PLACEHOLDER_THUMBNAIL_COUNT - 1),
        ]);
    }

    /**
     * @param  list<array{title: string, rows: list<array{label: string, value: string}>}>  $groups
     * @return list<array{title: string, rows: list<array{label: string, value: string}>}>
     */
    private function appendBodyRows(array $groups, Phone $phone): array
    {
        $extra = [];

        foreach (self::EXTRA_BODY_ROWS as $label => $attribute) {
            $extra[] = ['label' => $label, 'value' => (string) $phone->{$attribute}];
        }

        foreach ($groups as $index => $group) {
            if ($group['title'] === 'Bodi & Lainnya') {
                $groups[$index]['rows'] = [...$group['rows'], ...$extra];

                return $groups;
            }
        }

        $groups[] = ['title' => 'Bodi & Lainnya', 'rows' => $extra];

        return $groups;
    }
}
