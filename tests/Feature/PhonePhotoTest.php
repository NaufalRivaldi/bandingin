<?php

declare(strict_types=1);

use App\Livewire\PhoneDetailPage;
use App\Models\Phone;
use Livewire\Livewire;

beforeEach(function (): void {
    seedCatalog();
});

function givePhotos(array $urls, string $slug = 'a56'): void
{
    Phone::where('slug', $slug)->update(['image_urls' => json_encode($urls)]);
}

it('falls back to the gradient mock when a phone has no photos', function (): void {
    $this->get(route('hp.show', 'a56'))
        ->assertSuccessful()
        ->assertSee('device-shell', escape: false)
        ->assertSee('Foto produk');
});

it('renders the first photo in the big slot instead of the mock', function (): void {
    givePhotos(['https://contoh.com/depan.jpg', 'https://contoh.com/belakang.jpg']);

    // device-shell is the gradient mock's marker class.
    $this->get(route('hp.show', 'a56'))
        ->assertSuccessful()
        ->assertSee('https://contoh.com/depan.jpg', escape: false)
        ->assertSee('alt="Galaxy A56 5G"', escape: false)
        ->assertDontSee('device-shell', escape: false);
});

it('renders one thumbnail per photo', function (): void {
    givePhotos(['https://contoh.com/a.jpg', 'https://contoh.com/b.jpg', 'https://contoh.com/c.jpg']);

    $this->get(route('hp.show', 'a56'))
        ->assertSuccessful()
        ->assertSee('Foto produk 3')
        ->assertDontSee('Foto produk 4');
});

it('hides the thumbnail strip for a phone with a single photo', function (): void {
    givePhotos(['https://contoh.com/a.jpg']);

    $this->get(route('hp.show', 'a56'))
        ->assertSuccessful()
        ->assertDontSee('Foto produk 1');
});

it('swaps the big slot when a thumbnail is selected', function (): void {
    givePhotos(['https://contoh.com/a.jpg', 'https://contoh.com/b.jpg']);

    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->assertSee('https://contoh.com/a.jpg', escape: false)
        ->call('selectThumb', 1)
        ->assertSet('thumb', 1)
        ->assertSee('https://contoh.com/b.jpg', escape: false);
});

it('clamps an out-of-range thumbnail index', function (): void {
    givePhotos(['https://contoh.com/a.jpg', 'https://contoh.com/b.jpg']);

    Livewire::test(PhoneDetailPage::class, ['phone' => 'a56'])
        ->call('selectThumb', 99)
        ->assertSet('thumb', 1)
        ->call('selectThumb', -5)
        ->assertSet('thumb', 0);
});

it('renders the primary photo on catalogue cards', function (): void {
    givePhotos(['https://contoh.com/depan.jpg', 'https://contoh.com/belakang.jpg']);

    $this->get(route('katalog'))
        ->assertSuccessful()
        ->assertSee('https://contoh.com/depan.jpg', escape: false)
        ->assertDontSee('https://contoh.com/belakang.jpg', escape: false);
});

it('keeps the mock on cards for phones without photos', function (): void {
    $this->get(route('katalog'))
        ->assertSuccessful()
        ->assertSee('device-shell', escape: false);
});

it('ignores blank entries in a gallery', function (): void {
    givePhotos(['https://contoh.com/a.jpg', '', null]);

    expect(Phone::where('slug', 'a56')->sole()->images())->toBe(['https://contoh.com/a.jpg']);
});
