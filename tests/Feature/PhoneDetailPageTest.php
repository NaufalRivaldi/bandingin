<?php

declare(strict_types=1);

use App\Livewire\PhoneDetailPage;
use App\Models\Phone;
use App\Models\PhoneListing;
use App\Money\Rupiah;
use Illuminate\Support\Facades\Blade;
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

it('prices every variant in the seller section', function (): void {
    $phone = Phone::where('slug', 'a56')->with('variants')->sole();

    $page = Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->assertSeeInOrder(['Daftar penjual', 'Harga resmi per varian']);

    foreach ($phone->variants as $option) {
        $page->assertSee($option->formattedPrice());
    }
});

it('marks the chosen variant as the pressed option', function (): void {
    $phone = Phone::where('slug', 'a56')->with('variants')->sole();

    $page = Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->call('selectVariant', 2);

    expect($page->instance()->selectedVariant()->is($phone->variants->get(2)))->toBeTrue();
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

it('opens seller affiliate links in a new tab', function (): void {
    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->assertSeeHtml('target="_blank" rel="noopener noreferrer sponsored"');
});

it('hides the seller price button when the listing has no product url', function (): void {
    $listing = PhoneListing::factory()->create(['product_url' => null]);

    expect(Blade::render('<x-seller-row :seller="$seller" />', ['seller' => $listing]))
        ->not->toContain('Cek Harga');
});

it('keeps internal affiliate-style links in the same tab', function (): void {
    expect(Blade::render('<x-affiliate-link href="/katalog" />'))
        ->not->toContain('target="_blank"')
        ->not->toContain('sponsored');
});
