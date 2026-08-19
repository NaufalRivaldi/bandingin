<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PhoneListing;

/**
 * Keeps price_snapshots as the append-only history of every listing price,
 * so a future price tracker inherits a populated timeline.
 */
class PhoneListingObserver
{
    public function created(PhoneListing $listing): void
    {
        $this->recordPrice($listing);
    }

    public function updated(PhoneListing $listing): void
    {
        if (! $listing->wasChanged('price')) {
            return;
        }

        $this->recordPrice($listing);
    }

    private function recordPrice(PhoneListing $listing): void
    {
        $listing->snapshots()->create([
            'price' => $listing->price,
            'recorded_at' => now(),
        ]);
    }
}
