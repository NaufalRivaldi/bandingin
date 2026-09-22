<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\NewsItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Aggregated headlines from external publishers.
 *
 * Every card links off-site. These items deliberately have no route on this
 * site — see docs/superpowers/specs/2026-09-08-tek-id-news-aggregation-design.md.
 */
class NewsFeed extends Component
{
    public const int LIMIT = 8;

    /** @return Collection<int, NewsItem> */
    #[Computed]
    public function items(): Collection
    {
        return NewsItem::published()
            ->latest('published_at')
            ->limit(self::LIMIT)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.news-feed');
    }
}
