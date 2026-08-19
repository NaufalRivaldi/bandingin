<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Data\Phone;
use App\Support\PhoneCatalog;
use App\Support\SellerPricing;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class PhoneDetailPage extends Component
{
    /** How many gallery thumbnails the design shows under the product shot. */
    private const int THUMBNAIL_COUNT = 4;

    private const int RIVAL_COUNT = 4;

    /**
     * The spec table, grouped as the design groups it. Values resolve against spec keys,
     * falling back to the phone's own properties for the three that are not specs.
     *
     * @var array<string, array<string, string>>
     */
    public const array SPEC_GROUPS = [
        'Performa' => [
            'chipset' => 'Chipset',
            'antutu' => 'Skor AnTuTu',
            'ram' => 'RAM',
            'storage' => 'Penyimpanan',
        ],
        'Layar' => [
            'panel' => 'Panel',
            'nits' => 'Kecerahan',
            'refresh' => 'Refresh Rate',
            'resolusi' => 'Resolusi',
        ],
        'Kamera' => [
            'kamera' => 'Kamera Utama',
            'ultrawide' => 'Ultrawide',
            'selfie' => 'Kamera Depan',
            'video' => 'Rekam Video',
        ],
        'Baterai & Daya' => [
            'baterai' => 'Kapasitas',
            'charge' => 'Pengisian Daya',
        ],
        'Bodi & Lainnya' => [
            'ip' => 'IP Rating',
            'os' => 'Software',
            'berat' => 'Berat',
            'dimensi' => 'Dimensi',
            'jaringan' => 'Jaringan',
            'rilis' => 'Rilis',
        ],
    ];

    public string $phoneId = '';

    public int $variant = 0;

    public int $thumb = 0;

    public function mount(string $phone): void
    {
        $resolved = PhoneCatalog::find($phone);

        if (! $resolved instanceof Phone) {
            abort(404);
        }

        $this->phoneId = $resolved->id;
    }

    #[Computed]
    public function phone(): Phone
    {
        return PhoneCatalog::findOrFirst($this->phoneId);
    }

    /** Official price of the variant the visitor is looking at. */
    #[Computed]
    public function variantPrice(): int
    {
        return $this->phone()->price + (SellerPricing::VARIANTS[$this->variant]['delta'] ?? 0);
    }

    /**
     * Cheapest listing for the selected variant.
     *
     * Equivalent to SellerPricing::lowestPriceLabel() on the base variant, but the
     * variant pills shift every price on the page, this headline included.
     */
    #[Computed]
    public function lowestPriceLabel(): string
    {
        return Phone::formatRupiah(SellerPricing::lowestPrice($this->variantPrice()));
    }

    #[Computed]
    public function officialPriceLabel(): string
    {
        return Phone::formatRupiah($this->variantPrice());
    }

    /** @return list<array{store: string, note: string, price: string, tag: string}> */
    #[Computed]
    public function sellers(): array
    {
        return SellerPricing::detailed($this->variantPrice());
    }

    /** @return list<array{label: string, value: string}> */
    #[Computed]
    public function highlights(): array
    {
        $phone = $this->phone();

        return [
            ['label' => 'Chipset', 'value' => $phone->display('chipset')],
            ['label' => 'Layar', 'value' => $phone->display('refresh').' · '.$phone->display('nits')],
            ['label' => 'Baterai', 'value' => $phone->display('baterai')],
            ['label' => 'Kamera', 'value' => $phone->display('kamera')],
        ];
    }

    /** @return list<array{title: string, rows: list<array{label: string, value: string}>}> */
    #[Computed]
    public function specGroups(): array
    {
        $groups = [];

        foreach (self::SPEC_GROUPS as $title => $labels) {
            $rows = [];

            foreach ($labels as $key => $label) {
                $rows[] = ['label' => $label, 'value' => $this->specValue($key)];
            }

            $groups[] = ['title' => $title, 'rows' => $rows];
        }

        return $groups;
    }

    /** @return list<Phone> */
    #[Computed]
    public function rivals(): array
    {
        $others = array_filter(
            PhoneCatalog::all(),
            fn (Phone $candidate): bool => $candidate->id !== $this->phoneId,
        );

        return array_slice(array_values($others), 0, self::RIVAL_COUNT);
    }

    public function selectVariant(int $index): void
    {
        $this->variant = $index;
    }

    public function selectThumb(int $index): void
    {
        $this->thumb = $index;
    }

    public function render(): View
    {
        $phone = $this->phone();

        return view('livewire.phone-detail-page', [
            'variants' => SellerPricing::VARIANTS,
            'thumbnails' => range(0, self::THUMBNAIL_COUNT - 1),
        ])->title($phone->name.' — Spesifikasi & Harga | Bandingin.id');
    }

    /** The design mixes spec-table keys with three plain phone properties. */
    private function specValue(string $key): string
    {
        $phone = $this->phone();

        return match ($key) {
            'dimensi' => $phone->dimensi,
            'jaringan' => $phone->jaringan,
            'rilis' => $phone->rilis,
            default => $phone->display($key),
        };
    }
}
