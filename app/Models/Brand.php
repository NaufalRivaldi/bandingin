<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'blurb',
        'pros',
        'cons',
        'bestselling_range',
        'os_update_note',
    ];

    /** @return HasMany<Phone, $this> */
    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    /** @return BelongsToMany<Phone, $this> */
    public function highlightedPhones(): BelongsToMany
    {
        return $this->belongsToMany(Phone::class, 'brand_highlights')
            ->withPivot('sort')
            ->withTimestamps()
            ->orderBy('brand_highlights.sort');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'pros' => 'array',
            'cons' => 'array',
        ];
    }
}
