<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SpecDefinitionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpecDefinition extends Model
{
    /** @use HasFactory<SpecDefinitionFactory> */
    use HasFactory;

    protected $fillable = [
        'key',
        'label',
        'group',
        'sort',
    ];
}
