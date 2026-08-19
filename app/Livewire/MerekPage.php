<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\Brand;
use App\Support\BrandCatalog;
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
        return BrandCatalog::findOrFirst($this->brand);
    }

    public function selectBrand(string $name): void
    {
        $this->brand = $name;
    }

    public function render(): View
    {
        return view('livewire.merek-page', [
            'brandNames' => BrandCatalog::names(),
        ]);
    }
}
