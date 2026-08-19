<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\PhoneListingObserver;
use Database\Factories\PhoneListingFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy(PhoneListingObserver::class)]
class PhoneListing extends Model
{
    /** @use HasFactory<PhoneListingFactory> */
    use HasFactory;

    protected $fillable = [
        'phone_id',
        'store_id',
        'price',
        'product_url',
        'note',
        'warranty_tag',
    ];

    /** @return BelongsTo<Phone, $this> */
    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    /** @return BelongsTo<Store, $this> */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /** @return HasMany<PriceSnapshot, $this> */
    public function snapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class);
    }
}
