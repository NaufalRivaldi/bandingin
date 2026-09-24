<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AdFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ad extends Model
{
    /** @use HasFactory<AdFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'weight',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'weight' => 'integer',
        ];
    }

    /**
     * Ads eligible to be served to visitors.
     *
     * @param  Builder<Ad>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
