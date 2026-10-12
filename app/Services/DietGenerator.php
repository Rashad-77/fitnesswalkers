<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Support\Collection;

/**
 * Rule-based (non-AI) meal plan generator with costs in rupees.
 * Estimates only. Not medical advice. People who fail the safety gate are referred to the owner / a doctor.
 */
class DietGenerator
{
    public const ACTIVITY = ['sedentary' => 1.2, 'light' => 1.375, 'moderate' => 1.55, 'active' => 1.725];
    public const ALLERGENS = ['egg', 'dairy', 'fish', 'nuts', 'gluten', 'soy'];

    /** @return string[] reasons the member must NOT get an app-generated plan (empty = ok) */
    public static function safetyIssues(array $in): array
    {
        $issues = [];
        if (($in['age'] ?? 0) < 18) $issues[] = 'Members under 18 need a plan made by the owner (and ideally a doctor).';
        if (! empty($in['pregnant'])) $issues[] = 'Pregnancy or breastfeeding needs a doctor-approved diet.';
        if (! empty($in['medical'])) $issues[] = 'A medical condition or injury means your diet should be set with a doctor or the owner.';
        if (! empty($in['height']) && ! empty($in['weight']) && Bmi::calc($in['weight'], $in['height']) < 18.5) {
            $issues[] = 'Your BMI is in the underweight range, so please speak to the owner or a doctor before starting a plan.';
        }
        return $issues;
    }

    public static function targets(array $in): array
    {
        $bmr = 10 * $in['weight'] + 6.25 * $in['height'] - 5 * $in['age'] + ($in['sex'] === 'male' ? 5 : -161);
        $tdee = $bmr * (self::ACTIVITY[$in['activity']] ?? 1.375);
        $adjust = match ($in['goal']) { 'lose' => -400, 'gain' => 300, default => 0 };
        $floor = $in['sex'] === 'male' ? 1500 : 1200;
        $kcal = max($floor, round(($tdee + $adjust) / 10) * 10);
        $perKg = match ($in['goal']) { 'lose' => 1.6, 'gain' => 1.8, default => 1.4 };
        $protein = round($in['weight'] * $perKg);
        return ['kcal' => (int) $kcal, 'protein' => (int) $protein, 'floor_applied' => $kcal === $floor && ($tdee + $adjust) < $floor];
    }

    public static function foodsFor(array $in): Collection
    {
        $type = $in['diet_type'] ?? 'non_veg';
        $allowedTypes = match ($type) { 'vegan' => ['vegan'], 'vegetarian' => ['vegan', 'vegetarian'], default => ['vegan', 'vegetarian', 'non_veg'] };
        $avoid = $in['allergies'] ?? [];
        return Food::whereIn('diet_type', $allowedTypes)->get()->filter(
            fn ($f) => ! array_intersect($f->allergenList(), $avoid)
        )->values();
    }

    /** Build a 7-day plan. */
    public static function generate(array $in): array
    {
        $issues = self::safetyIssues($in);
        if ($issues) return ['blocked' => true, 'issues' => $issues];

        $t = self::targets($in);
        $foods = self::foodsFor($in);
        $meals = max(3, min(5, (int) ($in['meals'] ?? 3)));
        $budget = (float) ($in['budget'] ?? 0);

        $warnings = [];
        if ($t['floor_applied']) $warnings[] = 'Calories were raised to a safe minimum. Going lower is not recommended.';

        // First try a varied plan; if it is over budget, rebuild from the cheapest options.
        $result = null;
        foreach ([false, true] as $cheap) {
            $days = [];
            for ($d = 0; $d < 7; $d++) {
                $days[] = self::buildDay($foods, $t, $meals, $d, $cheap);
            }
            $result = self::finish(['blocked' => false, 'targets' => $t, 'meals_per_day' => $meals, 'budget' => $budget, 'days' => $days, 'warnings' => []]);
            if ($budget <= 0 || $result['avg_day_cost'] <= $budget) break;
        }
        if ($budget > 0 && $result['avg_day_cost'] > $budget) {
            $warnings[] = 'Even with the cheapest foods this plan costs about Rs. '.number_format($result['avg_day_cost']).' a day, above your budget of Rs. '.number_format($budget).'. Talk to the owner about what to cut.';
        }
        if ($foods->count() < 8) $warnings[] = 'Very few foods match your diet type and allergies, so variety is limited.';
        $result['warnings'] = $warnings;
        $avgProtein = collect($result['days'])->avg('protein');
        if ($avgProtein < $t['protein'] * 0.85) {
            $result['warnings'][] = 'With the foods that match your diet, protein averages about '.round($avgProtein).' g a day, below your '.$t['protein'].' g target. The owner can suggest extra protein sources.';
        }
        return $result;
    }

    private static function slots(int $meals): array
    {
        return match ($meals) {
            3 => ['Breakfast' => .27, 'Lunch' => .38, 'Dinner' => .35],
            4 => ['Breakfast' => .25, 'Lunch' => .35, 'Snack' => .10, 'Dinner' => .30],
            default => ['Breakfast' => .22, 'Snack 1' => .09, 'Lunch' => .32, 'Snack 2' => .09, 'Dinner' => .28],
        };
    }

    private static function buildDay(Collection $foods, array $t, int $meals, int $dayIdx, bool $cheap): array
    {
        $out = [];
        $i = 0;
        foreach (self::slots($meals) as $name => $share) {
            $out[] = self::buildMeal($foods, $name, $t['kcal'] * $share, $t['protein'] * $share, $dayIdx + $i * 2, $cheap);
            $i++;
        }
        return ['day' => $dayIdx + 1, 'meals' => $out];
    }

    /** Pick `pool[(offset) % n]` from the best-value options so days vary. */
    private static function pick(Collection $pool, int $offset, bool $cheap, string $by): ?Food
    {
        if ($pool->isEmpty()) return null;
        $sorted = $pool->sortBy(fn ($f) => $by === 'protein'
            ? $f->price_per_kg / max(1, $f->protein_100g)
            : $f->price_per_kg / max(1, $f->kcal_100g))->values();
        $n = $cheap ? min(2, $sorted->count()) : min(4, $sorted->count());
        return $sorted[$offset % $n];
    }

    public static function buildMeal(Collection $foods, string $slot, float $kcal, float $protein, int $offset, bool $cheap): array
    {
        $isSnack = str_starts_with($slot, 'Snack');
        $tag = $isSnack ? 'snack' : ($slot === 'Breakfast' ? 'breakfast' : 'main');
        // Only foods the owner tagged for this kind of meal (breakfast / main / snack).
        $pool = $foods->filter(fn ($f) => in_array($tag, array_map('trim', explode(',', (string) $f->meal_tags))));
        if ($pool->count() < 4) $pool = $foods; // tiny catalogues: fall back to everything
        $by = fn (string ...$cats) => $pool->whereIn('category', $cats);
        $items = [];

        $add = function (?Food $f, float $grams) use (&$items) {
            if (! $f || $grams < 10) return;
            $grams = round($grams / 5) * 5;
            $items[] = ['food_id' => $f->id, 'name' => $f->name, 'grams' => $grams,
                'kcal' => round($f->kcal_100g * $grams / 100), 'protein' => round($f->protein_100g * $grams / 100, 1),
                'cost' => $f->costFor($grams), 'category' => $f->category];
        };

        if ($isSnack) {
            $fruit = self::pick($by('fruit'), $offset, $cheap, 'kcal');
            $prot = self::pick($by('dairy', 'protein', 'legume', 'fat', 'grain'), $offset + 1, $cheap, 'protein');
            $add($fruit, 120);
            if ($prot) $add($prot, $prot->category === 'fat' ? 25 : min(150, max(40, ($protein * 0.7) / max(1, $prot->protein_100g) * 100)));
        } else {
            $prot = self::pick($by('protein', 'legume', 'dairy'), $offset, $cheap, 'protein');
            $veg = $slot === 'Breakfast' ? null : self::pick($by('veg'), $offset, $cheap, 'kcal');
            $grain = self::pick($by('grain'), $offset, $cheap, 'kcal');
            $fruit = $slot === 'Breakfast' ? self::pick($foods->where('category', 'fruit'), $offset, $cheap, 'kcal') : null;

            if ($prot) $add($prot, min(250, max(40, ($protein * 0.65) / max(1, $prot->protein_100g) * 100)));
            if ($veg) $add($veg, 150);
            if ($fruit) $add($fruit, 120);
            $have = array_sum(array_column($items, 'kcal'));
            // Fill remaining calories with the staple (rice, roti, bread...), up to a sensible cap.
            if ($grain) $add($grain, min(450, max(40, ($kcal - $have) / max(1, $grain->kcal_100g) * 100)));
            $have = array_sum(array_column($items, 'kcal'));
            if ($kcal - $have > 60) {
                $fat = self::pick($by('fat'), $offset, $cheap, 'kcal');
                if ($fat) $add($fat, min(30, ($kcal - $have) / max(1, $fat->kcal_100g) * 100));
            }
        }
        return ['name' => $slot, 'items' => $items];
    }

    /** Fill in totals (meal/day/week), shopping list. Called after generate and after a swap. */
    public static function finish(array $plan): array
    {
        $shop = [];
        $week = 0;
        foreach ($plan['days'] as &$day) {
            $dk = $dp = $dc = 0;
            foreach ($day['meals'] as &$meal) {
                $meal['kcal'] = array_sum(array_column($meal['items'], 'kcal'));
                $meal['protein'] = round(array_sum(array_column($meal['items'], 'protein')), 1);
                $meal['cost'] = round(array_sum(array_column($meal['items'], 'cost')), 2);
                $dk += $meal['kcal']; $dp += $meal['protein']; $dc += $meal['cost'];
                foreach ($meal['items'] as $it) {
                    $shop[$it['food_id']] ??= ['name' => $it['name'], 'grams' => 0, 'cost' => 0];
                    $shop[$it['food_id']]['grams'] += $it['grams'];
                    $shop[$it['food_id']]['cost'] += $it['cost'];
                }
            }
            unset($meal);
            $day['kcal'] = $dk; $day['protein'] = round($dp, 1); $day['cost'] = round($dc, 2);
            $week += $dc;
        }
        unset($day);
        $plan['week_cost'] = round($week, 2);
        $plan['avg_day_cost'] = round($week / max(1, count($plan['days'])), 2);
        $plan['shopping'] = array_values(collect($shop)->map(fn ($s) => [
            'name' => $s['name'], 'grams' => $s['grams'], 'cost' => round($s['cost'], 2),
        ])->sortBy('name')->all());
        return $plan;
    }

    /** Swap a meal for a different option and recalculate. */
    public static function swapMeal(array $in, array $plan, int $dayIdx, int $mealIdx): array
    {
        $foods = self::foodsFor($in);
        $meal = $plan['days'][$dayIdx]['meals'][$mealIdx];
        $t = $plan['targets'];
        $share = collect(self::slots($plan['meals_per_day']))->values()[$mealIdx] ?? .3;
        $attempt = (int) ($meal['swaps'] ?? 0) + 1;
        $new = self::buildMeal($foods, $meal['name'], $t['kcal'] * $share, $t['protein'] * $share, $dayIdx * 7 + $mealIdx * 3 + $attempt * 5 + 1, false);
        $new['swaps'] = $attempt;
        $plan['days'][$dayIdx]['meals'][$mealIdx] = $new;
        return self::finish($plan);
    }
}
