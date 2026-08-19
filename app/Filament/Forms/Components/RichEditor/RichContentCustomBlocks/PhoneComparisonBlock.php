<?php

declare(strict_types=1);

namespace App\Filament\Forms\Components\RichEditor\RichContentCustomBlocks;

use App\Models\Phone;
use App\Models\PhoneSpec;
use App\Models\SpecDefinition;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor\RichContentCustomBlock;
use Filament\Forms\Components\Select;

/**
 * An article-embedded duel table.
 *
 * Only the two phone ids and the chosen spec keys are stored, so the rendered
 * table always reflects current catalogue data rather than a frozen copy.
 */
class PhoneComparisonBlock extends RichContentCustomBlock
{
    public static function getId(): string
    {
        return 'phone_comparison';
    }

    public static function getLabel(): string
    {
        return 'Tabel perbandingan';
    }

    public static function configureEditorAction(Action $action): Action
    {
        return $action
            ->modalHeading('Tabel perbandingan')
            ->modalDescription('Tabel dirender dari data katalog terkini, jadi tidak pernah basi.')
            ->schema([
                Select::make('leftPhoneId')
                    ->label('HP kiri')
                    ->options(fn (): array => self::phoneOptions())
                    ->searchable()
                    ->required(),
                Select::make('rightPhoneId')
                    ->label('HP kanan')
                    ->options(fn (): array => self::phoneOptions())
                    ->searchable()
                    ->required(),
                Select::make('specKeys')
                    ->label('Baris spesifikasi')
                    ->options(fn (): array => SpecDefinition::orderBy('sort')->pluck('label', 'key')->all())
                    ->multiple()
                    ->default(['chipset', 'ram', 'layar', 'baterai', 'kamera'])
                    ->required(),
            ]);
    }

    /** @param  array<string, mixed>  $config */
    public static function toPreviewHtml(array $config): string
    {
        $phones = self::resolvePhones($config);

        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.phone-comparison.preview', [
            'left' => $phones[0] ?? null,
            'right' => $phones[1] ?? null,
        ])->render();
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $data
     */
    public static function toHtml(array $config, array $data): string
    {
        $phones = self::resolvePhones($config);

        if ($phones === null) {
            return '';
        }

        [$left, $right] = $phones;

        $rows = self::buildRows($left, $right, self::specKeys($config));

        if ($rows === []) {
            return '';
        }

        return view('filament.forms.components.rich-editor.rich-content-custom-blocks.phone-comparison.index', [
            'left' => $left,
            'right' => $right,
            'rows' => $rows,
        ])->render();
    }

    /**
     * @param  list<string>  $keys
     * @return list<array{label: string, left: string, right: string}>
     */
    private static function buildRows(Phone $left, Phone $right, array $keys): array
    {
        $rows = [];

        foreach ($keys as $key) {
            $leftSpec = self::specFor($left, $key);
            $rightSpec = self::specFor($right, $key);

            if (! $leftSpec instanceof PhoneSpec && ! $rightSpec instanceof PhoneSpec) {
                continue;
            }

            $rows[] = [
                'label' => $leftSpec?->definition->label ?? $rightSpec?->definition->label ?? $key,
                'left' => $leftSpec?->display_value ?? '—',
                'right' => $rightSpec?->display_value ?? '—',
            ];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return list<string>
     */
    private static function specKeys(array $config): array
    {
        $keys = $config['specKeys'] ?? [];

        return is_array($keys) ? array_values($keys) : [];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array{0: Phone, 1: Phone}|null
     */
    private static function resolvePhones(array $config): ?array
    {
        $left = Phone::with('specs.definition')->find($config['leftPhoneId'] ?? null);
        $right = Phone::with('specs.definition')->find($config['rightPhoneId'] ?? null);

        if (! $left instanceof Phone || ! $right instanceof Phone) {
            return null;
        }

        return [$left, $right];
    }

    private static function specFor(Phone $phone, string $key): ?PhoneSpec
    {
        return $phone->specs->first(
            static fn (PhoneSpec $spec): bool => $spec->definition?->key === $key,
        );
    }

    /** @return array<int, string> */
    private static function phoneOptions(): array
    {
        return Phone::orderBy('name')->pluck('name', 'id')->all();
    }
}
