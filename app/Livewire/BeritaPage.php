<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ArticleCategory;
use App\Models\Article;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Berita & Ulasan — Bandingin.id')]
class BeritaPage extends Component
{
    public const string ALL_CATEGORIES = 'Semua';

    /** How many articles the grid adds per "Muat lebih banyak". */
    public const int PER_PAGE = 15;

    #[Url(as: 'kategori')]
    public string $category = self::ALL_CATEGORIES;

    /**
     * Deliberately not a #[Url] property: how far someone has scrolled is
     * ephemeral, and putting it in the query string would make shared links
     * load an arbitrary number of articles.
     */
    public int $perPage = self::PER_PAGE;

    public function setCategory(string $category): void
    {
        $this->category = $category;
        $this->resetPage();
    }

    /** Fires when the category is set directly rather than through setCategory(). */
    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function loadMore(): void
    {
        $this->perPage += self::PER_PAGE;
    }

    private function resetPage(): void
    {
        $this->perPage = self::PER_PAGE;
    }

    /**
     * The featured article already has its own hero block on the unfiltered
     * list, so it is dropped from the grid there.
     *
     * @return Collection<int, Article>
     */
    #[Computed]
    public function articles(): Collection
    {
        return $this->listQuery()->limit($this->perPage)->get();
    }

    /** The whole list, not just the page on screen. */
    #[Computed]
    public function total(): int
    {
        return $this->listQuery()->count();
    }

    #[Computed]
    public function hasMore(): bool
    {
        return $this->total() > $this->perPage;
    }

    /**
     * The grid's articles before paging. Kept private so the limit and the
     * count can never drift apart.
     *
     * @return Builder<Article>
     */
    private function listQuery(): Builder
    {
        $query = Article::published()->latest('published_at');

        if (! $this->showsFeatured()) {
            return $query->where('category', $this->category);
        }

        $featured = $this->featured();

        return $query->when($featured !== null, fn ($query) => $query->whereKeyNot($featured->getKey()));
    }

    #[Computed]
    public function featured(): ?Article
    {
        return Article::published()->latest('published_at')->first();
    }

    /** The featured hero only makes sense on the unfiltered list. */
    #[Computed]
    public function showsFeatured(): bool
    {
        return $this->category === self::ALL_CATEGORIES;
    }

    #[Computed]
    public function listHeading(): string
    {
        return $this->showsFeatured() ? 'Terbaru' : $this->category;
    }

    public function render(): View
    {
        return view('livewire.berita-page', [
            'categories' => [self::ALL_CATEGORIES, ...ArticleCategory::values()],
        ]);
    }
}
