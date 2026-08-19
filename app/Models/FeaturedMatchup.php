<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\FeaturedMatchupFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeaturedMatchup extends Model
{
    /** @use HasFactory<FeaturedMatchupFactory> */
    use HasFactory;

    protected $fillable = [
        'left_phone_id',
        'right_phone_id',
        'sort',
    ];

    /** @return BelongsTo<Phone, $this> */
    public function leftPhone(): BelongsTo
    {
        return $this->belongsTo(Phone::class, 'left_phone_id');
    }

    /** @return BelongsTo<Phone, $this> */
    public function rightPhone(): BelongsTo
    {
        return $this->belongsTo(Phone::class, 'right_phone_id');
    }

    public function label(): string
    {
        return $this->leftPhone->name.' vs '.$this->rightPhone->name;
    }
}
