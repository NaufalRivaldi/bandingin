<?php

declare(strict_types=1);

beforeEach(function (): void {
    seedCatalog();
});

it('renders every page with its headline copy', function (string $url, string $expected): void {
    $this->get($url)
        ->assertOk()
        ->assertSee($expected, escape: false)
        // The shared shell must be present on every page.
        ->assertSee('Bandingin')
        ->assertSee('© '.now()->year.' Bandingin.id', escape: false);
})->with([
    'beranda' => ['/', 'Jangan Beli HP'],
    'katalog' => ['/katalog', 'Semua HP, satu daftar'],
    'bandingkan' => ['/bandingkan', 'Adu baris demi baris'],
    'detail HP' => ['/hp/note14pro', 'Redmi Note 14 Pro 5G'],
    'merek' => ['/merek', 'di Indonesia'],
    'berita' => ['/berita', 'Berita &amp; Ulasan'],
    'artikel' => ['/berita/review-galaxy-a56-5g', 'Review Galaxy A56 5G'],
    'faq' => ['/faq', 'Pusat bantuan'],
    'lapor' => ['/lapor', 'Bantu kami akurat'],
    'legal' => ['/legal', 'Kebijakan Privasi'],
    'cara menilai' => ['/cara-kami-menilai', 'Cara kami menilai'],
    'sumber harga' => ['/sumber-data-harga', 'Sumber data harga'],
]);

it('returns 404 for a phone that is not in the catalogue', function (): void {
    $this->get('/hp/tidak-ada')->assertNotFound();
});

it('returns 404 for an unknown article', function (): void {
    $this->get('/berita/tidak-ada')->assertNotFound();
});

it('marks the current section in the navigation', function (): void {
    $this->get('/katalog')->assertSee('font-semibold text-brand', escape: false);
});
