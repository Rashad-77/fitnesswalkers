<?php

namespace Tests\Feature;

use App\Models\{DietPlan, Food, Member, User};
use App\Services\DietGenerator;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DietTest extends TestCase
{
    use RefreshDatabase;

    private array $in = ['goal' => 'lose', 'age' => 28, 'sex' => 'male', 'height' => 175, 'weight' => 80, 'activity' => 'moderate',
        'diet_type' => 'non_veg', 'allergies' => [], 'meals' => 3, 'budget' => 1500];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DummySeeder::class);
    }

    public function test_safety_gate_blocks_risky_people(): void
    {
        foreach ([['age' => 16], ['pregnant' => true], ['medical' => true], ['weight' => 50, 'height' => 175]] as $change) {
            $plan = DietGenerator::generate($change + $this->in);
            $this->assertTrue($plan['blocked'], json_encode($change));
            $this->assertNotEmpty($plan['issues']);
        }
        $this->assertFalse(DietGenerator::generate($this->in)['blocked']);
    }

    public function test_calorie_floor_is_never_breached(): void
    {
        $in = ['goal' => 'lose', 'age' => 60, 'sex' => 'female', 'height' => 152, 'weight' => 48.6, 'activity' => 'sedentary'] + $this->in;
        $this->assertGreaterThan(18.5, \App\Services\Bmi::calc(48.6, 152));
        $t = DietGenerator::targets($in);
        $this->assertGreaterThanOrEqual(1200, $t['kcal']);
        $this->assertTrue($t['floor_applied']);
        $m = ['goal' => 'lose', 'sex' => 'male', 'age' => 70, 'height' => 160, 'weight' => 50, 'activity' => 'sedentary'] + $this->in;
        $this->assertGreaterThanOrEqual(1500, DietGenerator::targets($m)['kcal']);
    }

    public function test_plan_shape_totals_and_cost_add_up(): void
    {
        $plan = DietGenerator::generate($this->in);
        $this->assertCount(7, $plan['days']);
        $week = 0;
        foreach ($plan['days'] as $day) {
            $this->assertCount(3, $day['meals']);
            $this->assertEqualsWithDelta(array_sum(array_column($day['meals'], 'kcal')), $day['kcal'], 0.5);
            $this->assertEqualsWithDelta(array_sum(array_column($day['meals'], 'cost')), $day['cost'], 0.01);
            $this->assertEqualsWithDelta($plan['targets']['kcal'], $day['kcal'], $plan['targets']['kcal'] * 0.15, 'calories should be within 15% of target');
            $week += $day['cost'];
        }
        $this->assertEqualsWithDelta($week, $plan['week_cost'], 0.05);
        $this->assertEqualsWithDelta($week, array_sum(array_column($plan['shopping'], 'cost')), 0.1);
        // cost uses the food's own price
        $it = $plan['days'][0]['meals'][0]['items'][0];
        $this->assertEqualsWithDelta(Food::find($it['food_id'])->price_per_kg * $it['grams'] / 1000, $it['cost'], 0.01);
    }

    public function test_allergens_and_diet_type_are_respected(): void
    {
        $plan = DietGenerator::generate(['diet_type' => 'vegan', 'allergies' => ['soy', 'nuts', 'gluten']] + $this->in);
        $ids = collect($plan['days'])->flatMap(fn ($d) => collect($d['meals'])->flatMap(fn ($m) => array_column($m['items'], 'food_id')))->unique();
        $foods = Food::whereIn('id', $ids)->get();
        $this->assertNotEmpty($foods);
        foreach ($foods as $f) {
            $this->assertSame('vegan', $f->diet_type, $f->name);
            $this->assertEmpty(array_intersect($f->allergenList(), ['soy', 'nuts', 'gluten']), $f->name);
        }
        $veg = DietGenerator::generate(['diet_type' => 'vegetarian', 'allergies' => ['egg', 'dairy']] + $this->in);
        $vf = Food::whereIn('id', collect($veg['days'])->flatMap(fn ($d) => collect($d['meals'])->flatMap(fn ($m) => array_column($m['items'], 'food_id'))))->get();
        foreach ($vf as $f) { $this->assertNotSame('non_veg', $f->diet_type); $this->assertEmpty(array_intersect($f->allergenList(), ['egg', 'dairy'])); }
    }

    public function test_too_small_a_budget_is_flagged_honestly(): void
    {
        $plan = DietGenerator::generate(['budget' => 100] + $this->in);
        $this->assertStringContainsString('above your budget', implode(' ', $plan['warnings']));
        $ok = DietGenerator::generate(['budget' => 5000] + $this->in);
        $this->assertStringNotContainsString('above your budget', implode(' ', $ok['warnings']));
    }

    public function test_price_changes_change_the_cost(): void
    {
        $before = DietGenerator::generate($this->in)['week_cost'];
        Food::query()->update(['price_per_kg' => \DB::raw('price_per_kg * 2')]);
        $this->assertGreaterThan($before * 1.5, DietGenerator::generate(['budget' => 99999] + $this->in)['week_cost']);
    }

    public function test_member_generates_and_swaps_and_blocked_member_gets_no_plan(): void
    {
        $member = Member::find(5);
        $form = ['goal' => 'maintain', 'age' => 30, 'sex' => 'male', 'height' => 172, 'weight' => 70, 'activity' => 'light', 'diet_type' => 'non_veg', 'meals' => 4, 'budget' => 1500];
        $this->actingAs($member->user)->post(route('member.diet.generate'), $form)->assertRedirect(route('member.diet'));
        $dp = $member->dietPlans()->where('active', true)->first();
        $this->assertSame('app', $dp->source);
        $this->get(route('member.diet'))->assertOk()->assertSee('Swap this meal')->assertSee('Rs.');
        $this->get(route('member.diet.shopping'))->assertOk();

        $before = json_encode($dp->plan['days'][0]['meals'][1]['items']);
        $this->post(route('member.diet.swap'), ['day' => 0, 'meal' => 1])->assertRedirect();
        $after = $dp->fresh()->plan;
        $this->assertNotSame($before, json_encode($after['days'][0]['meals'][1]['items']));
        $this->assertEquals(1, $after['days'][0]['meals'][1]['swaps']);
        $this->assertEqualsWithDelta($after['week_cost'], array_sum(array_column($after['days'], 'cost')), 0.05);

        // under 18: refused, nothing saved over the old plan
        $count = DietPlan::where('member_id', $member->id)->count();
        $this->post(route('member.diet.generate'), ['age' => 15] + $form)->assertRedirect(route('member.diet'));
        $this->assertSame($count, DietPlan::where('member_id', $member->id)->count());
        $this->get(route('member.diet'))->assertSee('under 18');
    }

    public function test_owner_plan_overrides_app_plan_and_member_sees_it(): void
    {
        $member = Member::find(3);                                     // has an app plan
        $owner = User::where('role', 'admin')->first();
        $this->actingAs($owner)->post(route('admin.diets.store'), ['member_id' => $member->id, 'title' => 'Cutting plan', 'owner_text' => '5 eggs and rice'])->assertRedirect();
        $this->assertSame(1, $member->dietPlans()->where('active', true)->count());
        $this->actingAs($member->user)->get(route('member.diet'))->assertSee('5 eggs and rice')->assertSee('from the owner');
        $this->get(route('member.diet.shopping'))->assertNotFound();
    }
}
