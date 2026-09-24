<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\RenderArticleBody;
use App\Livewire\Concerns\SetsPageSeo;
use App\Models\Article;
use App\Seo\ArticleStructuredData;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class BeritaDetailPage extends Component
{
    use SetsPageSeo;

    /** How many articles the related rail shows. */
    private const int RELATED_COUNT = 3;

    public string $slug = '';

    public function mount(string $slug): void
    {
        abort_unless(Article::published()->where('slug', $slug)->exists(), 404);

        $this->slug = $slug;
    }

    #[Computed]
    public function article(): Article
    {
        return Article::published()->where('slug', $this->slug)->firstOrFail();
    }

    #[Computed]
    public function body(): HtmlString
    {
        return app(RenderArticleBody::class)->handle($this->article());
    }

    /**
     * Same-category articles first, then anything else, so the rail is never empty.
     *
     * @return Collection<int, Article>
     */
    #[Computed]
    public function related(): Collection
    {
        $article = $this->article();

        return Article::published()
            ->whereKeyNot($article->getKey())
            ->orderByRaw('case when category = ? then 0 else 1 end', [$article->category->value])
            ->latest('published_at')
            ->take(self::RELATED_COUNT)
            ->get();
    }

    public function render(): View
    {
        $article = $this->article();

        $this->seo($article->title, $article->excerpt);

        app(ArticleStructuredData::class)->handle($article);

        return view('livewire.berita-detail-page');
    }
}
