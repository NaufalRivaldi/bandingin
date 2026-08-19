<?php

declare(strict_types=1);

use App\Enums\ReportType;
use App\Livewire\LaporPage;
use App\Models\Report;
use Livewire\Livewire;

it('files a price report with its store details', function (): void {
    Livewire::test(LaporPage::class)
        ->call('selectType', 'harga')
        ->set('form.subject', 'Redmi Note 14 Pro 5G')
        ->set('form.email', 'pelapor@bandingin.test')
        ->set('form.store', 'Tokopedia Official')
        ->set('form.claimedPrice', 'Rp3.799.000')
        ->set('form.productUrl', 'https://tokopedia.test/redmi-note-14-pro')
        ->set('form.note', 'Harga sudah turun sejak kemarin sore.')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('sent', true)
        ->assertSee('Terima kasih, laporannya masuk');

    $report = Report::sole();

    expect($report->type)->toBe(ReportType::Harga)
        ->and($report->subject)->toBe('Redmi Note 14 Pro 5G')
        ->and($report->store)->toBe('Tokopedia Official')
        ->and($report->claimed_price)->toBe('Rp3.799.000')
        ->and($report->product_url)->toBe('https://tokopedia.test/redmi-note-14-pro');
});

it('requires the store fields only on the price tab', function (): void {
    // The price tab demands a store, a price and a link...
    Livewire::test(LaporPage::class)
        ->call('selectType', 'harga')
        ->set('form.subject', 'Galaxy A56 5G')
        ->set('form.email', 'pelapor@bandingin.test')
        ->call('save')
        ->assertHasErrors(['form.claimedPrice' => 'required', 'form.productUrl' => 'required'])
        ->assertSet('sent', false);

    // ...while suggesting a new phone does not.
    Livewire::test(LaporPage::class)
        ->call('selectType', 'hp')
        ->set('form.subject', 'POCO F7 Pro 12/256 GB')
        ->set('form.email', 'pelapor@bandingin.test')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('sent', true);

    expect(Report::sole()->type)->toBe(ReportType::Hp)
        ->and(Report::sole()->claimed_price)->toBeNull();
});

it('rejects an invalid email and an invalid product link', function (): void {
    Livewire::test(LaporPage::class)
        ->call('selectType', 'harga')
        ->set('form.subject', 'Galaxy A56 5G')
        ->set('form.email', 'bukan-email')
        ->set('form.claimedPrice', 'Rp5.000.000')
        ->set('form.productUrl', 'bukan-url')
        ->call('save')
        ->assertHasErrors(['form.email' => 'email', 'form.productUrl' => 'url']);

    expect(Report::count())->toBe(0);
});

it('requires a subject', function (): void {
    Livewire::test(LaporPage::class)
        ->call('selectType', 'spesifikasi')
        ->set('form.email', 'pelapor@bandingin.test')
        ->call('save')
        ->assertHasErrors(['form.subject' => 'required']);
});

it('shows the copy belonging to the selected tab', function (): void {
    Livewire::test(LaporPage::class)
        ->call('selectType', 'spesifikasi')
        ->assertSee(ReportType::Spesifikasi->formTitle())
        ->assertSee(ReportType::Spesifikasi->subjectLabel())
        // Store details belong to the price tab only.
        ->assertDontSee('Tautan halaman produk');
});

it('clears errors and the sent state when switching tabs', function (): void {
    Livewire::test(LaporPage::class)
        ->call('selectType', 'harga')
        ->call('save')
        ->assertHasErrors()
        ->call('selectType', 'hp')
        ->assertHasNoErrors()
        ->assertSet('sent', false);
});

it('offers a fresh form after a successful submission', function (): void {
    Livewire::test(LaporPage::class)
        ->call('selectType', 'hp')
        ->set('form.subject', 'POCO F7 Pro')
        ->set('form.email', 'pelapor@bandingin.test')
        ->call('save')
        ->assertSet('sent', true)
        ->call('backToForm')
        ->assertSet('sent', false)
        // The previous answers must not linger in the new form.
        ->assertSet('form.subject', '')
        ->assertSet('form.email', '');
});

it('opens on the tab named in the query string', function (): void {
    $this->get('/lapor?jenis=spesifikasi')
        ->assertOk()
        ->assertSee(ReportType::Spesifikasi->formTitle());
});
