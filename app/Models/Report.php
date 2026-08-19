<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportType;
use Database\Factories\ReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @use HasFactory<ReportFactory>
 */
class Report extends Model
{
    /** @use HasFactory<ReportFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'subject',
        'email',
        'store',
        'claimed_price',
        'product_url',
        'note',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => ReportType::class,
        ];
    }
}
