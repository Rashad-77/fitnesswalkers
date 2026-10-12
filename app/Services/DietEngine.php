<?php

namespace App\Services;

use App\Models\Food;
use Illuminate\Support\Collection;

/**
 * Builds a 7-day meal plan from a person's answers, in the way a dietitian would:
 *
 *   1. Safety check          people who need a professional are referred, not given a plan
 *   2. Energy and protein    Mifflin-St Jeor resting energy, times activity, adjusted for the goal; protein from body weight and training
 *   3. Servings per group    the daily servings in Sri Lanka's Food Based Dietary Guidelines (Ministry of Health, 3rd edition 2021),
 *                            which run from 1,600 kcal (a sedentary woman) to 2,600 kcal (a very active man), scaled to this person
 *   4. Fit to the person     eating pattern (vegetarian types), allergies, foods avoided, BMI, and extra protein for people who train
 *   5. Real foods            servings are turned into foods from the Sri Lankan catalogue, in household measures, spread over meals
 *
 * Calories and protein are ESTIMATES (typical values per serving) and are shown as "about". This is a general guide, not medical advice.
 */
class DietEngine
{
    public const GROUPS = ['cereal', 'veg', 'fruit', 'pulse', 'protein', 'egg', 'dairy', 'nuts', 'coconut', 'oil'];

    public const PATTERNS = [
        'everything' => 'I eat everything', 'semi' => 'Mostly plants, a little fish, egg or meat', 'ovolacto' => 'Vegetarian: milk and eggs',
        'lacto' => 'Vegetarian: milk, no eggs', 'ovo' => 'Vegetarian: eggs, no milk', 'vegan' => 'Vegan: plants only',
    ];

    public const SLOT_NAMES = ['breakfast' => 'Breakfast', 'snack_am' => 'Mid-morning snack', 'lunch' => 'Lunch', 'snack_pm' => 'Evening snack', 'dinner' => 'Dinner'];

    /** The guideline's range of servings a day: [low, high]. */
    public const RANGE = ['cereal' => [8, 13], 'veg' => [3, 5], 'fruit' => [2, 3], 'pulse' => [3, 5], 'protein' => [2, 4], 'egg' => [1, 1], 'dairy' => [1, 1], 'nuts' => [2, 2], 'coconut' => [3, 6], 'oil' => [1, 3]];

    /** Which meal gets the next serving of each group, in order of preference. The guideline spreads rice over the three main meals and puts protein with lunch and dinner. */
    private const PRIORITY = [
        'cereal' => ['lunch', 'dinner', 'breakfast'], 'protein' => ['lunch', 'dinner'], 'egg' => ['breakfast', 'snack_pm', 'lunch'],
        'pulse' => ['lunch', 'dinner', 'breakfast', 'snack_pm', 'snack_am'], 'veg' => ['lunch', 'dinner'], 'fruit' => ['breakfast', 'snack_pm', 'snack_am', 'dinner', 'lunch'],
        'dairy' => ['snack_am', 'breakfast', 'snack_pm'], 'nuts' => ['snack_am', 'snack_pm', 'breakfast', 'dinner'],
        'coconut' => ['lunch', 'dinner', 'breakfast'], 'oil' => ['lunch', 'dinner'],
    ];

    /* ============================================================== 1. safety */

    /** @return string[] reasons this person must NOT get an app-made plan (empty = fine) */
    public static function safetyIssues(array $in): array
    {
        $issues = [];
        if (($in['age'] ?? 0) < 18) $issues[] = 'Members under 18 need a plan made by a dietitian or doctor, because they are still growing.';
        if (! empty($in['pregnant'])) $issues[] = 'Pregnancy or breastfeeding needs a diet approved by a doctor.';
        if (! empty($in['medical'])) $issues[] = 'A medical condition or injury means your diet should be set with a doctor or dietitian.';
        if (! empty($in['eating_disorder'])) $issues[] = 'If you have had an eating disorder, please plan your food with a doctor or dietitian who knows your history.';
        if (! empty($in['height']) && ! empty($in['weight']) && Bmi::calc($in['weight'], $in['height']) < 18.5) {
            $issues[] = 'Your BMI is in the underweight range, so please speak to a doctor or dietitian before starting a plan.';
        }
        return $issues;
    }

    /* ============================================================== 2. energy and protein */

    public static function targets(array $in): array
    {
        $w = (float) $in['weight']; $h = (float) $in['height']; $age = (int) $in['age']; $male = ($in['sex'] ?? 'male') === 'male';
        $bmr = 10 * $w + 6.25 * $h - 5 * $age + ($male ? 5 : -161);                       // Mifflin-St Jeor

        // Daily life gives the base activity level; training days are added on top (about 6 kcal a minute).
        $base = ['sitting' => 1.40, 'standing' => 1.55, 'heavy' => 1.75][$in['job'] ?? 'sitting'] ?? 1.40;
        $days = max(0, min(7, (int) ($in['train_days'] ?? 0)));
        $mins = max(15, min(180, (int) ($in['train_minutes'] ?? 45)));
        $tdee = min($bmr * 2.2, $bmr * $base + $days * $mins * 6 / 7);
        $tdee *= ['slow' => 0.95, 'fast' => 1.05][$in['metabolism'] ?? 'average'] ?? 1.0;          // how the person says their body responds to food

        $goal = $in['goal'] ?? 'maintain';
        $steady = ($in['pace'] ?? 'gentle') === 'steady';
        $adjust = match ($goal) { 'lose' => $steady ? -500 : -300, 'gain' => $steady ? 400 : 250, default => 0 };
        $adjust = max($adjust, -0.25 * $tdee);                                            // never a deficit bigger than a quarter of what you burn
        $floor = $male ? 1500 : 1200;
        $kcal = max($floor, round(($tdee + $adjust) / 10) * 10);

        // Protein: more for people who train. The International Society of Sports Nutrition range for exercising people is 1.4 to 2.0 g per kg;
        // we use the lower half, which the guideline's foods can meet without distorting the plan.
        $perKg = $days >= 3 ? ($goal === 'maintain' ? 1.4 : 1.6) : ($days >= 1 ? 1.2 : 1.0);
        if (in_array($in['achievement'] ?? '', ['muscle'], true) || in_array($in['body_goal'] ?? '', ['athletic', 'muscular'], true)) $perKg += 0.2;   // building muscle needs more protein
        $protein = min(160, round($w * $perKg));

        return ['bmr' => (int) round($bmr), 'tdee' => (int) round($tdee), 'pal' => round($tdee / max(1, $bmr), 2), 'kcal' => (int) $kcal,
            'protein' => (int) $protein, 'per_kg' => $perKg, 'floor_applied' => $kcal === $floor && ($tdee + $adjust) < $floor];
    }

    /* ============================================================== 3 + 4. servings per group */

    /** Foods this person can be given: on sale, right eating pattern, no allergens, nothing they avoid. Liked foods are flagged. */
    public static function eligible(array $in): Collection
    {
        $diets = match ($in['pattern'] ?? 'everything') {
            'vegan' => ['vegan'], 'lacto' => ['vegan', 'dairy'], 'ovo' => ['vegan', 'egg'], 'ovolacto' => ['vegan', 'dairy', 'egg'],
            default => ['vegan', 'dairy', 'egg', 'meat'],
        };
        $allergies = (array) ($in['allergies'] ?? []);
        $dislikes = array_map('intval', (array) ($in['dislikes'] ?? []));
        $likes = array_map('intval', (array) ($in['likes'] ?? []));
        $tags = array_values(array_filter([! empty($in['no_beef']) ? 'beef' : null, ! empty($in['no_pork']) ? 'pork' : null]));
        return Food::where('active', true)->whereIn('diet', $diets)->get()
            ->filter(fn ($f) => ! array_intersect($f->allergenList(), $allergies) && ! in_array($f->id, $dislikes, true) && ! array_intersect($f->tagList(), $tags))
            ->each(fn ($f) => $f->setAttribute('liked', in_array($f->id, $likes, true)))
            ->each(function ($f) use ($in) {                    // foods the person says they usually eat at breakfast, lunch or dinner
                $types = [];
                foreach (['breakfast' => 'breakfast', 'lunch' => 'main', 'dinner' => 'main'] as $slot => $type) {
                    if (in_array($f->id, array_map('intval', (array) ($in['usuals'][$slot] ?? [])), true)) $types[] = $type;
                }
                $f->setAttribute('usual_types', array_values(array_unique($types)));
                if ($types) $f->setAttribute('liked', true);       // usual foods are kept when the plan is balanced to the calorie target
            })
            ->values();
    }

    /**
     * Daily servings per food group for this person, the estimate behind them, and any notes.
     * @return array{servings: array<string,int>, est: array{kcal:int, protein:int}, notes: string[], warnings: string[]}
     */
    public static function servings(array $in, array $t, Collection $foods): array
    {
        $notes = []; $warnings = [];
        $x = ($t['kcal'] - 1600) / 1000;                       // 0 = the guideline's lowest energy level, 1 = its highest
        $c = max(0, min(1, $x)); $xx = max(-.4, min(1.6, $x));
        $lerp = fn ($lo, $hi, $p) => $lo + ($hi - $lo) * $p;
        $s = [
            'cereal' => $lerp(8, 13, $xx), 'veg' => $lerp(3, 5, $c), 'fruit' => $lerp(2, 3, $c), 'pulse' => $lerp(3, 5, $xx), 'protein' => $lerp(2, 4, $xx),
            'egg' => 1, 'dairy' => 1, 'nuts' => 2, 'coconut' => $lerp(3, 6, $xx), 'oil' => $lerp(1, 3, $xx),
        ];
        $s = array_map(fn ($v) => (int) round($v), $s);
        $s['cereal'] = max(7, $s['cereal']); $s['pulse'] = max(2, $s['pulse']); $s['protein'] = max(1, $s['protein']); $s['coconut'] = max(2, $s['coconut']);

        // The guideline: people with a BMI of 25 or more need fewer cereal servings.
        $bmi = Bmi::calc($in['weight'], $in['height']);
        if ($bmi >= 27.5) { $s['cereal'] = max(7, $s['cereal'] - 2); $notes[] = 'Your BMI is high, so the guidelines suggest fewer rice and cereal servings. This plan has 2 fewer than usual for your energy level.'; }
        elseif ($bmi >= 25) { $s['cereal'] = max(7, $s['cereal'] - 1); $notes[] = 'With a BMI of 25 or more the guidelines suggest slightly fewer rice and cereal servings, so this plan has 1 fewer than usual.'; }

        $has = fn (string $g) => $foods->where('grp', $g)->isNotEmpty();
        $pattern = $in['pattern'] ?? 'everything';

        if ($pattern === 'semi') { $s['pulse'] += max(0, $s['protein'] - 2); $s['protein'] = min($s['protein'], 2); }   // mostly plants: animal foods kept small
        if ($pattern === 'vegan') $s['nuts'] = 3;
        if ($pattern === 'lacto' && $has('dairy')) $s['dairy'] = 2;
        if ($pattern === 'ovo' && $has('egg')) $s['egg'] = 2;
        foreach (['protein', 'egg', 'dairy'] as $g) {                // a whole group is out: its protein moves to pulses
            if (! $has($g) && $s[$g] > 0) { $s['pulse'] += $g === 'protein' ? $s[$g] : 1; $s[$g] = 0; }
        }
        foreach (['nuts', 'coconut', 'oil'] as $g) if (! $has($g)) $s[$g] = 0;
        if (! $has('pulse')) $warnings[] = 'Very few pulses are left after your choices, so protein will be low.';

        // Average calories and protein of one serving in each group, from the foods this person can have.
        $avg = [];
        foreach (self::GROUPS as $g) {
            $pool = $foods->where('grp', $g);
            $avg[$g] = ['k' => (float) ($pool->avg('kcal_serving') ?: 0), 'p' => (float) ($pool->avg('protein_serving') ?: 0)];
        }
        $est = function () use (&$s, $avg) {
            $k = $p = 0.0;
            foreach (self::GROUPS as $g) { $k += $s[$g] * $avg[$g]['k']; $p += $s[$g] * $avg[$g]['p']; }
            return ['kcal' => (int) round($k), 'protein' => (int) round($p)];
        };

        // People who train need more protein than the everyday range gives. Add servings of protein foods within sensible limits,
        // pay for the energy by trimming rice, and say so when the result goes beyond the guideline's range.
        $noFlesh = ! $has('protein');                                  // vegetarians have no fish or meat, so pulses and milk go a little higher
        $cap = ['protein' => 5, 'pulse' => $noFlesh ? 8 : 6, 'dairy' => $noFlesh ? 3 : 2, 'nuts' => 3, 'egg' => max(1, $s['egg'])];
        $guard = 0; $beyond = [];
        while ($est()['protein'] < $t['protein'] * 0.97 && $guard++ < 10) {
            $moved = false;
            foreach ($cap as $g => $max) {
                if ($has($g) && $s[$g] < $max) {
                    $s[$g]++; $moved = true;
                    if ($s[$g] > self::RANGE[$g][1]) $beyond[$g] = true;
                    break;
                }
            }
            if (! $moved) break;
        }
        // Keep the energy close to the target. Coconut and oil are 'as you prefer' in the guideline, so they give way first, then rice (never below 7 servings).
        $trim = function () use (&$s) {
            foreach ([['coconut', 3], ['oil', 1], ['cereal', 7]] as [$g, $min]) if ($s[$g] > $min) { $s[$g]--; return true; }
            return false;
        };
        while ($est()['kcal'] > $t['kcal'] * 1.05 && $trim());
        while ($est()['kcal'] < $t['kcal'] * 0.93 && $s['cereal'] < 16) $s['cereal']++;

        $final = $est();
        if ($beyond) { $names = array_map(fn ($g) => strtolower(Food::SHORT[$g]), array_keys($beyond)); $last = array_pop($names);
            $notes[] = 'To reach your protein target while training, this plan has more '.($names ? implode(', ', $names).' and '.$last : $last).' than the everyday guideline range.'; }
        if ($final['protein'] < $t['protein'] * 0.9) {
            $warnings[] = 'Your protein target is about '.$t['protein'].' g a day, but the foods you can eat give about '.$final['protein'].' g. A dietitian can suggest ways to close the gap.';
        }
        if ($t['floor_applied']) $warnings[] = 'Calories were raised to a safe minimum. Going lower is not recommended.';
        if ($pattern === 'vegan') $notes[] = 'A vegan diet has no natural vitamin B12. Please talk to a doctor about a supplement, and eat a variety of pulses, green leaves and seeds for iron, zinc and calcium.';
        elseif (in_array($pattern, ['lacto', 'ovo', 'ovolacto'], true)) $notes[] = 'Vegetarians can run low on iron, zinc and B12. Eat green leaves with a squeeze of lime or a vitamin C fruit, and keep milk or eggs in your day.';
        if (! $has('protein') && in_array($pattern, ['everything', 'semi'], true)) $notes[] = 'No fish or meat is included because of the foods you chose to avoid, so pulses carry more of your protein.';

        return ['servings' => $s, 'est' => $final, 'notes' => $notes, 'warnings' => $warnings];
    }

    /** Targets and servings without the daily menus: used by the live preview in the questionnaire. */
    public static function summary(array $in): array
    {
        $issues = self::safetyIssues($in);
        $t = self::targets($in);
        if ($issues) return ['blocked' => true, 'issues' => $issues, 'targets' => $t];
        return ['blocked' => false, 'targets' => $t] + self::servings($in, $t, self::eligible($in));
    }

    /* ============================================================== 5. foods and meals */

    private static function slots(int $meals): array
    {
        return match ($meals) { 3 => ['breakfast', 'lunch', 'dinner'], 4 => ['breakfast', 'lunch', 'snack_pm', 'dinner'], default => ['breakfast', 'snack_am', 'lunch', 'snack_pm', 'dinner'] };
    }

    /** @return array<string,array<int,array>> slot => list of needs [group, sub|null, servings] */
    private static function allocate(array $servings, array $slots, int $day): array
    {
        $out = array_fill_keys($slots, []);
        $put = function (string $slot, string $g, ?string $sub) use (&$out) {
            foreach ($out[$slot] as $k => $need) {
                if ($need[0] === $g && $need[1] === $sub) { $out[$slot][$k][2]++; return; }
            }
            $out[$slot][] = [$g, $sub, 1];
        };
        foreach (self::GROUPS as $g) {
            $n = (int) ($servings[$g] ?? 0);
            if ($n <= 0) continue;
            $prio = array_values(array_filter(self::PRIORITY[$g], fn ($sl) => in_array($sl, $slots, true))) ?: [$slots[0]];
            if ($g === 'veg') {                                 // each day: a green leaf (two on big days), a vitamin A vegetable, the rest others
                $leaf = $n >= 5 ? 2 : 1; $vita = ($n >= 4 || $day % 2 === 0) ? 1 : 0;
                $types = array_merge(array_fill(0, $leaf, 'leaf'), array_fill(0, $vita, 'vita'), array_fill(0, max(0, $n - $leaf - $vita), 'other'));
                foreach ($types as $i => $type) $put($prio[($i + $day) % count($prio)], 'veg', $type);
                continue;
            }
            for ($i = 0; $i < $n; $i++) $put($prio[$i % count($prio)], $g, null);
        }
        return $out;
    }

    private static function hash01(int $seed, int $id): float
    {
        $v = sin($seed * 12.9898 + $id * 78.233) * 43758.5453;
        return $v - floor($v);
    }

    /** Chooses one food from a pool: favours liked foods, whole grains and fish, avoids repeats, and varies from day to day. */
    private static function pick(Collection $pool, string $type, int $seed, array $avoid, string $group): ?Food
    {
        $c = $pool->filter(fn ($f) => in_array($type, $f->mealTagList(), true));
        if ($c->isEmpty()) $c = $pool;
        $subW = $group === 'protein' ? ['fish' => 2.0, 'poultry' => 1.4, 'dried' => 1.0, 'shellfish' => .7, 'red' => .5] : [];
        $best = null; $bestScore = -1;
        foreach ($c as $f) {
            $w = 1.0;
            if ($f->getAttribute('liked')) $w *= 4;
            if (in_array($type, (array) $f->getAttribute('usual_types'), true)) $w *= 2;
            if ($f->whole_grain) $w *= 1.7;
            if ($f->limited) $w *= .2;
            $w *= $subW[$f->sub] ?? 1;
            if (in_array($f->id, $avoid, true)) $w *= .12;
            $score = $w * (0.4 + self::hash01($seed, $f->id));
            if ($score > $bestScore) { $bestScore = $score; $best = $f; }
        }
        return $best;
    }

    public static function item(Food $f, int $servings): array
    {
        return ['food_id' => $f->id, 'name' => $f->name, 'local' => $f->local_name, 'grp' => $f->grp, 'sub' => $f->sub, 'servings' => $servings,
            'qty' => $f->qty($servings), 'kcal' => $f->kcalFor($servings), 'protein' => $f->proteinFor($servings),
            'per' => $f->per_serving, 'unit' => $f->unit, 'unit_plural' => $f->unit_plural, 'g' => $f->serving_g, 'whole' => $f->whole_grain];
    }

    /** Turns one meal's needs ("2 servings of cereal, 1 of fish...") into foods. */
    private static function fillMeal(array $needs, string $slot, Collection $foods, int $seed, array $avoid): array
    {
        $type = str_starts_with($slot, 'snack') ? 'snack' : ($slot === 'breakfast' ? 'breakfast' : 'main');
        $items = []; $usedHere = [];
        foreach ($needs as $ni => [$g, $sub, $n]) {
            $pool = $foods->where('grp', $g);
            if ($sub !== null && $pool->where('sub', $sub)->isNotEmpty()) $pool = $pool->where('sub', $sub);
            if ($pool->isEmpty() || $n <= 0) continue;
            $chunks = in_array($g, ['veg', 'fruit'], true) ? array_fill(0, $n, 1) : ($g === 'pulse' && $n >= 3 ? [$n - 1, 1] : [$n]);
            foreach ($chunks as $ci => $cs) {
                $f = self::pick($pool, $type, $seed + $ni * 17 + $ci * 5, array_merge($avoid, $usedHere), $g);
                if (! $f) continue;
                $usedHere[] = $f->id;
                foreach ($items as $k => $it) {                      // the same food twice in a meal is one bigger portion
                    if ($it['food_id'] === $f->id) { $items[$k] = self::item($f, $it['servings'] + $cs); continue 2; }
                }
                $items[] = self::item($f, $cs);
            }
        }
        return $items;
    }

    /** The guideline asks for at least half of the rice and cereals to be whole grain or less polished. Swap refined foods the person did not ask for. */
    private static function ensureWholeGrain(array $meals, Collection $foods): array
    {
        $total = $whole = 0;
        foreach ($meals as $m) foreach ($m['items'] as $it) if ($it['grp'] === 'cereal') { $total += $it['servings']; if ($it['whole']) $whole += $it['servings']; }
        if ($total === 0 || $whole * 2 >= $total) return $meals;
        $wholeFoods = $foods->where('grp', 'cereal')->where('whole_grain', true);
        foreach ($meals as $mi => $m) {
            foreach ($m['items'] as $ii => $it) {
                if ($it['grp'] !== 'cereal' || $it['whole'] || $whole * 2 >= $total) continue;
                $orig = $foods->firstWhere('id', $it['food_id']);
                if ($orig && $orig->getAttribute('liked')) continue;
                $type = str_starts_with($m['slot'], 'snack') ? 'snack' : ($m['slot'] === 'breakfast' ? 'breakfast' : 'main');
                $same = $wholeFoods->where('sub', $it['sub']);
                $swap = self::pick($same->isNotEmpty() ? $same : $wholeFoods, $type, 7 + $mi + $ii, [], 'cereal');
                if (! $swap) continue;
                $meals[$mi]['items'][$ii] = self::item($swap, $it['servings']);
                $whole += $it['servings'];
            }
        }
        return $meals;
    }

    private static function buildDay(array $servings, array $slots, Collection $foods, int $di, int $attempt, array $prevIds, int $target): array
    {
        $alloc = self::allocate($servings, $slots, $di);
        $meals = []; $today = [];
        foreach ($slots as $si => $slot) {
            $items = self::fillMeal($alloc[$slot], $slot, $foods, $di * 1000 + $si * 100 + $attempt * 7919, array_merge($prevIds, $today));
            foreach ($items as $it) $today[] = $it['food_id'];
            $meals[] = ['slot' => $slot, 'name' => self::SLOT_NAMES[$slot], 'needs' => $alloc[$slot], 'items' => $items];
        }
        return ['day' => $di + 1, 'meals' => self::balanceDay(self::ensureWholeGrain($meals, $foods), $foods, $target)];
    }

    /**
     * Different foods in the same group carry different energy (a cashew serving is not a pumpkin seed serving), so days can drift.
     * Swap foods inside their group, never changing the servings, until the day is within about 8% of the target.
     */
    private static function balanceDay(array $meals, Collection $foods, int $target): array
    {
        $kcal = fn () => array_sum(array_map(fn ($m) => array_sum(array_column($m['items'], 'kcal')), $meals));
        for ($i = 0; $i < 8; $i++) {
            $now = $kcal();
            if (abs($now - $target) <= $target * 0.08) break;
            $up = $now < $target;                                        // need more energy, or less?
            $best = null; $bestGain = 0;
            foreach ($meals as $mi => $m) {
                $type = str_starts_with($m['slot'], 'snack') ? 'snack' : ($m['slot'] === 'breakfast' ? 'breakfast' : 'main');
                foreach ($m['items'] as $ii => $it) {
                    if (in_array($it['grp'], ['veg', 'cereal'], true)) continue;     // vegetables and rice stay as the guideline sets them
                    $cur = $foods->firstWhere('id', $it['food_id']);
                    if (! $cur || $cur->getAttribute('liked')) continue;
                    $used = array_column($m['items'], 'food_id');
                    foreach ($foods->where('grp', $it['grp']) as $alt) {
                        if (in_array($alt->id, $used, true) || ! in_array($type, $alt->mealTagList(), true) || ($it['sub'] && $alt->sub !== $it['sub'] && $it['grp'] === 'protein')) continue;
                        $delta = ($alt->kcal_serving - $cur->kcal_serving) * $it['servings'];
                        $gain = $up ? $delta : -$delta;
                        if ($gain > $bestGain && $gain <= abs($target - $now) * 1.3) { $bestGain = $gain; $best = [$mi, $ii, $alt, $it['servings']]; }
                    }
                }
            }
            if (! $best) break;
            [$mi, $ii, $alt, $n] = $best;
            $meals[$mi]['items'][$ii] = self::item($alt, $n);
        }
        return $meals;
    }

    /** Build the whole week. */
    public static function generate(array $in): array
    {
        $sum = self::summary($in);
        if ($sum['blocked']) return ['blocked' => true, 'issues' => $sum['issues']];
        $foods = self::eligible($in);
        $slots = self::slots((int) ($in['meals'] ?? 3));
        $days = []; $prev = [];
        for ($d = 0; $d < 7; $d++) {
            $day = self::buildDay($sum['servings'], $slots, $foods, $d, 0, $prev, $sum['targets']['kcal']);
            $prev = collect($day['meals'])->flatMap(fn ($m) => array_column($m['items'], 'food_id'))->all();
            $days[] = $day;
        }
        return self::finish(['blocked' => false, 'targets' => $sum['targets'], 'servings' => $sum['servings'], 'est' => $sum['est'], 'goal' => $in['goal'] ?? 'maintain', 'pattern' => $in['pattern'] ?? 'everything',
            'meals_per_day' => count($slots), 'notes' => $sum['notes'], 'warnings' => $sum['warnings'], 'days' => $days]);
    }

    /** Totals for every meal and day, and the week's shopping list. Called after building and after any swap. */
    public static function finish(array $plan): array
    {
        $shop = []; $kc = $pr = 0;
        foreach ($plan['days'] as &$day) {
            $dk = 0; $dp = 0.0; $groups = array_fill_keys(self::GROUPS, 0);
            foreach ($day['meals'] as &$meal) {
                $meal['kcal'] = (int) array_sum(array_column($meal['items'], 'kcal'));
                $meal['protein'] = round(array_sum(array_column($meal['items'], 'protein')), 1);
                $dk += $meal['kcal']; $dp += $meal['protein'];
                foreach ($meal['items'] as $it) {
                    $groups[$it['grp']] += $it['servings'];
                    $shop[$it['food_id']] ??= ['food_id' => $it['food_id'], 'name' => $it['name'], 'local' => $it['local'] ?? null, 'grp' => $it['grp'], 'servings' => 0,
                        'per' => $it['per'], 'unit' => $it['unit'], 'unit_plural' => $it['unit_plural'], 'g' => $it['g']];
                    $shop[$it['food_id']]['servings'] += $it['servings'];
                }
            }
            unset($meal);
            $day['kcal'] = $dk; $day['protein'] = (int) round($dp); $day['groups'] = $groups;
            $kc += $dk; $pr += $dp;
        }
        unset($day);
        $n = max(1, count($plan['days']));
        $plan['avg_kcal'] = (int) round($kc / $n); $plan['avg_protein'] = (int) round($pr / $n);
        $order = array_flip(self::GROUPS);
        $plan['shopping'] = array_values(collect($shop)->map(fn ($s) => $s + [
            'qty' => Food::measure($s['per'], $s['unit'], $s['unit_plural'], $s['servings']), 'grams' => $s['g'] ? (int) round($s['g'] * $s['servings']) : null,
        ])->sortBy([fn ($a, $b) => ($order[$a['grp']] ?? 99) <=> ($order[$b['grp']] ?? 99), fn ($a, $b) => strcmp($a['name'], $b['name'])])->all());
        return $plan;
    }

    /* ============================================================== changes after the plan is made */

    /** Re-roll one meal with different foods (same servings). */
    public static function swapMeal(array $in, array $plan, int $di, int $mi): array
    {
        $foods = self::eligible($in);
        $meal = $plan['days'][$di]['meals'][$mi];
        $attempt = (int) ($meal['swaps'] ?? 0) + 1;
        $items = self::fillMeal($meal['needs'], $meal['slot'], $foods, $di * 1000 + $mi * 100 + $attempt * 7919 + 3, array_column($meal['items'], 'food_id'));
        if ($items) {
            $meal['items'] = $items; $meal['swaps'] = $attempt; unset($meal['edited']);
            $plan['days'][$di]['meals'][$mi] = $meal;
        }
        return self::finish($plan);
    }

    public static function replanDay(array $in, array $plan, int $di): array
    {
        foreach (array_keys($plan['days'][$di]['meals']) as $mi) $plan = self::swapMeal($in, $plan, $di, $mi);
        return $plan;
    }

    /** Other foods this person could eat instead of one item: the same group (and the same kind of vegetable). */
    public static function alternatives(array $in, array $item): Collection
    {
        $pool = self::eligible($in)->where('grp', $item['grp'])->reject(fn ($f) => $f->id === $item['food_id']);
        if ($item['grp'] === 'veg' && $pool->where('sub', $item['sub'])->isNotEmpty()) $pool = $pool->where('sub', $item['sub']);
        return $pool->sortBy('name')->values();
    }

    /** Replace one food with another from the same group. The servings stay the same. */
    public static function swapItem(array $in, array $plan, int $di, int $mi, int $ii, int $foodId): array
    {
        $item = $plan['days'][$di]['meals'][$mi]['items'][$ii] ?? abort(404);
        $food = self::eligible($in)->firstWhere('id', $foodId);
        abort_unless($food && $food->grp === $item['grp'], 404);
        $plan['days'][$di]['meals'][$mi]['items'][$ii] = self::item($food, $item['servings']);
        $plan['days'][$di]['meals'][$mi]['edited'] = true;
        return self::finish($plan);
    }

    /** Eat more or less of one food: the same food, a different number of servings (1 to 8). */
    public static function resizeItem(array $plan, int $di, int $mi, int $ii, int $servings): array
    {
        $it = $plan['days'][$di]['meals'][$mi]['items'][$ii] ?? abort(404);
        $food = Food::find($it['food_id']) ?? abort(404);
        $plan['days'][$di]['meals'][$mi]['items'][$ii] = self::item($food, max(1, min(8, $servings)));
        $plan['days'][$di]['meals'][$mi]['edited'] = true;
        return self::finish($plan);
    }
}
