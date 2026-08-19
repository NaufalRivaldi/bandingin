<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PhoneSpecFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneSpec extends Model
{
    /** @use HasFactory<PhoneSpecFactory> */
    use HasFactory;

    protected $fillable = [
        'phone_id',
        'spec_definition_id',
        'display_value',
        'comparable_value',
    ];

    /** @return BelongsTo<Phone, $this> */
    public function phone(): BelongsTo
    {
        return $this->belongsTo(Phone::class);
    }

    /** @return BelongsTo<SpecDefinition, $this> */
    public function definition(): BelongsTo
    {
        return $this->belongsTo(SpecDefinition::class, 'spec_definition_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'comparable_value' => 'integer',
        ];
    }
}
