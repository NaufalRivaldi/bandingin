<?php

declare(strict_types=1);

namespace App\Models;

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
