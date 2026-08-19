<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Enums\ReportType;
use App\Livewire\Forms\ReportForm;
use Illuminate\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Bantu kami akurat — Bandingin.id')]
class LaporPage extends Component
{
    #[Url(as: 'jenis')]
    public ReportType $type = ReportType::Harga;

    public ReportForm $form;

    public bool $sent = false;

    /** @var list<string> */
    public const array STORES = [
        'Shopee Mall',
        'Tokopedia Official',
        'TikTok Shop',
        'Toko distributor',
    ];

    /** @var list<array{label: string, value: string}> */
    private const array SLAS = [
        ['label' => 'Harga salah', 'value' => 'Diperbaiki < 24 jam'],
        ['label' => 'HP baru', 'value' => 'Tayang < 7 hari'],
        ['label' => 'Koreksi spesifikasi', 'value' => 'Ditinjau 2 hari kerja'],
    ];

    public function mount(): void
    {
        $this->form->type = $this->type;
    }

    public function selectType(string $type): void
    {
        $this->type = ReportType::from($type);
        $this->form->type = $this->type;

        $this->sent = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->form->submit();

        $this->sent = true;
    }

    public function backToForm(): void
    {
        $this->sent = false;
    }

    public function render(): View
    {
        return view('livewire.lapor-page', [
            'types' => ReportType::cases(),
            'stores' => self::STORES,
            'slas' => self::SLAS,
        ]);
    }
}
