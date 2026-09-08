<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Brand;
use App\Money\Rupiah;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Merek HP di Indonesia — Bandingin.id')]
class MerekPage extends Component
{
    #[Url]
    public string $brand = 'Xiaomi';

    #[Computed]
    public function activeBrand(): Brand
    {
        return Brand::with('highlightedPhones')->withCount('phones')->firstWhere('name', $this->brand)
            ?? Brand::with('highlightedPhones')->withCount('phones')->orderBy('name')->firstOrFail();
    }

    /**
     * The four figures the design prints. Two are computed from the catalogue
     * and two are editorial columns filled in by an admin.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function stats(): array
    {
        $brand = $this->activeBrand();

        return [
            'Model aktif' => (string) $brand->phones_count,
            'Harga terendah' => self::abbreviate($brand->phones()->min('price')),
            'Rentang terlaris' => $brand->bestselling_range,
            'Update OS' => $brand->os_update_note,
        ];
    }

    /** @return list<string> */
    #[Computed]
    public function brandNames(): array
    {
        return Brand::orderBy('name')->pluck('name')->all();
    }

    public function selectBrand(string $name): void
    {
        $this->brand = $name;
    }

    public function render(): View
    {
        return view('livewire.merek-page');
    }

    /** 1_400_000 becomes "Rp1,4 jt", matching the design's compact stat style. */
    private static function abbreviate(?int $price): string
    {
        if ($price === null) {
            return '—';
        }

        if ($price < 1_000_000) {
            return Rupiah::format($price);
        }

        return 'Rp'.rtrim(rtrim(number_format($price / 1_000_000, 1, ',', '.'), '0'), ',').' jt';
    }
}
