<?php

namespace Tests\Feature;

use App\Models\{DietPlan, Food, Member, User, WorkoutPlan};
use App\Services\DietEngine;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlanTest extends TestCase
{
    use RefreshDatabase;

    private array $in = ['sex' => 'male', 'age' => 30, 'height' => 175, 'weight' => 78, 'achievement' => 'lose', 'goal' => 'lose', 'pace' => 'gentle', 'current_body' => 'average',
        'body_goal' => 'toned', 'metabolism' => 'average', 'best_shape' => 'few_years', 'causes' => [], 'job' => 'sitting', 'train_days' => 3, 'train_minutes' => 45, 'meals' => 4,
        'pattern' => 'everything', 'allergies' => [], 'usuals' => ['breakfast' => [], 'lunch' => [], 'dinner' => []]];

    protected function setUp(): void { parent::setUp(); $this->seed(DummySeeder::class); }

    private function answers(array $over = []): array
    {
        $a = $this->in + ['no_beef' => 0, 'no_pork' => 0, 'pregnant' => 0, 'medical' => 0, 'eating_disorder' => 0, 'understand' => 1];
        unset($a['usuals'], $a['goal']);
        return array_merge($a, $over);
    }

    private function member(string $email): User { return User::where('email', $email)->first(); }

    public function test_plan_hits_the_calorie_target_and_has_every_meal_slot(): void
    {
        $plan = DietEngine::generate($this->in);
        $this->assertFalse($plan['blocked']);
        $this->assertCount(7, $plan['days']);
        $this->assertEqualsWithDelta($plan['targets']['kcal'], $plan['avg_kcal'], $plan['targets']['kcal'] * 0.12);
        $this->assertCount(4, $plan['days'][0]['meals']);
        $this->assertNotEmpty($plan['shopping']);
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
        $plan = DietEngine::generate(['pattern' => 'vegan', 'allergies' => ['soy', 'nuts']] + $this->in);
        foreach ($plan['days'] as $day) foreach ($day['meals'] as $meal) foreach ($meal['items'] as $it) {
            $f = Food::find($it['food_id']);
            $this->assertSame('vegan', $f->diet, $f->name);
            $this->assertEmpty(array_intersect($f->allergenList(), ['soy', 'nuts']), $f->name);
        }
    }

    public function test_usual_foods_are_kept_in_the_plan(): void
    {
        $oats = Food::where('name', 'like', 'Oats%')->first();
        $plan = DietEngine::generate(['usuals' => ['breakfast' => [$oats->id], 'lunch' => [], 'dinner' => []]] + $this->in);
        $n = 0;
        foreach ($plan['days'] as $day) foreach ($day['meals'] as $meal) if ($meal['slot'] === 'breakfast') foreach ($meal['items'] as $it) $n += $it['food_id'] === $oats->id;
        $this->assertGreaterThanOrEqual(4, $n, 'a food the member usually eats appears most mornings');
    }

    public function test_unsafe_answers_get_no_plan(): void
    {
        foreach ([['medical' => true], ['pregnant' => true], ['eating_disorder' => true], ['age' => 16]] as $bad) {
            $this->assertTrue(DietEngine::generate($bad + $this->in)['blocked']);
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

    public function test_member_with_a_workout_plan_takes_the_survey_and_gets_a_plan_built_around_it(): void
    {
        $u = $this->member('kasun@example.test');
        $this->assertNotNull($u->member->approvedPlan());
        $this->actingAs($u)->get(route('member.diet.create'))->assertOk()->assertSee('About you')->assertSee('What do you usually have for breakfast?');
        $this->post(route('member.diet.generate'), $this->answers())->assertRedirect(route('member.diet'));
        $plan = $u->member->dietPlans()->where('active', true)->first();
        $this->assertSame('app', $plan->source);
        $this->assertSame($u->member->approvedPlan()->name, $plan->plan['workout']['name']);
        $this->assertSame($plan->plan['workout']['days'], $plan->inputs['train_days'], 'workout days come from the workout plan, not the survey');
        $this->assertNotEmpty($plan->plan['water']);
        $this->get(route('member.diet'))->assertOk()->assertSee('Main course')->assertSee('Dishes')->assertSee('Dessert')->assertSee('Water')->assertSee('Built around your workout plan');
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
            ->assertOk()->assertJsonPath('blocked', false)->assertJsonStructure(['targets' => ['kcal', 'protein', 'tdee'], 'servings', 'water' => ['litres']]);
    }

    public function test_changing_a_food_and_a_portion_updates_the_day(): void
    {
        $u = $this->member('kasun@example.test');
        $this->actingAs($u)->post(route('member.diet.generate'), $this->answers());
        $dp = $u->member->dietPlans()->where('active', true)->first();
        $it = $dp->plan['days'][0]['meals'][0]['items'][0];
        $before = $dp->plan['days'][0]['kcal'];
        $this->post(route('member.diet.item'), ['day' => 0, 'meal' => 0, 'item' => 0, 'food_id' => $it['food_id'], 'servings' => $it['servings'] + 2])->assertRedirect();
        $after = $dp->fresh()->plan;
        $this->assertSame($it['servings'] + 2, $after['days'][0]['meals'][0]['items'][0]['servings']);
        $this->assertGreaterThan($before, $after['days'][0]['kcal']);
        $this->assertNotEmpty($after['water'], 'extras survive an edit');
        $this->get(route('member.diet.shopping'))->assertOk()->assertSee('Shopping list');
    }

    public function test_owner_can_manage_foods_and_switch_one_off(): void
    {
        $owner = User::where('role', 'admin')->first();
        $f = Food::first();
        $this->actingAs($owner)->get(route('admin.foods.index'))->assertOk()->assertSee($f->name);
        $this->put(route('admin.foods.update', $f), ['toggle' => 1])->assertRedirect();
        $this->assertFalse($f->fresh()->active);
        $this->assertTrue(DietEngine::eligible($this->in)->doesntContain('id', $f->id));
    }
}
