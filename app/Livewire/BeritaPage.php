<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\Article;
use App\Support\ArticleRepository;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Berita & Ulasan — Bandingin.id')]
class BeritaPage extends Component
{
    #[Url(as: 'kategori')]
    public string $category = ArticleRepository::ALL_CATEGORIES;

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    /** @return list<Article> */
    #[Computed]
    public function articles(): array
    {
        return ArticleRepository::forCategory($this->category);
    }

    /** The featured hero only makes sense on the unfiltered list. */
    #[Computed]
    public function showsFeatured(): bool
    {
        return $this->category === ArticleRepository::ALL_CATEGORIES;
    }

    #[Computed]
    public function listHeading(): string
    {
        return $this->showsFeatured() ? 'Terbaru' : $this->category;
    }

    public function render(): View
    {
        return view('livewire.berita-page', [
            'categories' => ArticleRepository::categories(),
            'featured' => ArticleRepository::featured(),
        ]);
    }
}
