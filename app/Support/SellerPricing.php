<?php

declare(strict_types=1);

namespace App\Support;

use App\Data\Phone;

/**
 * Marketplace listings derived from a phone's official price.
 *
 * The design models each storefront as a fixed offset from the official price, and the
 * distributor listing is always the cheapest — which is what "harga terendah" quotes.
 */
final class SellerPricing
{
    private const int LOWEST_OFFSET = -520000;

    /** @var list<array{label: string, delta: int}> */
    public const array VARIANTS = [
        ['label' => '8/256 GB', 'delta' => 0],
        ['label' => '12/256 GB', 'delta' => 500000],
        ['label' => '12/512 GB', 'delta' => 1200000],
    ];

    /**
     * The detailed seller table on a phone's own page.
     *
     * @return list<array{store: string, note: string, price: string, tag: string}>
     */
    public static function detailed(int $basePrice): array
    {
        return self::build($basePrice, [
            ['Shopee Mall', 'Toko resmi · gratis ongkir', 0, ''],
            ['Tokopedia Official', 'Cicilan 0% 12 bulan', -150000, ''],
            ['TikTok Shop', 'Flash sale sampai malam ini', -320000, 'Termurah'],
            ['Toko distributor', 'Garansi toko 1 tahun', self::LOWEST_OFFSET, 'Non-resmi'],
        ]);
    }

    /**
     * The condensed seller list shown side by side on the comparison page.
     *
     * @return list<array{store: string, note: string, price: string, tag: string}>
     */
    public static function compact(int $basePrice): array
    {
        return self::build($basePrice, [
            ['Shopee', 'Toko resmi · gratis ongkir', 0, ''],
            ['Tokopedia', 'Official store · cicilan 0%', -150000, ''],
            ['TikTok Shop', 'Flash sale hari ini', -320000, ''],
            ['Distributor', 'Garansi toko 1 tahun', self::LOWEST_OFFSET, ''],
        ]);
    }

    public static function lowestPrice(int $basePrice): int
    {
        return $basePrice + self::LOWEST_OFFSET;
    }

    public static function lowestPriceLabel(Phone $phone): string
    {
        return Phone::formatRupiah(self::lowestPrice($phone->price));
    }

    /**
     * @param  list<array{0: string, 1: string, 2: int, 3: string}>  $definitions
     * @return list<array{store: string, note: string, price: string, tag: string}>
     */
    private static function build(int $basePrice, array $definitions): array
    {
        return array_map(
            static fn (array $definition): array => [
                'store' => $definition[0],
                'note' => $definition[1],
                'price' => Phone::formatRupiah($basePrice + $definition[2]),
                'tag' => $definition[3],
            ],
            $definitions,
        );
    }
}
