<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\ReportType;
use App\Models\Report;
use Livewire\Form;

class ReportForm extends Form
{
    /** Drives the conditional store/price rules; set by the component from the active tab. */
    public ?ReportType $type = null;

    public string $subject = '';

    public string $email = '';

    public string $store = 'Shopee Mall';

    public string $claimedPrice = '';

    public string $productUrl = '';

    public string $note = '';

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];

        if ($this->type?->requiresStoreDetails() !== true) {
            return $rules;
        }

        return $rules + [
            'store' => ['required', 'string'],
            'claimedPrice' => ['required', 'string', 'max:50'],
            'productUrl' => ['required', 'url', 'max:2000'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'subject' => 'subjek',
            'email' => 'email',
            'store' => 'toko',
            'claimedPrice' => 'harga yang benar',
            'productUrl' => 'tautan halaman produk',
            'note' => 'catatan tambahan',
        ];
    }

    public function submit(): void
    {
        $validated = $this->validate();

        Report::create([
            'type' => $this->type,
            'subject' => $validated['subject'],
            'email' => $validated['email'],
            'store' => $validated['store'] ?? null,
            'claimed_price' => $validated['claimedPrice'] ?? null,
            'product_url' => $validated['productUrl'] ?? null,
            'note' => $validated['note'] ?? null,
        ]);

        $this->reset('subject', 'email', 'claimedPrice', 'productUrl', 'note');
    }
}
