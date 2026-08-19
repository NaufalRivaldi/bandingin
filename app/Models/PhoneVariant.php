<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PhoneVariantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneVariant extends Model
{
    /** @use HasFactory<PhoneVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'phone_id',
        'label',
        'price',
        'is_default',
        'sort',
    ];

    /** @return BelongsTo<Phone, $this> */
    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
