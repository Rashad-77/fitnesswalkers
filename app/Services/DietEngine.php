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
 *
 *   The week of dishes is built from these numbers by DishPlanner.
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

        // Overrides: the member can adjust within safe limits, and the owner can set their own targets (see targetLimits).
        $ov = $in['override'] ?? null;
        if (is_array($ov)) {
            if (isset($ov['kcal'])) $kcal = max($floor, (int) round((float) $ov['kcal']));
            if (isset($ov['protein'])) $protein = (int) round((float) $ov['protein']);
        }
        // Fat is about 27% of calories (20 to 35% is healthy), carbs are what is left.
        $fatPct = is_array($ov) && isset($ov['fat_pct']) ? max(15, min(45, (float) $ov['fat_pct'])) : 27.0;
        $fat = (int) round($kcal * $fatPct / 100 / 9);
        $carbs = (int) round(($kcal - 4 * $protein - 9 * $fat) / 4);
        if ($carbs < 100) { $carbs = 100; $fat = max(0, (int) round(($kcal - 4 * $protein - 400) / 9)); }   // never plan under 100 g of carbs

        return ['bmr' => (int) round($bmr), 'tdee' => (int) round($tdee), 'pal' => round($tdee / max(1, $bmr), 2), 'kcal' => (int) $kcal,
            'protein' => (int) $protein, 'carbs' => $carbs, 'fat' => $fat, 'fat_pct' => round($fat * 9 / max(1, $kcal) * 100),
            'per_kg' => $perKg, 'floor_applied' => $kcal === $floor && ($tdee + $adjust) < $floor, 'floor' => $floor, 'overridden' => is_array($ov)];
    }

    /**
     * How far a person can move their own targets. Members get a safe band; the owner gets a wider one ($owner = true).
     * @return array{kcal: array{0:int,1:int}, protein: array{0:int,1:int}, fat_pct: array{0:int,1:int}, carbs_min: int, base: array}
     */
    public static function targetLimits(array $in, bool $owner = false): array
    {
        $base = self::targets(array_diff_key($in, ['override' => 1]));                      // what the survey alone would give
        $w = (float) $in['weight']; $floor = $base['floor'];
        $lo = $owner ? $floor : max($floor, (int) (round($base['tdee'] * 0.75 / 10) * 10));
        $hi = (int) (round(($owner ? $base['tdee'] * 1.5 : max($base['tdee'] * 1.2, $base['tdee'] + 500)) / 10) * 10);
        return ['kcal' => [$lo, $hi], 'protein' => [(int) round($w * ($owner ? 0.6 : 0.8)), (int) min($owner ? 220 : 200, round($w * ($owner ? 2.6 : 2.2)))],
            'fat_pct' => $owner ? [15, 45] : [20, 35], 'carbs_min' => 100, 'base' => $base];
    }

    /* ============================================================== 3 + 4. servings per group */

    /** Foods this person can be given: on sale, right eating pattern, no allergens, nothing they avoid. */
    public static function eligible(array $in): Collection
    {
        $diets = match ($in['pattern'] ?? 'everything') {
            'vegan' => ['vegan'], 'lacto' => ['vegan', 'dairy'], 'ovo' => ['vegan', 'egg'], 'ovolacto' => ['vegan', 'dairy', 'egg'],
            default => ['vegan', 'dairy', 'egg', 'meat'],
        };
        $allergies = (array) ($in['allergies'] ?? []);
        $tags = array_values(array_filter([! empty($in['no_beef']) ? 'beef' : null, ! empty($in['no_pork']) ? 'pork' : null]));
        return Food::where('active', true)->whereIn('diet', $diets)->get()
            ->filter(fn ($f) => ! array_intersect($f->allergenList(), $allergies) && ! array_intersect($f->tagList(), $tags))
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
}
