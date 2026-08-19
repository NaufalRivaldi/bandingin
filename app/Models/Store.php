<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\StoreFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    /** @use HasFactory<StoreFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'is_official',
    ];

    /** @return HasMany<PhoneListing, $this> */
    public function listings(): HasMany
    {
        return $this->hasMany(PhoneListing::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_official' => 'boolean',
        ];
    }
}
