<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CompareDuel;
use App\Duel\DuelResult;
use App\Models\Article;
use App\Models\FeaturedMatchup;
use App\Models\Phone;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Bandingin.id — Jangan Beli HP Sebelum Dibandingkan')]
class HomePage extends Component
{
    /** How many suggestions the picker dropdown shows. */
    private const int SUGGESTION_LIMIT = 6;

    public string $left = 'a56';

    public string $right = 'note14pro';

    /** Which picker dropdown is open: "left", "right", or null when both are closed. */
    public ?string $open = null;

    public string $leftQuery = '';

    public string $rightQuery = '';

    public bool $onlyDifferences = false;

    public function mount(): void
    {
        $this->syncQueriesToSelection();
    }

    #[Computed]
    public function leftPhone(): Phone
    {
        return $this->resolve($this->left);
    }

    #[Computed]
    public function rightPhone(): Phone
    {
        return $this->resolve($this->right);
    }

    #[Computed]
    public function duel(): DuelResult
    {
        return app(CompareDuel::class)->handle(
            $this->leftPhone(),
            $this->rightPhone(),
            ['' => CompareDuel::SUMMARY_ROWS],
            $this->onlyDifferences,
        );
    }

    /** @return Collection<int, Phone> */
    #[Computed]
    public function leftSuggestions(): Collection
    {
        return $this->suggestions($this->leftQuery);
    }

    /** @return Collection<int, Phone> */
    #[Computed]
    public function rightSuggestions(): Collection
    {
        return $this->suggestions($this->rightQuery);
    }

    public function openPicker(string $side): void
    {
        $this->open = $side;

        $side === 'left'
            ? $this->leftQuery = ''
            : $this->rightQuery = '';
    }

    public function closePickers(): void
    {
        $this->open = null;
        $this->syncQueriesToSelection();
    }

    public function pick(string $side, string $slug): void
    {
        $side === 'left'
            ? $this->left = $slug
            : $this->right = $slug;

        $this->closePickers();
    }

    public function setMatchup(string $left, string $right): void
    {
        $this->left = $left;
        $this->right = $right;

        $this->closePickers();
    }

    public function toggleOnlyDifferences(): void
    {
        $this->onlyDifferences = ! $this->onlyDifferences;
    }

    public function render(): View
    {
        $articles = Article::published()->latest('published_at')->take(5)->get();

        return view('livewire.home-page', [
            'matchups' => FeaturedMatchup::with(['leftPhone', 'rightPhone'])->orderBy('sort')->get(),
            'featured' => $articles->first(),
            'latest' => $articles->skip(1)->values(),
        ]);
    }

    /** Falls back to the first published phone so the duel card is never empty. */
    private function resolve(string $slug): Phone
    {
        return Phone::published()->with('specs.definition')->firstWhere('slug', $slug)
            ?? Phone::published()->with('specs.definition')->orderBy('id')->firstOrFail();
    }

    /** @return Collection<int, Phone> */
    private function suggestions(string $query): Collection
    {
        return Phone::published()
            ->with('brand')
            ->search($query)
            ->orderBy('id')
            ->take(self::SUGGESTION_LIMIT)
            ->get();
    }

    /** Closed pickers show the selected phone's name rather than the search term. */
    private function syncQueriesToSelection(): void
    {
        $this->leftQuery = $this->leftPhone()->name;
        $this->rightQuery = $this->rightPhone()->name;
    }
}
