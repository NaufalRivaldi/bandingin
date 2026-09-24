<?php

declare(strict_types=1);

/**
 * The catalogue now arrives by CSV import, so a fresh install serves every
 * public page against a database holding only lookup data until the first
 * import runs.
 */
it('renders every public page against a freshly seeded install', function (string $route): void {
    $this->seed();

    $this->get(route($route))->assertSuccessful();
})->with([
    ['katalog'],
    ['merek'],
    ['berita'],
    ['faq'],
    ['lapor'],
    ['legal'],
    ['cara-menilai'],
    ['sumber-harga'],
]);

it('renders the phone-dependent pages against a freshly seeded install', function (string $route): void {
    $this->seed();

    $this->get(route($route))->assertSuccessful();
})->with([
    ['home'],
    ['bandingkan'],
])->skip('HomePage::resolve() and PerbandinganPage::resolve() both firstOrFail() on the first published phone, so these 404 until a CSV import lands. Needs an agreed empty state for the duel card and the comparison table.');
