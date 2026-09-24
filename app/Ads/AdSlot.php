<?php

declare(strict_types=1);

namespace App\Ads;

use App\Models\Ad;
use App\Settings\AdSettings;
use Illuminate\Support\Facades\Cache;

/**
 * Decides what, if anything, the public layout should offer for ads.
 *
 * Counting page views and opening the tab happen in the browser
 * (resources/js/ads.js); this class only answers "are ads on, and which link
 * should this page render?".
 */
final class AdSlot
{
    /** Active ads are read on every public page view, so the list is cached. */
    private const string CACHE_KEY = 'ads.active';

    /**
     * Deliberately short and invalidation-free: an edited ad appearing within
     * five minutes is fine, and the `enabled` toggle is read uncached, so
     * switching ads off still takes effect immediately.
     */
    private const int CACHE_MINUTES = 5;

    public function __construct(private readonly AdSettings $settings) {}

    /**
     * @return array{frequency: int, cooldownMinutes: int, url: string}|null
     */
    public function payload(): ?array
    {
        if (! $this->settings->enabled) {
            return null;
        }

        $url = $this->pickWeighted($this->activeAds());

        if ($url === null) {
            return null;
        }

        return [
            'frequency' => $this->settings->frequency,
            'cooldownMinutes' => $this->settings->cooldown_minutes,
            'url' => $url,
        ];
    }

    /**
     * Only primitives are cached here, never Eloquent models.
     *
     * Serializing models into a shared cache store (this app uses `database`)
     * round-trips them back as __PHP_Incomplete_Class, which silently breaks
     * the whole slot. Plain arrays are also smaller and survive model changes.
     *
     * @return list<array{url: string, weight: int}>
     */
    private function activeAds(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            now()->addMinutes(self::CACHE_MINUTES),
            fn (): array => Ad::query()->active()
                ->get(['url', 'weight'])
                ->map(fn (Ad $ad): array => [
                    'url' => $ad->url,
                    'weight' => $ad->weight,
                ])
                ->all(),
        );
    }

    /**
     * Picks one ad URL with a probability proportional to its weight.
     *
     * @param  list<array{url: string, weight: int}>  $ads
     */
    private function pickWeighted(array $ads): ?string
    {
        $totalWeight = array_sum(array_column($ads, 'weight'));

        if ($totalWeight < 1) {
            return null;
        }

        $draw = random_int(1, $totalWeight);

        foreach ($ads as $ad) {
            $draw -= $ad['weight'];

            if ($draw <= 0) {
                return $ad['url'];
            }
        }

        return null;
    }
}
