<?php

declare(strict_types=1);

use App\Actions\CompareDuel;
use App\Data\Phone;
use App\Data\SpecValue;
use App\Support\PhoneCatalog;

function phoneWith(string $id, int $price, int $ram, int $battery): Phone
{
    return new Phone(
        id: $id,
        brand: 'Uji',
        name: 'Uji '.$id,
        price: $price,
        specs: [
            'ram' => new SpecValue($ram.' GB', $ram),
            'baterai' => new SpecValue($battery.' mAh', $battery),
        ],
        scores: ['Performa' => 50],
        pros: [],
        cons: [],
        nfc: true,
        ip68: false,
        tag: '',
        dimensi: '',
        jaringan: '',
        rilis: '',
    );
}

it('awards a row to the side with the higher value', function (): void {
    $result = (new CompareDuel)->handle(
        phoneWith('kiri', 3_000_000, ram: 12, battery: 5000),
        phoneWith('kanan', 3_000_000, ram: 8, battery: 6000),
        ['Uji' => ['ram' => 'RAM', 'baterai' => 'Baterai']],
    );

    $rows = $result->rows();

    expect($rows[0]->leftWins)->toBeTrue()
        ->and($rows[0]->rightWins)->toBeFalse()
        ->and($rows[1]->rightWins)->toBeTrue()
        ->and($result->leftWins)->toBe(1)
        ->and($result->rightWins)->toBe(1);
});

it('treats identical values as a tie that scores for neither side', function (): void {
    $result = (new CompareDuel)->handle(
        phoneWith('kiri', 3_000_000, ram: 8, battery: 5000),
        phoneWith('kanan', 3_000_000, ram: 8, battery: 5000),
        ['Uji' => ['ram' => 'RAM']],
    );

    expect($result->rows()[0]->isTie())->toBeTrue()
        ->and($result->leftWins)->toBe(0)
        ->and($result->rightWins)->toBe(0)
        ->and($result->winner())->toBeNull()
        ->and($result->summary())->toBe('Imbang — 0 kategori masing-masing');
});

it('awards the price row to the cheaper phone', function (): void {
    $result = (new CompareDuel)->handle(
        phoneWith('mahal', 9_000_000, ram: 8, battery: 5000),
        phoneWith('murah', 3_000_000, ram: 8, battery: 5000),
        ['Uji' => [CompareDuel::PRICE_KEY => 'Harga']],
    );

    expect($result->rows()[0]->rightWins)->toBeTrue()
        ->and($result->winner()?->id)->toBe('murah');
});

it('hides tied rows when only differences are requested', function (): void {
    $groups = ['Uji' => ['ram' => 'RAM', 'baterai' => 'Baterai']];

    $left = phoneWith('kiri', 3_000_000, ram: 8, battery: 5000);
    $right = phoneWith('kanan', 3_000_000, ram: 8, battery: 6000);

    $all = (new CompareDuel)->handle($left, $right, $groups);
    $differences = (new CompareDuel)->handle($left, $right, $groups, onlyDifferences: true);

    expect($all->rows())->toHaveCount(2)
        ->and($differences->rows())->toHaveCount(1)
        ->and($differences->rows()[0]->label)->toBe('Baterai')
        // Hidden rows must not change the tally.
        ->and($differences->rightWins)->toBe($all->rightWins);
});

it('drops a group entirely when every row in it is tied', function (): void {
    $result = (new CompareDuel)->handle(
        phoneWith('kiri', 3_000_000, ram: 8, battery: 5000),
        phoneWith('kanan', 2_000_000, ram: 8, battery: 5000),
        ['Sama' => ['ram' => 'RAM'], 'Beda' => [CompareDuel::PRICE_KEY => 'Harga']],
        onlyDifferences: true,
    );

    expect($result->groups)->toHaveCount(1)
        ->and($result->groups[0]->title)->toBe('Beda');
});

it('summarises the winner across the real catalogue', function (): void {
    $result = (new CompareDuel)->handle(
        PhoneCatalog::findOrFirst('a56'),
        PhoneCatalog::findOrFirst('note14pro'),
        ['' => CompareDuel::SUMMARY_ROWS],
    );

    expect($result->decidedCount())->toBeGreaterThan(0)
        ->and($result->summary())->toContain('menang di')
        ->and($result->leftShare() + $result->rightShare())->toEqualWithDelta(100.0, 0.001);
});

it('collects distinct buying reasons for each side', function (): void {
    $result = (new CompareDuel)->handle(
        PhoneCatalog::findOrFirst('ip16'),
        PhoneCatalog::findOrFirst('note14pro'),
    );

    expect($result->leftReasons)->not->toBeEmpty()
        ->and($result->leftReasons)->toHaveCount(count(array_unique($result->leftReasons)))
        ->and(count($result->rightReasons))->toBeLessThanOrEqual(4);
});
