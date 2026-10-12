<?php

namespace Tests\Feature;

use App\Models\{DietCheckin, DietPlan, Dish, Food, Member, User, WorkoutPlan};
use App\Services\{DietCheckins, DietEngine, DishPlanner};
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    private array $in = ['sex' => 'male', 'age' => 30, 'height' => 175, 'weight' => 78, 'achievement' => 'lose', 'goal' => 'lose', 'pace' => 'gentle', 'current_body' => 'average',
        'body_goal' => 'toned', 'metabolism' => 'average', 'best_shape' => 'few_years', 'causes' => [], 'job' => 'sitting', 'train_days' => 3, 'train_minutes' => 45, 'meals' => 4,
        'pattern' => 'everything', 'allergies' => []];

    protected function setUp(): void { parent::setUp(); $this->seed(DummySeeder::class); }

    private function answers(array $over = []): array
    {
        $a = $this->in + ['no_beef' => 0, 'no_pork' => 0, 'pregnant' => 0, 'medical' => 0, 'eating_disorder' => 0, 'understand' => 1];
        unset($a['goal']);
        return array_merge($a, $over);
    }

    private function member(string $email): User { return User::where('email', $email)->first(); }

    private function plan(array $over = []): DietPlan
    {
        $u = $this->member('kasun@example.test');
        $this->actingAs($u)->post(route('member.diet.generate'), $this->answers($over))->assertRedirect(route('member.diet'));
        return $u->member->dietPlans()->where('active', true)->first();
    }

    public function test_week_of_dishes_lands_on_the_targets_and_varies(): void
    {
        $plan = DishPlanner::generate($this->in);
        $this->assertFalse($plan['blocked']);
        $this->assertSame('dishes', $plan['format']);
        $this->assertCount(7, $plan['days']);
        $this->assertCount(4, $plan['days'][0]['meals']);
        $t = $plan['targets'];
        foreach ($plan['days'] as $d) {
            $this->assertEqualsWithDelta($t['kcal'], $d['kcal'], $t['kcal'] * 0.1);
            $this->assertEqualsWithDelta($t['protein'], $d['protein'], $t['protein'] * 0.3);
        }
        $names = collect($plan['days'])->flatMap(fn ($d) => collect($d['meals'])->pluck('dish_id'))->unique();
        $this->assertGreaterThanOrEqual(10, $names->count(), 'the week is not the same few dishes');
        $this->assertNotEmpty($plan['shopping']);
        $this->assertGreaterThan(0, $t['carbs']);
        $this->assertGreaterThan(0, $t['fat']);
    }

    public function test_metabolism_and_body_goal_change_the_numbers(): void
    {
        $avg = DietEngine::targets($this->in);
        $this->assertLessThan($avg['kcal'], DietEngine::targets(['metabolism' => 'slow'] + $this->in)['kcal']);
        $this->assertGreaterThan($avg['kcal'], DietEngine::targets(['metabolism' => 'fast'] + $this->in)['kcal']);
        $this->assertGreaterThan($avg['protein'], DietEngine::targets(['body_goal' => 'muscular'] + $this->in)['protein']);
    }

    public function test_allergies_and_diet_pattern_are_respected(): void
    {
        $plan = DishPlanner::generate(['pattern' => 'vegan', 'allergies' => ['soy', 'nuts']] + $this->in);
        foreach ($plan['days'] as $day) foreach ($day['meals'] as $meal) {
            $dishes = array_filter([$meal['dish_id'], $meal['dessert']['dish_id'] ?? null]);
            foreach (Dish::with('items.food')->whereIn('id', $dishes)->get() as $dish) foreach ($dish->items as $it) {
                $this->assertSame('vegan', $it->food->diet, $dish->name.': '.$it->food->name);
                $this->assertEmpty(array_intersect($it->food->allergenList(), ['soy', 'nuts']), $dish->name);
            }
        }
    }

    public function test_unsafe_answers_get_no_plan(): void
    {
        foreach ([['medical' => true], ['pregnant' => true], ['eating_disorder' => true], ['age' => 16]] as $bad) {
            $this->assertTrue(DishPlanner::generate($bad + $this->in)['blocked']);
        }
    }

    public function test_member_without_a_workout_plan_is_sent_to_the_workout_page_first(): void
    {
        $u = $this->member('dilshan@example.test');
        $this->assertNull($u->member->approvedPlan());
        $this->actingAs($u)->get(route('member.diet'))->assertOk()->assertSee('First, your workout plan');
        $this->get(route('member.diet.create'))->assertRedirect(route('member.diet'));
        $this->post(route('member.diet.generate'), $this->answers())->assertForbidden();
    }

    public function test_survey_has_no_usual_food_questions_and_the_plan_is_built_around_the_workout(): void
    {
        $u = $this->member('kasun@example.test');
        $this->assertNotNull($u->member->approvedPlan());
        $this->actingAs($u)->get(route('member.diet.create'))->assertOk()->assertSee('About you')->assertDontSee('usually have for');
        $plan = $this->plan();
        $this->assertSame('app', $plan->source);
        $this->assertSame($u->member->approvedPlan()->name, $plan->plan['workout']['name']);
        $this->assertSame($plan->plan['workout']['days'], $plan->inputs['train_days'], 'workout days come from the workout plan, not the survey');
        $this->assertNotEmpty($plan->plan['water']);
        $this->get(route('member.diet'))->assertOk()->assertSee('Main course')->assertSee('Water')->assertSee('Built around your workout plan')->assertSee('Weekly check-in');
    }

    public function test_survey_needs_the_tick_box_and_unsafe_answers_are_refused(): void
    {
        $u = $this->member('kasun@example.test');
        $this->actingAs($u)->post(route('member.diet.generate'), $this->answers(['understand' => 0]))->assertSessionHasErrors('understand');
        $this->post(route('member.diet.generate'), $this->answers(['causes' => ['medication']]))->assertRedirect(route('member.diet.create'))->assertSessionHas('diet_issues');
        $this->assertSame(1, $u->member->dietPlans()->where('active', true)->count(), 'the sample plan is untouched');
    }

    public function test_preview_returns_numbers_for_the_last_screen(): void
    {
        $this->actingAs($this->member('kasun@example.test'))->postJson(route('member.diet.preview'), $this->answers())
            ->assertOk()->assertJsonPath('blocked', false)->assertJsonStructure(['targets' => ['kcal', 'protein', 'carbs', 'fat', 'tdee'], 'water' => ['litres']]);
    }

    public function test_changing_a_dish_and_a_portion_updates_the_day_and_keeps_extras(): void
    {
        $dp = $this->plan();
        $before = $dp->plan['days'][0]['kcal'];
        $m0 = $dp->plan['days'][0]['meals'][0];
        $this->post(route('member.diet.dish'), ['day' => 0, 'meal' => 0, 'scale' => 2.5])->assertRedirect();
        $after = $dp->fresh()->plan;
        $this->assertSame(2.5, (float) $after['days'][0]['meals'][0]['scale']);
        $this->assertNotSame($before, $after['days'][0]['kcal']);
        $this->assertNotEmpty($after['water'], 'extras survive an edit');
        $other = Dish::where('kind', 'meal')->where('slots', 'like', '%breakfast%')->where('id', '!=', $m0['dish_id'])->first();
        $this->post(route('member.diet.dish'), ['day' => 0, 'meal' => 0, 'dish_id' => $other->id])->assertRedirect();
        $this->assertSame($other->id, $dp->fresh()->plan['days'][0]['meals'][0]['dish_id']);
        $this->post(route('member.diet.reroll'), ['day' => 1, 'meal' => 1])->assertRedirect();
        $this->get(route('member.diet.shopping'))->assertOk()->assertSee('Shopping list');
    }

    public function test_options_tell_how_each_dish_fits_the_day(): void
    {
        $in = $this->plan()->inputs;
        $opts = DishPlanner::options($in, 'lunch', 'meal');
        $this->assertNotEmpty($opts);
        $fit = DishPlanner::fit(['kcal' => 2240, 'protein' => 125, 'carbs' => 284, 'fat' => 67], ['kcal' => 2240, 'protein' => 125, 'carbs' => 284, 'fat' => 67]);
        $this->assertSame('ok', $fit['all']);
        $off = DishPlanner::fit(['kcal' => 3200, 'protein' => 60, 'carbs' => 284, 'fat' => 67], ['kcal' => 2240, 'protein' => 125, 'carbs' => 284, 'fat' => 67]);
        $this->assertSame('high', $off['kcal']['state']);
        $this->assertSame('low', $off['protein']['state']);
        $this->assertSame('off', $off['all']);
    }

    public function test_member_can_change_targets_inside_the_safe_band_only(): void
    {
        $dp = $this->plan();
        $lim = DietEngine::targetLimits($dp->inputs);
        $this->post(route('member.diet.targets'), ['kcal' => $lim['kcal'][1] + 400, 'protein' => 120, 'fat_pct' => 28])->assertSessionHasErrors('kcal');
        $this->post(route('member.diet.targets'), ['kcal' => 1000, 'protein' => 120, 'fat_pct' => 28])->assertSessionHasErrors('kcal');
        $this->post(route('member.diet.targets'), ['kcal' => 2000, 'protein' => 400, 'fat_pct' => 28])->assertSessionHasErrors('protein');
        $kcal = (int) round(($lim['kcal'][0] + $lim['kcal'][1]) / 2, -1);
        $this->post(route('member.diet.targets'), ['kcal' => $kcal, 'protein' => 120, 'fat_pct' => 30])->assertRedirect(route('member.diet'));
        $this->assertSame($kcal, $dp->fresh()->plan['targets']['kcal']);
        $this->post(route('member.diet.targets.reset'))->assertRedirect();
        $this->assertSame(DietEngine::targets($dp->inputs)['kcal'], $dp->fresh()->plan['targets']['kcal']);
    }

    public function test_owner_can_override_targets_wider_and_the_member_is_locked_out(): void
    {
        $dp = $this->plan();
        $memberLimit = DietEngine::targetLimits($dp->inputs)['kcal'][0];
        $ownerLimit = DietEngine::targetLimits($dp->inputs, true)['kcal'][0];
        $this->assertLessThan($memberLimit, $ownerLimit);
        $mine = $dp->member->user;
        $this->actingAs(User::where('role', 'admin')->first());
        $this->post(route('admin.diets.targets', $dp), ['kcal' => 99999, 'protein' => 120, 'fat_pct' => 30])->assertSessionHasErrors('kcal');
        $this->post(route('admin.diets.targets', $dp), ['kcal' => 2600, 'protein' => 160, 'fat_pct' => 30])->assertRedirect(route('admin.diets.show', $dp));
        $dp = $dp->fresh();
        $this->assertSame(2600, $dp->plan['targets']['kcal']);
        $this->assertSame('owner', $dp->inputs['override_by']);
        $this->get(route('admin.diets.show', $dp))->assertOk()->assertSee('Set this member');
        $this->actingAs($mine)->post(route('member.diet.targets'), ['kcal' => 2200, 'protein' => 120, 'fat_pct' => 30])->assertSessionHas('warn');
        $this->assertSame(2600, $dp->fresh()->plan['targets']['kcal']);
        $this->actingAs(User::where('role', 'admin')->first())->post(route('admin.diets.targets.reset', $dp))->assertRedirect();
        $this->assertArrayNotHasKey('override_by', $dp->fresh()->inputs);
    }

    public function test_weekly_checkin_steps_calories_and_records_the_week(): void
    {
        $dp = $this->plan();
        $kcal = $dp->plan['targets']['kcal'];
        $this->post(route('member.diet.checkin'), ['weight' => 78, 'followed' => 'most', 'hunger' => 'ok', 'energy' => 'ok'])->assertRedirect(route('member.diet'));
        $c = DietCheckin::where('diet_plan_id', $dp->id)->latest('id')->first();
        $this->assertNotNull($c);
        $this->assertSame($kcal, $c->kcal_before);
        $this->assertContains($c->action, ['kept', 'lowered', 'raised', 'hold']);
        $this->post(route('member.diet.checkin'), ['weight' => 5, 'followed' => 'most', 'hunger' => 'ok', 'energy' => 'ok'])->assertSessionHasErrors('weight');
    }

    public function test_checkin_decisions_are_gentle_and_safe(): void
    {
        $dp = $this->plan(); $w = $dp->inputs['weight'];
        $stalled = DietCheckins::decide($dp, $w, 'most', 'ok', 'ok');
        $this->assertLessThanOrEqual(150, abs($stalled['kcal'] - $dp->plan['targets']['kcal']), 'never a big jump');
        $this->assertSame('hold', DietCheckins::decide($dp, $w, 'little', 'ok', 'ok')['action'], 'not following means no change');
        $hungry = DietCheckins::decide($dp, $w, 'most', 'high', 'ok');
        $this->assertGreaterThanOrEqual($dp->plan['targets']['kcal'], $hungry['kcal'], 'no cut on a very hungry member');
    }

    public function test_owner_sees_flags_for_members_who_need_a_look(): void
    {
        $dp = $this->plan();
        foreach ([14, 7] as $ago) DietCheckin::create(['member_id' => $dp->member_id, 'diet_plan_id' => $dp->id, 'week_of' => today()->subDays($ago), 'weight_kg' => 78, 'followed' => 'little', 'hunger' => 'ok', 'energy' => 'low',
            'kcal_before' => 2000, 'kcal_after' => 2000, 'action' => 'hold', 'message' => 'x']);
        $kinds = collect(DietCheckins::flags($dp->member))->pluck('kind');
        $this->assertTrue($kinds->contains('notfollowing'));
        $this->assertTrue($kinds->contains('energy'));
        $owner = User::where('role', 'admin')->first();
        $this->actingAs($owner)->get(route('admin.diets.index'))->assertOk()->assertSee('Not following the plan');
        $this->assertTrue($this->get(route('admin.dashboard'))->assertOk()->viewData('tasks')->contains(fn ($t) => $t['kind'] === 'meal' && str_contains($t['title'], 'Not following')));
    }

    public function test_owner_can_manage_foods_and_switch_one_off(): void
    {
        $owner = User::where('role', 'admin')->first();
        $f = Food::first();
        $this->actingAs($owner)->get(route('admin.foods.index'))->assertOk()->assertSee($f->name);
        $this->put(route('admin.foods.update', $f), ['toggle' => 1])->assertRedirect();
        $this->assertFalse($f->fresh()->active);
        $this->assertTrue(DishPlanner::eligible($this->in)->every(fn ($d) => $d->items->every(fn ($i) => $i->food_id !== $f->id)), 'dishes with a switched-off food are not offered');
    }

    public function test_owner_can_add_edit_and_switch_off_a_dish(): void
    {
        $this->actingAs(User::where('role', 'admin')->first());
        $this->get(route('admin.dishes.index'))->assertOk()->assertSee('Dishes');
        $rice = Food::where('grp', 'cereal')->first(); $veg = Food::where('grp', 'veg')->first();
        $this->post(route('admin.dishes.store'), ['name' => 'Test rice bowl', 'kind' => 'meal', 'slots' => ['lunch'], 'items' => [
            ['food_id' => $rice->id, 'servings' => 2, 'role' => 'main', 'scales' => 1], ['food_id' => $veg->id, 'servings' => 1, 'role' => 'dish']]])->assertRedirect(route('admin.dishes.index'));
        $dish = Dish::where('name', 'Test rice bowl')->first();
        $this->assertCount(2, $dish->items);
        $this->assertTrue($dish->items[0]->scales);
        $this->assertFalse($dish->items[1]->scales);
        $this->get(route('admin.dishes.edit', $dish))->assertOk()->assertSee('Test rice bowl');
        $this->post(route('admin.dishes.store'), ['name' => 'Empty', 'kind' => 'meal', 'slots' => ['lunch']])->assertSessionHasErrors('items');
        $this->put(route('admin.dishes.update', $dish), ['toggle' => 1])->assertRedirect();
        $this->assertFalse($dish->fresh()->active);
        $this->assertTrue(DishPlanner::eligible($this->in)->doesntContain('id', $dish->id));
    }

    public function test_owner_can_add_a_food_and_missing_carbs_and_fat_are_estimated(): void
    {
        $this->actingAs(User::where('role', 'admin')->first())->post(route('admin.foods.store'), [
            'name' => 'Test hopper', 'grp' => 'cereal', 'per_serving' => 1, 'unit' => 'piece', 'kcal_serving' => 120, 'protein_serving' => 3, 'diet' => 'vegan', 'meal_tags' => ['breakfast']])->assertRedirect();
        $f = Food::where('name', 'Test hopper')->first();
        $this->assertGreaterThan(10, (float) $f->carbs_serving);
        $this->assertGreaterThan(0, (float) $f->fat_serving);
    }
}
