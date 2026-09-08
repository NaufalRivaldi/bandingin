<?php

declare(strict_types=1);

namespace App\Models;

use App\Money\Rupiah;
use Database\Factories\PhoneFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Phone extends Model
{
    /** @use HasFactory<PhoneFactory> */
    use HasFactory;

    /**
     * Catalogue price bands. Shared by the filter scope and the sidebar counts,
     * so they must not be duplicated in the Livewire component.
     *
     * @var array<string, array{label: string, min: int, max: int}>
     */
    public const array PRICE_BANDS = [
        'p1' => ['label' => 'Rp1–2 juta', 'min' => 1_000_000, 'max' => 2_000_000],
        'p2' => ['label' => 'Rp2–4 juta', 'min' => 2_000_000, 'max' => 4_000_000],
        'p3' => ['label' => 'Rp4–7 juta', 'min' => 4_000_000, 'max' => 7_000_000],
        'p4' => ['label' => 'Rp7 juta ke atas', 'min' => 7_000_000, 'max' => PHP_INT_MAX],
    ];

    /**
     * Minimum-spec filters that resolve against phone_specs rows.
     *
     * @var array<string, array{label: string, key: string, min: int}>
     */
    public const array SPEC_FILTERS = [
        'ram12' => ['label' => 'RAM 12 GB', 'key' => 'ram', 'min' => 12],
        'hz120' => ['label' => 'Layar 120 Hz', 'key' => 'refresh', 'min' => 120],
        'bat5k' => ['label' => 'Baterai 5.000 mAh+', 'key' => 'baterai', 'min' => 5000],
        'fast' => ['label' => 'Isi daya 45W+', 'key' => 'charge', 'min' => 45],
    ];

    /**
     * Filters that are plain boolean columns rather than spec rows.
     *
     * @var array<string, string>
     */
    public const array FLAG_FILTERS = [
        'nfc' => 'Ada NFC',
        'ip68' => 'Tahan air IP68',
    ];

    protected $fillable = [
        'brand_id',
        'slug',
        'name',
        'price',
        'tag',
        'nfc',
        'ip68',
        'dimensions',
        'network',
        'released_label',
        'released_at',
        'pros',
        'cons',
        'score_performa',
        'score_layar',
        'score_kamera',
        'score_baterai',
        'score_nilai',
        'published_at',
    ];

    /** @return BelongsTo<Brand, $this> */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /** @return HasMany<PhoneSpec, $this> */
    public function specs(): HasMany
    {
        return $this->hasMany(PhoneSpec::class);
    }

    /** @return HasMany<PhoneListing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(PhoneListing::class);
    }

    /** @return HasMany<PhoneVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(PhoneVariant::class)->orderBy('sort');
    }

    public function defaultVariant(): ?PhoneVariant
    {
        return $this->variants->firstWhere('is_default', true);
    }

    /**
     * The five category scores, keyed by the labels the design prints.
     *
     * @return array<string, int>
     */
    public function scores(): array
    {
        return [
            'Performa' => $this->score_performa,
            'Layar' => $this->score_layar,
            'Kamera' => $this->score_kamera,
            'Baterai' => $this->score_baterai,
            'Nilai per Rupiah' => $this->score_nilai,
        ];
    }

    public function averageScore(): int
    {
        $scores = $this->scores();

        return (int) round(array_sum($scores) / count($scores));
    }

    public function formattedPrice(): string
    {
        return Rupiah::format($this->price);
    }

    /** Reads the loaded specs relation — eager-load specs.definition before calling. */
    public function display(string $key): string
    {
        return $this->specFor($key)?->display_value ?? '—';
    }

    /** Reads the loaded specs relation — eager-load specs.definition before calling. */
    public function comparable(string $key): int
    {
        return $this->specFor($key)?->comparable_value ?? 0;
    }

    private function specFor(string $key): ?PhoneSpec
    {
        return $this->specs->first(
            static fn (PhoneSpec $spec): bool => $spec->definition?->key === $key,
        );
    }

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /** @param  Builder<$this>  $query */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', '%'.$term.'%')
                ->orWhereRelation('brand', 'name', 'like', '%'.$term.'%');
        });
    }

    /**
     * @param  Builder<$this>  $query
     * @param  list<string>  $bandIds
     */
    public function scopeInPriceBands(Builder $query, array $bandIds): void
    {
        $bands = array_intersect_key(self::PRICE_BANDS, array_flip($bandIds));

        if ($bands === []) {
            return;
        }

        // Upper bound is exclusive so adjacent bands never both claim a price.
        $query->where(function (Builder $query) use ($bands): void {
            foreach ($bands as $band) {
                $query->orWhere(fn (Builder $band2): Builder => $band2
                    ->where('price', '>=', $band['min'])
                    ->where('price', '<', $band['max']));
            }
        });
    }

    /**
     * @param  Builder<$this>  $query
     * @param  list<string>  $brandNames
     */
    public function scopeOfBrands(Builder $query, array $brandNames): void
    {
        if ($brandNames === []) {
            return;
        }

        $query->whereHas('brand', fn (Builder $brand): Builder => $brand->whereIn('name', $brandNames));
    }

    /**
     * Every selected filter must match, so each becomes its own constraint.
     *
     * @param  Builder<$this>  $query
     * @param  list<string>  $filterIds
     */
    public function scopeMatchingSpecFilters(Builder $query, array $filterIds): void
    {
        foreach ($filterIds as $id) {
            if (isset(self::FLAG_FILTERS[$id])) {
                $query->where($id, true);

                continue;
            }

            $filter = self::SPEC_FILTERS[$id] ?? null;

            if ($filter === null) {
                continue;
            }

            $query->whereHas('specs', fn (Builder $spec): Builder => $spec
                ->where('comparable_value', '>=', $filter['min'])
                ->whereHas('definition', fn (Builder $definition): Builder => $definition
                    ->where('key', $filter['key'])));
        }
    }

    /** @param  Builder<$this>  $query */
    public function scopeSortedBy(Builder $query, string $sort): void
    {
        match ($sort) {
            'murah' => $query->orderBy('price'),
            'mahal' => $query->orderByDesc('price'),
            default => $query->orderByDesc('released_at'),
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'nfc' => 'boolean',
            'ip68' => 'boolean',
            'released_at' => 'date',
            'published_at' => 'datetime',
            'pros' => 'array',
            'cons' => 'array',
        ];
    }
}
