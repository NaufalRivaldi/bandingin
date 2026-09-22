<?php

declare(strict_types=1);

namespace App\Actions\Phones;

use App\Models\Phone;

/**
 * Replaces a phone's spec, variant and listing rows from imported CSV values.
 *
 * Each relation is independent: passing null leaves it untouched, so a CSV that
 * omits a column never silently wipes data. Passing an array is a full replace —
 * rows not present are deleted. Upserts go through the models so PhoneListingObserver
 * keeps appending price_snapshots; prunes are mass deletes, which the
 * price_snapshots foreign key cascades for us.
 */
final class SyncPhoneRelations
{
    /**
     * @param  array<string, array{display: string, comparable: int}>|null  $specs  keyed by spec definition id
     * @param  list<array{label: string, price: int, is_default: bool, sort: int}>|null  $variants
     * @param  list<array{store_id: int, price: int, product_url: ?string, warranty_tag: ?string, note: ?string}>|null  $listings
     */
    public function handle(Phone $phone, ?array $specs, ?array $variants, ?array $listings): void
    {
        if ($specs !== null) {
            $this->syncSpecs($phone, $specs);
        }

        if ($variants !== null) {
            $this->syncVariants($phone, $variants);
        }

        if ($listings !== null) {
            $this->syncListings($phone, $listings);
        }
    }

    /** @param  array<string, array{display: string, comparable: int}>  $specs */
    private function syncSpecs(Phone $phone, array $specs): void
    {
        foreach ($specs as $definitionId => $values) {
            $phone->specs()->updateOrCreate(
                ['spec_definition_id' => (int) $definitionId],
                [
                    'display_value' => $values['display'],
                    'comparable_value' => $values['comparable'],
                ],
            );
        }

        $phone->specs()
            ->whereNotIn('spec_definition_id', array_map(intval(...), array_keys($specs)))
            ->delete();
    }

    /** @param  list<array{label: string, price: int, is_default: bool, sort: int}>  $variants */
    private function syncVariants(Phone $phone, array $variants): void
    {
        foreach ($variants as $variant) {
            $phone->variants()->updateOrCreate(
                ['label' => $variant['label']],
                [
                    'price' => $variant['price'],
                    'is_default' => $variant['is_default'],
                    'sort' => $variant['sort'],
                ],
            );
        }

        $phone->variants()
            ->whereNotIn('label', array_column($variants, 'label'))
            ->delete();
    }

    /** @param  list<array{store_id: int, price: int, product_url: ?string, warranty_tag: ?string, note: ?string}>  $listings */
    private function syncListings(Phone $phone, array $listings): void
    {
        foreach ($listings as $listing) {
            $phone->listings()->updateOrCreate(
                ['store_id' => $listing['store_id']],
                [
                    'price' => $listing['price'],
                    'product_url' => $listing['product_url'],
                    'warranty_tag' => $listing['warranty_tag'],
                    'note' => $listing['note'],
                ],
            );
        }

        $phone->listings()
            ->whereNotIn('store_id', array_column($listings, 'store_id'))
            ->delete();
    }
}
