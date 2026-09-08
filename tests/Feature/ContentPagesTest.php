<?php

declare(strict_types=1);

beforeEach(function (): void {
    seedCatalog();
});

use App\Livewire\BeritaPage;
use App\Livewire\FaqPage;
use App\Livewire\LegalPage;
use App\Livewire\MerekPage;
use Livewire\Livewire;

it('filters articles by category and hides the featured block', function (): void {
    Livewire::test(BeritaPage::class)
        // The featured article is promoted out of the grid on the default view.
        ->assertSee('Sorotan')
        ->assertSee('11 artikel')
        ->set('category', 'Ulasan')
        ->assertDontSee('Sorotan')
        ->assertSee('3 artikel')
        ->assertSee('Review Galaxy A56 5G');
});

it('opens an article on its own page', function (): void {
    $this->get('/berita/adu-kamera-malam-redmi-vs-galaxy')
        ->assertOk()
        ->assertSee('Adu kamera malam')
        ->assertSee('Rizky Adhitama')
        // This article embeds a duel table.
        ->assertSee('Kartu Duel')
        ->assertSee('Artikel Terkait');
});

it('switches brands on the merek page', function (): void {
    Livewire::test(MerekPage::class)
        ->assertSee('Xiaomi di Indonesia')
        ->set('brand', 'Apple')
        ->assertSee('Apple di Indonesia')
        ->assertSee('iPhone 16')
        ->assertSee('Yang perlu dipertimbangkan');
});

it('filters the faq by topic', function (): void {
    Livewire::test(FaqPage::class)
        ->assertSee('Apa itu Kartu Duel?')
        ->call('selectTopic', 'Skor')
        ->assertSee('Bagaimana skor dihitung?')
        ->assertDontSee('Apa itu Kartu Duel?');
});

it('opens one faq answer at a time', function (): void {
    Livewire::test(FaqPage::class)
        // The first answer is open on arrival.
        ->assertSet('open', 0)
        ->call('toggle', 2)
        ->assertSet('open', 2)
        // Clicking the open item closes it.
        ->call('toggle', 2)
        ->assertSet('open', -1);
});

it('switches between the two legal documents', function (): void {
    Livewire::test(LegalPage::class)
        ->assertSee('Kebijakan Privasi')
        ->assertSee('Data yang kami kumpulkan')
        ->set('document', 'syarat')
        ->assertSee('Syarat &amp; Ketentuan', escape: false)
        ->assertSee('Sifat informasi');
});

it('serves the legal document named in the query string', function (): void {
    $this->get('/legal?dok=syarat')
        ->assertOk()
        ->assertSee('Sifat informasi');
});
