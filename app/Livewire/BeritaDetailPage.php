<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\Article;
use App\Support\ArticleRepository;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BeritaDetailPage extends Component
{
    public string $slug = '';

    public function mount(string $slug): void
    {
        abort_if(ArticleRepository::find($slug) === null, 404);

        $this->slug = $slug;
    }

    #[Computed]
    public function article(): Article
    {
        return ArticleRepository::find($this->slug) ?? abort(404);
    }

    /** @return list<Article> */
    #[Computed]
    public function related(): array
    {
        return ArticleRepository::related($this->article());
    }

    /** The page title is per-article, so it cannot be set with the #[Title] attribute. */
    public function render(): View
    {
        return view('livewire.berita-detail-page')
            ->title($this->article()->title.' — Bandingin.id');
    }
}
