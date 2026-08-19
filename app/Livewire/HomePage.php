<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CompareDuel;
use App\Data\DuelResult;
use App\Data\Phone;
use App\Support\ArticleRepository;
use App\Support\PhoneCatalog;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Bandingin.id — Jangan Beli HP Sebelum Dibandingkan')]
class HomePage extends Component
{
    public string $left = 'a56';

    public string $right = 'note14pro';

    /** Which picker dropdown is open: "left", "right", or null when both are closed. */
    public ?string $open = null;

    public string $leftQuery = '';

    public string $rightQuery = '';

    public bool $onlyDifferences = false;

    /** @var list<array{left: string, right: string, label: string}> */
    public const array MATCHUPS = [
        ['left' => 'a56', 'right' => 'note14pro', 'label' => 'Galaxy A56 vs Redmi Note 14 Pro'],
        ['left' => 'x7pro', 'right' => 'r13pp', 'label' => 'POCO X7 Pro vs realme 13 Pro+'],
        ['left' => 'ip16', 'right' => 's25', 'label' => 'iPhone 16 vs Galaxy S25'],
        ['left' => 'note40pro', 'right' => 'note14pro', 'label' => 'Infinix Note 40 Pro vs Redmi Note 14 Pro'],
    ];

    public function mount(): void
    {
        $this->syncQueriesToSelection();
    }

    #[Computed]
    public function leftPhone(): Phone
    {
        return PhoneCatalog::findOrFirst($this->left);
    }

    #[Computed]
    public function rightPhone(): Phone
    {
        return PhoneCatalog::findOrFirst($this->right);
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

    /** @return list<Phone> */
    #[Computed]
    public function leftSuggestions(): array
    {
        return PhoneCatalog::search($this->leftQuery);
    }

    /** @return list<Phone> */
    #[Computed]
    public function rightSuggestions(): array
    {
        return PhoneCatalog::search($this->rightQuery);
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

    public function pick(string $side, string $id): void
    {
        $side === 'left'
            ? $this->left = $id
            : $this->right = $id;

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
        return view('livewire.home-page', [
            'matchups' => self::MATCHUPS,
            'featured' => ArticleRepository::featured(),
            'latest' => array_slice(ArticleRepository::all(), 1, 4),
        ]);
    }

    /** Closed pickers show the selected phone's name rather than the search term. */
    private function syncQueriesToSelection(): void
    {
        $this->leftQuery = $this->leftPhone()->name;
        $this->rightQuery = $this->rightPhone()->name;
    }
}
