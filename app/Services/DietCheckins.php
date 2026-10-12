<?php

namespace App\Services;

use App\Models\{BodyLog, DietCheckin, DietPlan, Member};
use Carbon\Carbon;

/**
 * The weekly check-in: the member logs their weight and how the week went, and the calorie target moves a small, safe step
 * if their weight is not changing at the pace their goal needs. The owner is flagged only when something looks wrong.
 */
class DietCheckins
{
    /** Weight change per week the goal should give, in kg (negative = losing). */
    public static function expectedRate(array $in): float
    {
        $steady = ($in['pace'] ?? 'gentle') === 'steady';
        return match ($in['achievement'] ?? ($in['goal'] ?? 'maintain')) {
            'lose' => $steady ? -0.5 : -0.25, 'tone' => -0.25, 'muscle' => 0.25, 'gain' => $steady ? 0.4 : 0.25, default => 0.0,
        };
    }

    /** The date the next check-in is due: a week after the last one (or after the plan was made). */
    public static function dueOn(DietPlan $plan): Carbon
    {
        $last = DietCheckin::where('diet_plan_id', $plan->id)->latest('week_of')->first();
        return ($last ? $last->week_of->copy() : $plan->created_at->copy()->startOfDay())->addDays(7);
    }

    public static function isDue(DietPlan $plan): bool { return self::dueOn($plan)->lte(today()); }

    /**
     * Work out what the check-in means and what the calories should do. Does not save anything.
     * @return array{action: string, kcal: int, message: string, rate: float, expected: float}
     */
    public static function decide(DietPlan $plan, float $weight, string $followed, string $hunger, string $energy): array
    {
        $in = $plan->inputs; $kcal = (int) $plan->plan['targets']['kcal'];
        $last = DietCheckin::where('diet_plan_id', $plan->id)->latest('week_of')->first();
        $prev = $last ? $last->weight_kg : (float) $in['weight'];
        $since = $last ? $last->week_of->copy() : $plan->created_at->copy()->startOfDay();
        $weeks = max(1.0, $since->diffInDays(today()) / 7);
        $rate = round(($weight - $prev) / $weeks, 2);                      // kg a week
        $expected = self::expectedRate($in);
        $limits = DietEngine::targetLimits($in);
        $say = fn (string $a, int $k, string $m) => ['action' => $a, 'kcal' => $k, 'message' => $m, 'rate' => $rate, 'expected' => $expected];

        if ($followed === 'little') return $say('hold', $kcal, 'Your calories stay the same. Try following the plan for a full week first, then we will see what your weight does.');

        $fast = abs($rate) > max(0.9, 0.012 * $weight);                    // far faster than is wise either way
        $lo = $expected < 0 ? $expected : 0; $step = 0;
        if ($expected < 0) {                                              // losing
            if ($rate > $expected * 0.5 && $followed === 'most') $step = -100;           // losing less than half as fast as planned
            elseif ($rate < $expected * 1.8 || ($rate < 0 && $fast)) $step = 150;          // losing much faster than planned
        } elseif ($expected > 0) {                                        // gaining
            if ($rate < $expected * 0.5 && $followed === 'most') $step = 150;
            elseif ($rate > $expected * 1.8) $step = -100;
        } else {                                                          // staying the same
            if ($rate > 0.4) $step = -100; elseif ($rate < -0.4) $step = 100;
        }
        if ($hunger === 'high' && $step < 0) $step = 0;                    // do not cut calories on someone who is very hungry
        $new = max($limits['kcal'][0], min($limits['kcal'][1], $kcal + $step));
        $pace = ($rate > 0 ? '+' : '').number_format($rate, 2).' kg a week';
        if ($step !== 0 && $new === $kcal) return $say('hold', $kcal, "Your weight moved $pace, but your calories are already at the edge of the safe range. Talk to your trainer.");
        if ($step === 0) {
            $m = "You are on track: your weight moved $pace. Your calories stay the same.";
            if ($hunger === 'high') $m .= ' You felt hungry, so add water, vegetables and pulses to your meals.';
            if ($energy === 'low') $m .= ' Low energy can come from poor sleep or too little food, so tell your trainer if it continues.';
            return $say('kept', $kcal, $m);
        }
        $verb = $new > $kcal ? 'raised' : 'lowered';
        return $say($verb, $new, "Your weight moved $pace, and your goal needs about ".number_format($expected, 2)." kg a week. Calories ".$verb.' from '.number_format($kcal).' to '.number_format($new).' for the coming week.');
    }

    /** Save a check-in, and if the calories changed, rebuild the plan with the new target. Returns the saved check-in. */
    public static function record(Member $m, DietPlan $plan, array $d): DietCheckin
    {
        $r = self::decide($plan, (float) $d['weight'], $d['followed'], $d['hunger'], $d['energy']);
        $checkin = DietCheckin::create(['member_id' => $m->id, 'diet_plan_id' => $plan->id, 'week_of' => today(), 'weight_kg' => $d['weight'], 'followed' => $d['followed'], 'hunger' => $d['hunger'],
            'energy' => $d['energy'], 'note' => $d['note'] ?? null, 'kcal_before' => $plan->plan['targets']['kcal'], 'kcal_after' => $r['kcal'], 'action' => $r['action'], 'message' => $r['message']]);
        BodyLog::updateOrCreate(['member_id' => $m->id, 'logged_on' => today()], ['weight_kg' => $d['weight']]);
        if (in_array($r['action'], ['raised', 'lowered'], true)) {
            $in = $plan->inputs;
            $in['override'] = array_merge($in['override'] ?? [], ['kcal' => $r['kcal']]);
            $in['weight'] = $d['weight'];
            $new = DishPlanner::generate($in);
            if (empty($new['blocked'])) {
                foreach (['water', 'tips', 'schedule', 'workout'] as $k) if (isset($plan->plan[$k])) $new[$k] = $plan->plan[$k];
                $plan->update(['inputs' => $in, 'plan' => $new]);
            }
        }
        return $checkin;
    }

    /**
     * What the owner should look at for a member's meal plan. Empty when all is well.
     * @return array<int, array{kind: string, text: string}>
     */
    public static function flags(Member $m): array
    {
        $plan = $m->dietPlans()->where('active', true)->where('source', 'app')->first();
        if (! $plan || ($plan->plan['format'] ?? '') !== 'dishes') return [];
        $flags = [];
        $cs = DietCheckin::where('diet_plan_id', $plan->id)->latest('week_of')->take(3)->get();
        if (self::dueOn($plan)->lt(today()->subDays(3))) $flags[] = ['kind' => 'overdue', 'text' => 'Check-in is '.self::dueOn($plan)->diffInDays(today()).' days late'];
        if ($cs->take(2)->count() === 2 && $cs->take(2)->every(fn ($c) => $c->followed === 'little')) $flags[] = ['kind' => 'notfollowing', 'text' => 'Not following the plan for 2 weeks'];
        if ($cs->count() >= 2 && $cs->take(2)->every(fn ($c) => $c->action === 'lowered' || $c->action === 'raised')) $flags[] = ['kind' => 'stalled', 'text' => 'Calories changed 2 weeks running and weight is not on pace'];
        if ($cs->take(2)->count() === 2 && $cs->take(2)->every(fn ($c) => $c->energy === 'low')) $flags[] = ['kind' => 'energy', 'text' => 'Low energy 2 weeks running'];
        if ($cs->count() >= 2) {                                          // newest first: how much weight was lost over the latest week
            $loss = $cs[1]->weight_kg - $cs[0]->weight_kg;
            if ($loss > 0.012 * $cs[0]->weight_kg) $flags[] = ['kind' => 'fast', 'text' => 'Losing weight very fast ('.number_format($loss, 1).' kg last week)'];
        }
        return $flags;
    }
}
