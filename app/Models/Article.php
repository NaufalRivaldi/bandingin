<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleCategory;
use App\Models\Concerns\FormatsIndonesianDate;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use FormatsIndonesianDate;

    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'title',
        'category',
        'excerpt',
        'body',
        'tags',
        'read_minutes',
        'published_at',
        'source',
        'source_url',
        'external_id',
        'image_url',
    ];

    public function meta(): string
    {
        return $this->category->value.' · '.$this->dateShort();
    }

    /** @param  Builder<$this>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /** @param  Builder<$this>  $query */
    public function scopeScraped(Builder $query): void
    {
        $query->whereNotNull('source');
    }

    /** @param  Builder<$this>  $query */
    public function scopeOwn(Builder $query): void
    {
        $query->whereNull('source');
    }

    /** Scraped articles carry a credit line and a canonical tag; ours do not. */
    public function isScraped(): bool
    {
        return $this->source !== null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
            'tags' => 'array',
            'published_at' => 'datetime',
        ];
    }
}
