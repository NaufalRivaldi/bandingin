<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Actions\CompareDuel;
use App\Duel\DuelResult;
use App\Livewire\Concerns\SetsPageSeo;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Money\Rupiah;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class PerbandinganPage extends Component
{
    use SetsPageSeo;

    /** How many suggestions the picker dropdown shows. */
    private const int SUGGESTION_LIMIT = 6;

    #[Url]
    public string $left = 'a56';

    #[Url]
    public string $right = 'note14pro';

    /** Which picker dropdown is open: "left", "right", or null when both are closed. */
    public ?string $open = null;

    public string $leftQuery = '';

    public string $rightQuery = '';

    public bool $onlyDifferences = false;

    /** @var list<array{left: string, right: string, label: string}> */
    public const array RELATED = [
        ['left' => 'x7pro', 'right' => 'note14pro', 'label' => 'POCO X7 Pro vs Redmi Note 14 Pro'],
        ['left' => 'a56', 'right' => 'r13pp', 'label' => 'Galaxy A56 vs realme 13 Pro+'],
        ['left' => 'ip16', 'right' => 's25', 'label' => 'iPhone 16 vs Galaxy S25'],
        ['left' => 'note40pro', 'right' => 'note14pro', 'label' => 'Infinix Note 40 Pro vs Redmi Note 14 Pro'],
    ];

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
    public function pairTitle(): string
    {
        return $this->leftPhone()->name.' vs '.$this->rightPhone()->name;
    }

    #[Computed]
    public function duel(): DuelResult
    {
        return app(CompareDuel::class)->handle(
            $this->leftPhone(),
            $this->rightPhone(),
            CompareDuel::FULL_GROUPS,
            $this->onlyDifferences,
        );
    }

    /**
     * One price panel per phone, each with its own real marketplace listings.
     *
     * @return list<array{phone: Phone, lowest: string, sellers: Collection<int, PhoneListing>}>
     */
    #[Computed]
    public function priceCards(): array
    {
        return array_map(
            function (Phone $phone): array {
                $sellers = $this->sellersFor($phone);

                return [
                    'phone' => $phone,
                    'lowest' => Rupiah::format($sellers->min('price') ?? $phone->price),
                    'sellers' => $sellers,
                ];
            },
            [$this->leftPhone(), $this->rightPhone()],
        );
    }

    /** @return Collection<int, PhoneListing> */
    public function sellersFor(Phone $phone): Collection
    {
        return $phone->listings()->with('store')->orderBy('price')->get();
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
        $this->open = $this->open === $side ? null : $side;

        $side === 'left'
            ? $this->leftQuery = ''
            : $this->rightQuery = '';
    }

    public function closePickers(): void
    {
        $this->open = null;
    }

    public function pick(string $side, string $slug): void
    {
        $side === 'left'
            ? $this->left = $slug
            : $this->right = $slug;

        $this->closePickers();
    }

    public function swap(): void
    {
        [$this->left, $this->right] = [$this->right, $this->left];

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
        $this->seo('Bandingkan HP', 'Adu dua HP baris demi baris: chipset, layar, kamera, baterai, dan harga. Kami tandai mana yang menang di setiap spesifikasi.');
        $this->noindexWhenFiltered('left', 'right');

        return view('livewire.perbandingan-page', [
            'related' => self::RELATED,
            'specCount' => array_sum(array_map(
                static fn (array $labels): int => count($labels),
                CompareDuel::FULL_GROUPS,
            )),
        ]);
    }

    /** Falls back to the first published phone so the table is never empty. */
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
}
