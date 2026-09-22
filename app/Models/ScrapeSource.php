<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ArticleCategory;
use Database\Factories\ScrapeSourceFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One external feed to scrape, plus the category its articles become.
 *
 * This is what replaced the hardcoded channel enum: adding a site is a row,
 * not a deploy, and the feed-to-category mapping is the owner's decision
 * rather than a guess in code.
 */
class ScrapeSource extends Model
{
    /** @use HasFactory<ScrapeSourceFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'feed_url',
        'category',
        'is_active',
        'last_run_at',
        'last_error',
    ];

    /** @param  Builder<$this>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Records the outcome of a run so the admin table shows feed health. */
    public function markRan(?string $error = null): void
    {
        $this->forceFill([
            'last_run_at' => now(),
            'last_error' => $error,
        ])->save();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'category' => ArticleCategory::class,
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }
}
