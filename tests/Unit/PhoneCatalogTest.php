<?php

declare(strict_types=1);

use App\Actions\CompareDuel;
use App\Data\Phone;
use App\Support\PhoneCatalog;

it('exposes the full demo catalogue', function (): void {
    expect(PhoneCatalog::all())->toHaveCount(12)
        ->and(PhoneCatalog::brands())->toEqualCanonicalizing(
            ['Samsung', 'Xiaomi', 'POCO', 'realme', 'Apple', 'Infinix']
        );
});

/**
 * Every phone must answer every row the comparison table asks for, otherwise a duel
 * would silently fall back to a placeholder for the missing side. An em dash is only
 * acceptable when it is authored, e.g. a phone with no ultrawide camera at all.
 */
it('gives every phone every spec the duel table compares', function (Phone $phone): void {
    $keys = array_diff(
        array_merge(...array_map('array_keys', array_values(CompareDuel::FULL_GROUPS))),
        [CompareDuel::PRICE_KEY],
    );

    foreach ($keys as $key) {
        expect($phone->specs)->toHaveKey($key)
            ->and($phone->display($key))->not->toBe('');
    }
})->with(fn (): array => array_combine(
    array_map(static fn (Phone $phone): string => $phone->name, PhoneCatalog::all()),
    array_map(static fn (Phone $phone): array => [$phone], PhoneCatalog::all()),
));

it('scores every phone across the five published categories', function (): void {
    foreach (PhoneCatalog::all() as $phone) {
        expect($phone->scores)->toHaveCount(5)
            ->and(array_keys($phone->scores))->toBe(['Performa', 'Layar', 'Kamera', 'Baterai', 'Nilai per Rupiah'])
            ->and($phone->averageScore())->toBeGreaterThan(0)->toBeLessThanOrEqual(100);
    }
});

it('formats prices the Indonesian way', function (): void {
    expect(Phone::formatRupiah(3999000))->toBe('Rp3.999.000')
        ->and(PhoneCatalog::findOrFirst('note14pro')->formattedPrice())->toBe('Rp3.999.000');
});

it('falls back to the first phone for an unknown id', function (): void {
    expect(PhoneCatalog::find('tidak-ada'))->toBeNull()
        ->and(PhoneCatalog::findOrFirst('tidak-ada')->id)->toBe('a56');
});

it('searches on both name and brand', function (): void {
    expect(array_map(fn (Phone $p): string => $p->id, PhoneCatalog::search('poco')))
        ->toEqualCanonicalizing(['x7pro', 'm7pro'])
        ->and(PhoneCatalog::search('galaxy a56'))->toHaveCount(1)
        ->and(PhoneCatalog::search('tidak ada hp ini'))->toBeEmpty()
        ->and(PhoneCatalog::search(''))->toHaveCount(6);
});

it('keeps weight comparable as lower-is-better', function (): void {
    // Weight is stored negated so the shared "higher wins" rule still holds.
    expect(PhoneCatalog::findOrFirst('s25')->comparable('berat'))
        ->toBeGreaterThan(PhoneCatalog::findOrFirst('a56')->comparable('berat'));
});
