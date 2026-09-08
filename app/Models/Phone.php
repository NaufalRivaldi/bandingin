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
