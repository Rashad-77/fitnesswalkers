<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Support\Facades\Http;

/**
 * Updates food prices (Rs. per kg).
 *
 * There is no free live feed of Sri Lankan food prices. This updater reads a JSON feed from
 * config('fitness.price_feed_url') — a list of {"name": "...", "price_per_kg": 123.0} — and
 * the owner can also upload a CSV in Admin > Foods. Protections: owner-locked prices are skipped,
 * and any change bigger than +/-40% is rejected and reported so a bad feed can't wreck costs.
 */
class PriceUpdater
{
    public const MAX_CHANGE = 0.40;

    /** @return array{updated:int, rejected:array, skipped_locked:int, unmatched:array, error:?string} */
    public static function fromFeed(?string $url = null): array
    {
        $url ??= config('fitness.price_feed_url');
        if (! $url) {
            return self::result(error: 'No price feed is configured (set PRICE_FEED_URL). Prices were not changed.');
        }
        try {
            $rows = Http::timeout(20)->get($url)->throw()->json();
        } catch (\Throwable $e) {
            return self::result(error: 'Could not read the price feed: '.$e->getMessage());
        }
        return is_array($rows) ? self::apply($rows) : self::result(error: 'The price feed was not a list.');
    }

    /** @param array<int,array{name:string,price_per_kg:float|int|string}> $rows */
    public static function apply(array $rows): array
    {
        $res = self::result();
        $foods = Food::all()->keyBy(fn ($f) => mb_strtolower($f->name));
        foreach ($rows as $row) {
            $name = mb_strtolower(trim((string) ($row['name'] ?? '')));
            $price = (float) ($row['price_per_kg'] ?? 0);
            $food = $foods[$name] ?? null;
            if (! $food) { $res['unmatched'][] = $row['name'] ?? '?'; continue; }
            if ($price <= 0) { $res['rejected'][] = "{$food->name}: invalid price"; continue; }
            if ($food->price_locked) { $res['skipped_locked']++; continue; }
            $old = (float) $food->price_per_kg;
            if ($old > 0 && abs($price - $old) / $old > self::MAX_CHANGE) {
                $res['rejected'][] = "{$food->name}: Rs. {$old} -> Rs. {$price} is a change of more than 40%, left as is";
                continue;
            }
            $food->update(['price_per_kg' => $price, 'price_updated_on' => now()->toDateString(), 'is_dummy' => false]);
            $res['updated']++;
        }
        return $res;
    }

    private static function result(?string $error = null): array
    {
        return ['updated' => 0, 'rejected' => [], 'skipped_locked' => 0, 'unmatched' => [], 'error' => $error];
    }
}
