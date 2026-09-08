<?php

declare(strict_types=1);

use App\Livewire\PhoneDetailPage;
use App\Models\Phone;
use App\Money\Rupiah;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

it('renders a seeded phone by slug', function (): void {
    $this->get(route('hp.show', 'a56'))
        ->assertSuccessful()
        ->assertSee('Galaxy A56 5G');
});

it('returns 404 for an unknown slug', function (): void {
    $this->get(route('hp.show', 'tidak-ada'))->assertNotFound();
});

it('returns 404 for an unpublished phone', function (): void {
    Phone::where('slug', 'a56')->update(['published_at' => null]);

    $this->get(route('hp.show', 'a56'))->assertNotFound();
});

it('shows real listing prices from the database', function (): void {
    $phone = Phone::where('slug', 'a56')->with('listings')->sole();

    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->assertSee(Rupiah::format($phone->listings->min('price')));
});

it('does not shift seller prices when a variant is selected', function (): void {
    $phone = Phone::where('slug', 'a56')->with('listings')->sole();
    $cheapest = Rupiah::format($phone->listings->min('price'));

    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->call('selectVariant', 2)
        ->assertSee($cheapest);
});

it('moves the official price headline with the selected variant', function (): void {
    $phone = Phone::where('slug', 'a56')->with('variants')->sole();
    $third = $phone->variants->get(2);

    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->call('selectVariant', 2)
        ->assertSee($third->formattedPrice());
});

it('groups specs the way the design groups them', function (): void {
    $groups = Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->instance()
        ->specGroups();

    expect(collect($groups)->pluck('title')->all())
        ->toContain('Performa', 'Layar', 'Kamera', 'Baterai & Daya', 'Bodi & Lainnya');
});

it('closes the spec table with the phone body rows', function (): void {
    $groups = Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->instance()
        ->specGroups();

    $body = collect($groups)->firstWhere('title', 'Bodi & Lainnya');

    expect(collect($body['rows'])->pluck('label')->all())
        ->toContain('Dimensi', 'Jaringan', 'Rilis');
});
