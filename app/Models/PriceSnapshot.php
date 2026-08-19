<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'phone_listing_id',
        'price',
        'recorded_at',
    ];

    /** @return BelongsTo<PhoneListing, $this> */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(PhoneListing::class, 'phone_listing_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }
}
