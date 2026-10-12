<?php

namespace App\Services;

use App\Models\{Dish, Food};
use Illuminate\Support\Collection;

/**
 * Plans a week of meals from the person's daily targets (calories, protein, carbs, fat), using dishes people actually cook.
 *
 *   targets  -> split across the day's meals -> pick a dish for each meal that fits the slot, the diet and allergies, and the week's variety
 *            -> set the portion (the main course and the protein grow or shrink in whole servings) so the meal hits its share of the targets
 *            -> add a fruit dessert when a lunch or dinner has room -> balance the whole day to the targets.
 *
 * Changing a dish or a portion later shows its effect on the day against the targets (see fit()).
 * Calories, protein, carbs and fat are ESTIMATES from typical values per serving.
 */
class DishPlanner
{
    /** Portion multipliers the member can choose. Amounts snap to whole servings, so the numbers below are what is really eaten. */
    public const SCALES = [0.5, 0.75, 1.0, 1.25, 1.5, 1.75, 2.0, 2.25, 2.5];

    /** Share of the day's targets each meal gets. */
    private const SHARES = [
        3 => ['breakfast' => .28, 'lunch' => .38, 'dinner' => .34],
        4 => ['breakfast' => .25, 'lunch' => .35, 'snack_pm' => .10, 'dinner' => .30],
        5 => ['breakfast' => .22, 'snack_am' => .08, 'lunch' => .32, 'snack_pm' => .10, 'dinner' => .28],
    ];

    /* ================================================================ the dishes a person can have */

    /** @return Collection<int, Dish> dishes (with foods) that suit this person's diet, allergies and dislikes. */
    public static function eligible(array $in): Collection
    {
        $allowed = match ($in['pattern'] ?? 'everything') {
            'vegan' => [], 'lacto' => ['dairy'], 'ovo' => ['egg'], 'ovolacto' => ['dairy', 'egg'], default => ['dairy', 'egg', 'meat'],
        };
        $allergies = (array) ($in['allergies'] ?? []);
        $avoid = array_values(array_filter([! empty($in['no_beef']) ? 'beef' : null, ! empty($in['no_pork']) ? 'pork' : null]));
        return Dish::with('items.food')->where('active', true)->get()
            ->filter(fn (Dish $d) => $d->items->isNotEmpty() && $d->items->every(fn ($i) => $i->food && $i->food->active)
                && ! array_diff($d->needList(), $allowed) && ! array_intersect($d->allergenList(), $allergies) && ! array_intersect($d->avoidList(), $avoid))
            ->values();
    }

    /** One portion of a dish at a multiplier: the foods with whole-number servings, and their totals. */
    public static function dishAt(Dish $dish, float $scale): array
    {
        $items = []; $t = ['kcal' => 0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];
        foreach ($dish->items as $it) {
            $f = $it->food;
            $n = $it->scales ? max(1, (int) round($it->servings * $scale)) : (float) $it->servings;
            $row = ['food_id' => $f->id, 'name' => $f->name, 'local' => $f->local_name, 'grp' => $f->grp, 'role' => $it->role, 'servings' => $n,
                'qty' => $f->qty($n), 'g' => $f->serving_g ? (int) round($f->serving_g * $n) : null, 'kcal' => $f->kcalFor($n), 'protein' => $f->proteinFor($n),
                'carbs' => $f->carbsFor($n), 'fat' => $f->fatFor($n), 'per' => (float) $f->per_serving, 'unit' => $f->unit, 'unit_plural' => $f->unit_plural];
            $items[] = $row;
            $t['kcal'] += $row['kcal']; $t['protein'] += $row['protein']; $t['carbs'] += $row['carbs']; $t['fat'] += $row['fat'];
        }
        return ['scale' => $scale, 'items' => $items, 'kcal' => (int) $t['kcal'], 'protein' => round($t['protein'], 1), 'carbs' => round($t['carbs'], 1), 'fat' => round($t['fat'], 1)];
    }

    /** How far some totals are from a target, as a single number (0 = exactly on it). */
    public static function error(array $got, array $target): float
    {
        $e = 2.0 * abs($got['kcal'] - $target['kcal']) / max($target['kcal'], 60)
            + 1.5 * max(0, $target['protein'] - $got['protein']) / max($target['protein'], 8) + 0.4 * max(0, $got['protein'] - $target['protein'] * 1.4) / max($target['protein'], 8)
            + 1.0 * abs($got['carbs'] - $target['carbs']) / max($target['carbs'], 15)
            + 1.0 * abs($got['fat'] - $target['fat']) / max($target['fat'], 6);
        return $e;
    }

    /** The portion of this dish that fits a target best. */
    public static function bestScale(Dish $dish, array $target): array
    {
        $best = null;
        foreach (self::SCALES as $s) {
            $r = self::dishAt($dish, $s);
            $r['err'] = self::error($r, $target) + 0.02 * abs($s - 1);             // a tiny pull toward a normal portion
            if (! $best || $r['err'] < $best['err'] - 1e-9) $best = $r;
        }
        return $best;
    }

    /* ================================================================ the week */

    public static function slotsFor(int $meals): array { return array_keys(self::SHARES[max(3, min(5, $meals))]); }

    public static function generate(array $in): array
    {
        $sum = DietEngine::summary($in);
        if ($sum['blocked']) return ['blocked' => true, 'issues' => $sum['issues']];
        $t = $sum['targets'];
        $dishes = self::eligible($in);
        $meals = max(3, min(5, (int) ($in['meals'] ?? 4)));
        $shares = self::SHARES[$meals];
        $days = []; $recent = [];
        for ($d = 0; $d < 7; $d++) {
            $day = ['day' => $d + 1, 'meals' => []];
            $today = [];
            foreach ($shares as $slot => $share) {
                $target = ['kcal' => $t['kcal'] * $share, 'protein' => $t['protein'] * $share, 'carbs' => $t['carbs'] * $share, 'fat' => $t['fat'] * $share];
                $meal = self::buildMeal($dishes, $slot, $target, $d, array_merge($recent, $today));
                if (! $meal) continue;
                $today[] = $meal['dish_id'];
                $day['meals'][] = $meal;
            }
            $days[] = self::balanceDay($day, $t, $dishes, $in);
            $recent = array_slice(array_merge($recent, $today), -($meals * 3));          // no dish again within about three days
        }
        return self::finish(['format' => 'dishes', 'blocked' => false, 'targets' => $t, 'servings' => $sum['servings'], 'goal' => $in['goal'] ?? 'maintain', 'pattern' => $in['pattern'] ?? 'everything',
            'meals_per_day' => $meals, 'notes' => $sum['notes'], 'warnings' => $sum['warnings'], 'days' => $days]);
    }

    /** Picks the dish for one meal slot and sets its portion, then a dessert if there is room. */
    private static function buildMeal(Collection $dishes, string $slot, array $target, int $dayIndex, array $avoidIds): ?array
    {
        $key = str_starts_with($slot, 'snack') ? 'snack' : $slot;
        $weekday = $dayIndex < 5;
        $pool = $dishes->filter(fn (Dish $d) => $d->kind === ($key === 'snack' ? 'snack' : 'meal') && in_array($key, $d->slotList(), true));
        if ($weekday) $pool = $pool->reject(fn (Dish $d) => in_array('weekend', $d->tagList(), true));           // quick dishes on workdays
        $fresh = $pool->reject(fn (Dish $d) => in_array($d->id, $avoidIds, true));
        $pool = $fresh->isNotEmpty() ? $fresh : $pool;
        if ($pool->isEmpty()) return null;

        $best = null;
        foreach ($pool as $dish) {
            $r = self::bestScale($dish, $target);
            $score = $r['err'] + 0.35 * self::hash01($dayIndex * 31 + crc32($slot), $dish->id);                  // a little variety, the same every time for the same plan
            if (! $best || $score < $best['score']) $best = ['score' => $score, 'dish' => $dish, 'r' => $r];
        }
        $meal = self::mealFrom($best['dish'], $best['r']['scale'], $slot);
        if (in_array($slot, ['lunch', 'dinner'], true)) {
            $left = $target['kcal'] - $meal['dish_totals']['kcal'];
            if ($left >= 40) {
                $des = $dishes->filter(fn (Dish $d) => $d->kind === 'dessert' && in_array($slot, $d->slotList(), true) && ! in_array($d->id, $avoidIds, true));
                $bestD = null;
                foreach ($des as $dd) {
                    foreach ([1.0, 1.5, 2.0] as $s) {
                        $r = self::dishAt($dd, $s);
                        if ($r['kcal'] > $left * 1.3) continue;
                        $score = abs($r['kcal'] - $left) / max($left, 40) + 0.3 * self::hash01($dayIndex * 17 + crc32($slot), $dd->id);
                        if (! $bestD || $score < $bestD['score']) $bestD = ['score' => $score, 'dish' => $dd, 'scale' => $s];
                    }
                }
                if ($bestD) $meal = self::withDessert($meal, $bestD['dish'], $bestD['scale']);
            }
        }
        return $meal;
    }

    /** A meal record for a dish at a portion. */
    public static function mealFrom(Dish $dish, float $scale, string $slot): array
    {
        $r = self::dishAt($dish, $scale);
        return ['slot' => $slot, 'name' => DietEngine::SLOT_NAMES[$slot] ?? ucfirst($slot), 'dish_id' => $dish->id, 'dish' => $dish->name, 'local' => $dish->local_name,
            'tags' => $dish->tagList(), 'prep' => $dish->prep_minutes, 'method' => $dish->method, 'scale' => $scale, 'items' => $r['items'], 'dish_totals' => self::totals($r),
            'dessert' => null, 'edited' => false];
    }

    public static function withDessert(array $meal, ?Dish $dessert, float $scale = 1.0): array
    {
        if (! $dessert) { $meal['dessert'] = null; return $meal; }
        $r = self::dishAt($dessert, $scale);
        $meal['dessert'] = ['dish_id' => $dessert->id, 'dish' => $dessert->name, 'scale' => $scale, 'items' => $r['items'], 'totals' => self::totals($r)];
        return $meal;
    }

    private static function totals(array $r): array { return ['kcal' => $r['kcal'], 'protein' => $r['protein'], 'carbs' => $r['carbs'], 'fat' => $r['fat']]; }

    /** After the meals are chosen, nudge portions a step at a time until the whole day is as close to the targets as it can get. */
    private static function balanceDay(array $day, array $t, Collection $dishes, array $in): array
    {
        $byId = $dishes->keyBy('id');
        $dayErr = function (array $meals) use ($t) {
            $tot = ['kcal' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0];
            foreach ($meals as $m) foreach (self::mealTotals($m) as $k => $v) $tot[$k] += $v;
            return self::error($tot, $t);
        };
        for ($i = 0; $i < 10; $i++) {
            $cur = $dayErr($day['meals']); $bestErr = $cur; $bestMove = null;
            foreach ($day['meals'] as $mi => $m) {
                if (! isset($byId[$m['dish_id']])) continue;
                $dish = $byId[$m['dish_id']];
                $idx = array_search($m['scale'], self::SCALES, true);
                foreach ([-1, 1] as $dir) {
                    $j = $idx === false ? null : $idx + $dir;
                    if ($j === null || ! isset(self::SCALES[$j])) continue;
                    $try = $day['meals'];
                    $try[$mi] = self::keepDessert(self::mealFrom($dish, self::SCALES[$j], $m['slot']), $m);
                    $e = $dayErr($try);
                    if ($e < $bestErr - 1e-6) { $bestErr = $e; $bestMove = [$mi, $try[$mi]]; }
                }
            }
            if (! $bestMove) break;
            $day['meals'][$bestMove[0]] = $bestMove[1];
        }
        return $day;
    }

    private static function keepDessert(array $new, array $old): array { $new['dessert'] = $old['dessert']; $new['edited'] = $old['edited'] ?? false; return $new; }

    /** Totals of a meal: its dish and its dessert. */
    public static function mealTotals(array $m): array
    {
        $t = $m['dish_totals'];
        if (! empty($m['dessert'])) foreach ($m['dessert']['totals'] as $k => $v) $t[$k] += $v;
        return $t;
    }

    /* ================================================================ totals, shopping list, group servings */

    /** Totals for every meal and day, servings by food group, and the week's shopping list. Call after building and after any change. */
    public static function finish(array $plan): array
    {
        $shop = []; $sum = ['kcal' => 0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0];
        foreach ($plan['days'] as &$day) {
            $dt = ['kcal' => 0, 'protein' => 0.0, 'carbs' => 0.0, 'fat' => 0.0]; $groups = array_fill_keys(DietEngine::GROUPS, 0);
            foreach ($day['meals'] as &$m) {
                $mt = self::mealTotals($m);
                $m['kcal'] = (int) $mt['kcal']; $m['protein'] = round($mt['protein'], 1); $m['carbs'] = round($mt['carbs'], 1); $m['fat'] = round($mt['fat'], 1);
                foreach ($dt as $k => $_) $dt[$k] += $mt[$k];
                foreach (array_merge($m['items'], $m['dessert']['items'] ?? []) as $it) {
                    $groups[$it['grp']] = ($groups[$it['grp']] ?? 0) + $it['servings'];
                    $shop[$it['food_id']] ??= ['food_id' => $it['food_id'], 'name' => $it['name'], 'grp' => $it['grp'], 'servings' => 0.0, 'per' => $it['per'], 'unit' => $it['unit'], 'unit_plural' => $it['unit_plural'], 'gper' => $it['g'] ? $it['g'] / max(0.01, $it['servings']) : null];
                    $shop[$it['food_id']]['servings'] += $it['servings'];
                }
            }
            unset($m);
            $day['kcal'] = (int) $dt['kcal']; $day['protein'] = (int) round($dt['protein']); $day['carbs'] = (int) round($dt['carbs']); $day['fat'] = (int) round($dt['fat']); $day['groups'] = $groups;
            foreach ($sum as $k => $_) $sum[$k] += $dt[$k];
        }
        unset($day);
        $n = max(1, count($plan['days']));
        $plan['avg_kcal'] = (int) round($sum['kcal'] / $n); $plan['avg_protein'] = (int) round($sum['protein'] / $n); $plan['avg_carbs'] = (int) round($sum['carbs'] / $n); $plan['avg_fat'] = (int) round($sum['fat'] / $n);
        $order = array_flip(DietEngine::GROUPS);
        $plan['shopping'] = array_values(collect($shop)->map(function ($s) {
            $g = $s['gper'] ? (int) round($s['gper'] * $s['servings']) : null;
            return $s + ['qty' => Food::measure($s['per'], $s['unit'], $s['unit_plural'], $s['servings']), 'grams' => $g, 'buy' => self::buyAmount($s, $g)];
        })->sortBy([fn ($a, $b) => ($order[$a['grp']] ?? 99) <=> ($order[$b['grp']] ?? 99), fn ($a, $b) => strcmp($a['name'], $b['name'])])->all());
        return $plan;
    }

    /** What to buy, rounded to an amount a shop actually sells (nearest 50 g, or 100 g above half a kilo, or whole pieces). */
    private static function buyAmount(array $s, ?int $g): string
    {
        if ($g) {
            $r = $g >= 500 ? (int) (ceil($g / 100) * 100) : (int) (ceil($g / 50) * 50);
            return $r >= 1000 ? rtrim(rtrim(number_format($r / 1000, 1), '0'), '.').' kg' : $r.' g';
        }
        if (in_array($s['unit'], ['egg', 'duck egg', 'piece', 'medium banana', 'medium mango', 'small apple', 'small orange', 'small pear', 'small guava', 'cob', 'date', 'medium potato'], true) || str_contains($s['unit'], 'medium') || str_contains($s['unit'], 'small')) {
            $n = (int) ceil($s['per'] * $s['servings'] - 1e-9);
            return $n.' '.($n === 1 ? $s['unit'] : ($s['unit_plural'] ?: $s['unit']));
        }
        return Food::measure($s['per'], $s['unit'], $s['unit_plural'], $s['servings']);
    }

    /* ================================================================ changes after the plan is made */

    /** Use a different dish for a meal (or its dessert), at a portion or at the one that fits that meal best. */
    public static function swapDish(array $in, array $plan, int $di, int $mi, int $dishId, ?float $scale = null, string $part = 'main'): array
    {
        $meal = $plan['days'][$di]['meals'][$mi] ?? abort(404);
        $dish = self::eligible($in)->firstWhere('id', $dishId);
        abort_unless($dish, 404);
        $t = $plan['targets'];
        if ($part === 'dessert') {
            abort_unless($dish->kind === 'dessert', 404);
            $meal = self::withDessert($meal, $dish, $scale && in_array($scale, self::SCALES, true) ? $scale : 1.0);
        } else {
            abort_unless($dish->kind === (str_starts_with($meal['slot'], 'snack') ? 'snack' : 'meal') && in_array(str_starts_with($meal['slot'], 'snack') ? 'snack' : $meal['slot'], $dish->slotList(), true), 404);
            if (! $scale || ! in_array($scale, self::SCALES, true)) {
                $share = self::SHARES[$plan['meals_per_day']][$meal['slot']] ?? .3;
                $scale = self::bestScale($dish, ['kcal' => $t['kcal'] * $share, 'protein' => $t['protein'] * $share, 'carbs' => $t['carbs'] * $share, 'fat' => $t['fat'] * $share])['scale'];
            }
            $new = self::mealFrom($dish, $scale, $meal['slot']);
            $new['dessert'] = $meal['dessert'];
            $meal = $new;
        }
        $meal['edited'] = true;
        $plan['days'][$di]['meals'][$mi] = $meal;
        return self::finish($plan);
    }

    /** "Surprise me": the next best dish for this meal that is not already on the menu that day. */
    public static function reroll(array $in, array $plan, int $di, int $mi): array
    {
        $meal = $plan['days'][$di]['meals'][$mi] ?? abort(404);
        $key = str_starts_with($meal['slot'], 'snack') ? 'snack' : $meal['slot'];
        $t = $plan['targets']; $share = self::SHARES[$plan['meals_per_day']][$meal['slot']] ?? .3;
        $target = ['kcal' => $t['kcal'] * $share, 'protein' => $t['protein'] * $share, 'carbs' => $t['carbs'] * $share, 'fat' => $t['fat'] * $share];
        $onToday = array_column($plan['days'][$di]['meals'], 'dish_id');
        $attempt = (int) ($meal['swaps'] ?? 0) + 1;
        $ranked = self::eligible($in)->filter(fn (Dish $d) => $d->kind === ($key === 'snack' ? 'snack' : 'meal') && in_array($key, $d->slotList(), true) && $d->id !== $meal['dish_id'] && ! in_array($d->id, $onToday, true))
            ->map(fn (Dish $d) => ['dish' => $d, 'r' => self::bestScale($d, $target)])->sortBy(fn ($x) => $x['r']['err'])->values();
        if ($ranked->isEmpty()) return $plan;
        $pick = $ranked[($attempt - 1) % min(5, $ranked->count())];
        $new = self::mealFrom($pick['dish'], $pick['r']['scale'], $meal['slot']);
        $new['dessert'] = $meal['dessert']; $new['edited'] = true; $new['swaps'] = $attempt;
        $plan['days'][$di]['meals'][$mi] = $new;
        return self::finish($plan);
    }

    /** Eat more or less of a dish (or its dessert). */
    public static function setScale(array $in, array $plan, int $di, int $mi, float $scale, string $part = 'main'): array
    {
        $meal = $plan['days'][$di]['meals'][$mi] ?? abort(404);
        abort_unless(in_array($scale, self::SCALES, true), 422);
        $id = $part === 'dessert' ? ($meal['dessert']['dish_id'] ?? null) : $meal['dish_id'];
        abort_unless($id, 404);
        return self::swapDish($in, $plan, $di, $mi, $id, $scale, $part);
    }

    /** Remove the dessert from a meal. */
    public static function dropDessert(array $plan, int $di, int $mi): array
    {
        $plan['days'][$di]['meals'][$mi]['dessert'] = null;
        $plan['days'][$di]['meals'][$mi]['edited'] = true;
        return self::finish($plan);
    }

    /* ================================================================ for the screen: options and how well a day fits */

    /** Every dish a meal could be changed to, with its numbers at each portion, for the Change panel. */
    public static function options(array $in, string $slot, string $kind): array
    {
        $key = str_starts_with($slot, 'snack') ? 'snack' : $slot;
        return self::eligible($in)->filter(fn (Dish $d) => $d->kind === $kind && in_array($key, $d->slotList(), true))->map(function (Dish $d) {
            $opts = [];
            foreach (self::SCALES as $s) { $r = self::dishAt($d, $s); $opts[] = [$s, $r['kcal'], round($r['protein']), round($r['carbs']), round($r['fat']), self::servingsText($r['items'])]; }
            return ['id' => $d->id, 'n' => $d->name, 'l' => $d->local_name, 'prep' => $d->prep_minutes, 'tags' => $d->tagList(), 'o' => $opts];
        })->sortBy('n')->values()->all();
    }

    /** "1 ½ cups red rice, 2 fish..." is too long for a menu, so the Change panel shows just the main course amount. */
    private static function servingsText(array $items): string
    {
        $main = collect($items)->firstWhere('role', 'main') ?? ($items[0] ?? null);
        return $main ? $main['qty'].' '.preg_replace('/,.*$/', '', $main['name']) : '';
    }

    /**
     * How a day's totals compare with the targets, per nutrient: 'ok' when close, 'low' or 'high' when not, and by how much.
     * Protein only counts as a problem when it is low or very high; carbs and fat when they are well off.
     */
    public static function fit(array $totals, array $targets): array
    {
        $rule = ['kcal' => [0.93, 1.07], 'protein' => [0.9, 1.45], 'carbs' => [0.85, 1.15], 'fat' => [0.8, 1.2]];
        $out = [];
        foreach ($rule as $k => [$lo, $hi]) {
            $target = max(1, $targets[$k]); $got = $totals[$k] ?? 0; $r = $got / $target;
            $out[$k] = ['got' => (int) round($got), 'target' => (int) round($targets[$k]), 'diff' => (int) round($got - $targets[$k]), 'pct' => (int) round($r * 100), 'state' => $r < $lo ? 'low' : ($r > $hi ? 'high' : 'ok')];
        }
        $out['all'] = collect($out)->every(fn ($x) => $x['state'] === 'ok') ? 'ok' : (collect($out)->contains(fn ($x) => $x['state'] !== 'ok' && abs($x['pct'] - 100) > 25) ? 'off' : 'near');
        return $out;
    }

    /** The same fixed 0..1 number for the same inputs, so a plan is the same every time it is built. */
    private static function hash01(int $seed, int $id): float
    {
        return (crc32($seed.':'.$id) % 10000) / 10000;
    }
}
