<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\DuelGroup;
use App\Data\DuelResult;
use App\Data\DuelRow;
use App\Data\Phone;

/**
 * Scores two phones against each other, row by row.
 *
 * A row is won by whichever side has the higher comparable value; equal values are a
 * tie and score for neither. Price is the exception — cheaper wins — so it is negated
 * before comparison.
 */
final class CompareDuel
{
    public const string PRICE_KEY = 'harga';

    /**
     * The full grouped table used by the comparison page.
     *
     * @var array<string, array<string, string>>
     */
    public const array FULL_GROUPS = [
        'Performa' => [
            'chipset' => 'Chipset',
            'antutu' => 'Skor AnTuTu',
            'ram' => 'RAM',
            'storage' => 'Penyimpanan',
        ],
        'Layar' => [
            'layar' => 'Panel & Kecerahan',
            'refresh' => 'Refresh Rate',
            'resolusi' => 'Resolusi',
        ],
        'Kamera' => [
            'kamera' => 'Kamera Utama',
            'ultrawide' => 'Ultrawide',
            'selfie' => 'Kamera Depan',
            'video' => 'Rekam Video',
        ],
        'Baterai & Daya' => [
            'baterai' => 'Kapasitas',
            'charge' => 'Pengisian Daya',
        ],
        'Lainnya' => [
            'ip' => 'IP Rating',
            'osUpdate' => 'Dukungan Software',
            'berat' => 'Berat',
            self::PRICE_KEY => 'Harga Resmi',
        ],
    ];

    /**
     * The condensed table used by the homepage duel card.
     *
     * @var array<string, string>
     */
    public const array SUMMARY_ROWS = [
        'chipset' => 'Chipset',
        'ram' => 'RAM',
        'layar' => 'Layar',
        'refresh' => 'Refresh Rate',
        'baterai' => 'Baterai',
        'kamera' => 'Kamera Utama',
        'ip' => 'IP Rating',
        self::PRICE_KEY => 'Harga',
    ];

    /**
     * Why a reader might pick the phone that won a given row.
     *
     * @var array<string, string>
     */
    private const array REASONS = [
        'chipset' => 'performa harian dan gaming jadi prioritas',
        'layar' => 'sering pakai HP di luar ruangan',
        'baterai' => 'butuh baterai tahan seharian penuh',
        'kamera' => 'kamera utama yang paling penting',
        'charge' => 'nggak suka nunggu lama saat ngecas',
        'osUpdate' => 'ingin dipakai lama dengan update resmi',
        'storage' => 'butuh penyimpanan cepat dan lega',
        self::PRICE_KEY => 'budget adalah pertimbangan utama',
    ];

    /**
     * @param  array<string, array<string, string>>  $groups  Group title => [spec key => row label].
     */
    public function handle(
        Phone $left,
        Phone $right,
        array $groups = self::FULL_GROUPS,
        bool $onlyDifferences = false,
    ): DuelResult {
        $leftWins = 0;
        $rightWins = 0;
        $leftReasons = [];
        $rightReasons = [];
        $builtGroups = [];

        foreach ($groups as $title => $labels) {
            $rows = [];

            foreach ($labels as $key => $label) {
                $row = $this->buildRow($left, $right, (string) $key, $label);

                if ($row->leftWins) {
                    $leftWins++;
                    $leftReasons[] = self::REASONS[$key] ?? null;
                }

                if ($row->rightWins) {
                    $rightWins++;
                    $rightReasons[] = self::REASONS[$key] ?? null;
                }

                if ($onlyDifferences && $row->isTie()) {
                    continue;
                }

                $rows[] = $row;
            }

            if ($rows !== []) {
                $builtGroups[] = new DuelGroup((string) $title, $rows);
            }
        }

        return new DuelResult(
            left: $left,
            right: $right,
            groups: $builtGroups,
            leftWins: $leftWins,
            rightWins: $rightWins,
            leftReasons: $this->distinct($leftReasons),
            rightReasons: $this->distinct($rightReasons),
        );
    }

    private function buildRow(Phone $left, Phone $right, string $key, string $label): DuelRow
    {
        $isPrice = $key === self::PRICE_KEY;

        $leftValue = $isPrice ? $left->formattedPrice() : $left->display($key);
        $rightValue = $isPrice ? $right->formattedPrice() : $right->display($key);

        $leftScore = $isPrice ? -$left->price : $left->comparable($key);
        $rightScore = $isPrice ? -$right->price : $right->comparable($key);

        return new DuelRow(
            key: $key,
            label: $label,
            leftValue: $leftValue,
            rightValue: $rightValue,
            leftWins: $leftScore > $rightScore,
            rightWins: $rightScore > $leftScore,
        );
    }

    /**
     * @param  list<string|null>  $reasons
     * @return list<string>
     */
    private function distinct(array $reasons): array
    {
        return array_slice(array_values(array_unique(array_filter($reasons))), 0, 4);
    }
}
