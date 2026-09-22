<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\FormatsIndonesianDate;
use Database\Factories\NewsItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * An article headline aggregated from an external publisher.
 *
 * These rows are link-out only: they carry no body and deliberately have no
 * slug, because they must never resolve to a page on this site.
 */
class NewsItem extends Model
{
    use FormatsIndonesianDate;

    /** @use HasFactory<NewsItemFactory> */
    use HasFactory;

    protected $fillable = [
        'external_id',
        'source',
        'channel',
        'title',
        'excerpt',
        'url',
        'image_url',
        'published_at',
    ];

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
