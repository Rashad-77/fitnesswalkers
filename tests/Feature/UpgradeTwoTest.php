<?php

namespace Tests\Feature;

use App\Models\{Member, Plan, User};
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pricing content, freeze days, analytics, live search and error pages. */
class UpgradeTwoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DummySeeder::class);
    }

    private function owner(): User { return User::where('role', 'admin')->first(); }

    /* ---------- pricing ---------- */

    public function test_each_plan_shows_its_own_features_and_a_computed_saving(): void
    {
        $res = $this->get(route('pricing'))->assertOk()
            ->assertSee('Everything in Monthly')->assertSee('Freeze your membership once')->assertSee('Most popular')
            ->assertSee('Choose Quarterly');
        $this->assertSame(1, substr_count($res->getContent(), 'Use of the whole gym floor'), 'a feature written for one plan appears once');
        $res->assertSee('Save Rs. 1,500');                         // 3 x Rs. 5,000 - Rs. 13,500
        $this->get(route('home'))->assertOk()->assertSee('Rs. 13,500')->assertDontSee('Use of the whole gym floor');
    }

    public function test_enquiry_link_preselects_the_chosen_package(): void
    {
        $this->get(route('contact', ['plan' => 'quarterly']))->assertOk()
            ->assertSee('Join the Quarterly package')->assertSee('<option selected>Joining: Quarterly package</option>', false);
    }

    public function test_owner_edits_plan_marketing_and_only_one_is_featured(): void
    {
        $annual = Plan::where('name', 'Annual')->first();
        $this->actingAs($this->owner())->put(route('admin.plans.update', $annual), [
            'name' => 'Annual', 'duration_days' => 365, 'price' => 48000, 'active' => 1, 'featured' => 1,
            'tagline' => 'Best for regulars', 'features' => "Everything in Quarterly\nTwo guest passes",
        ])->assertRedirect();
        $this->assertSame(1, Plan::where('featured', true)->count());
        $this->assertTrue($annual->fresh()->featured);
        $this->assertSame(['Everything in Quarterly', 'Two guest passes'], $annual->fresh()->featureList());
        $this->get(route('pricing'))->assertSee('Two guest passes')->assertSee('Best for regulars');
        $this->get(route('admin.plans.index'))->assertOk()->assertSee('Per month')->assertSee('Members');
    }

    /* ---------- freeze gives the days back ---------- */

    public function test_unfreezing_adds_the_frozen_days_to_the_end_date(): void
    {
        $m = Member::find(1);
        $end = $m->currentMembership->ends_on->copy();
        $this->actingAs($this->owner())->post(route('admin.members.freeze', $m))->assertRedirect();
        $this->assertTrue($m->fresh()->frozen);
        $m->fresh()->update(['frozen_at' => today()->subDays(9)]);
        $this->post(route('admin.members.freeze', $m))->assertRedirect()->assertSessionHas('ok');
        $this->assertFalse($m->fresh()->frozen);
        $got = $m->fresh()->currentMembership->ends_on;
        $this->assertTrue($got->equalTo($end->copy()->addDays(9)), 'ends '.$got->toDateTimeString().' expected '.$end->copy()->addDays(9)->toDateTimeString().' (was '.$end->toDateTimeString().')');
    }

    /* ---------- analytics ---------- */

    public function test_analytics_tabs_render_with_their_data(): void
    {
        $this->actingAs($this->owner());
        $money = $this->get(route('admin.finance'))->assertOk()->assertSee('Income, expenses and profit');
        $this->assertCount(12, $money->viewData('trend')['income']);
        $this->get(route('admin.finance', ['tab' => 'members']))->assertOk()->assertSee('Renewal rate')->assertSee('Members over the year')
            ->assertViewHas('activeNow', fn ($n) => $n >= 1);
        $att = $this->get(route('admin.finance', ['tab' => 'attendance']))->assertOk()->assertSee('When people come in');
        $grid = $att->viewData('grid');
        $this->assertCount(12, $grid);
        $this->assertCount(7, $grid[0]['cells']);
        $this->get(route('admin.finance', ['month' => now()->subMonths(3)->format('Y-m'), 'tab' => 'members']))->assertOk();
        $this->get(route('admin.finance', ['month' => 'garbage']))->assertOk();         // a bad month falls back to this month
    }

    /* ---------- live search, sorting, errors ---------- */

    public function test_member_search_endpoint_finds_people_and_is_owner_only(): void
    {
        $this->actingAs($this->owner())->getJson(route('admin.search', ['q' => 'Kasun']))->assertOk()
            ->assertJsonPath('0.name', 'Kasun Perera')->assertJsonStructure([['name', 'initials', 'no', 'status', 'url']]);
        $this->getJson(route('admin.search', ['q' => 'k']))->assertOk()->assertExactJson([]);
        $this->actingAs(Member::find(1)->user)->getJson(route('admin.search', ['q' => 'Kasun']))->assertRedirect();     // members are sent back to their own app
    }

    public function test_members_list_sorts_by_balance(): void
    {
        $res = $this->actingAs($this->owner())->get(route('admin.members.index', ['sort' => 'balance', 'dir' => 'desc']))->assertOk();
        $this->assertSame('Tharushi Jayasinghe', $res->viewData('members')->first()->user->name);   // owes the most
        $this->get(route('admin.members.index', ['sort' => 'nonsense']))->assertOk();
    }

    public function test_branded_error_page_and_receipt_design(): void
    {
        $this->get('/no-such-page')->assertNotFound()->assertSee('We cannot find that page')->assertSee('Go to the home page');
        $p = \App\Models\Payment::first();
        $this->actingAs($this->owner())->get(route('admin.payments.show', $p))->assertOk()->assertSee('Payment receipt')->assertSee('Amount received');
    }
}
