<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ArticleCategory;
use App\Models\Article;
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

    #[Url(as: 'kategori')]
    public string $category = self::ALL_CATEGORIES;

    public function setCategory(string $category): void
    {
        $this->category = $category;
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
        $query = Article::published()->latest('published_at');

        if (! $this->showsFeatured()) {
            return $query->where('category', $this->category)->get();
        }

        $featured = $this->featured();

        return $query
            ->when($featured !== null, fn ($query) => $query->whereKeyNot($featured->getKey()))
            ->get();
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
