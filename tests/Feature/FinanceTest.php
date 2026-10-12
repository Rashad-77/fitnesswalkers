<?php

namespace Tests\Feature;

use App\Models\{Expense, Member, Membership, Payment, Plan, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_finance_overview_numbers(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o@x.test', 'role' => 'admin', 'password' => 'password12']);
        $plan = Plan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 5000]);
        $m = Member::create(['user_id' => User::create(['name' => 'M', 'email' => 'm@x.test', 'role' => 'member', 'password' => 'password12'])->id, 'member_no' => 'FW-1', 'joined_on' => today()]);
        $ms = Membership::create(['member_id' => $m->id, 'plan_id' => $plan->id, 'starts_on' => today(), 'ends_on' => today()->addDays(30), 'price' => 5000]);
        $thisMonth = now()->startOfMonth()->addDay()->toDateString();
        Payment::create(['receipt_no' => 'R1', 'member_id' => $m->id, 'membership_id' => $ms->id, 'amount' => 3000, 'method' => 'cash', 'paid_on' => $thisMonth]);
        Payment::create(['receipt_no' => 'R2', 'member_id' => $m->id, 'amount' => 1000, 'method' => 'card', 'paid_on' => $thisMonth]);
        Payment::create(['receipt_no' => 'R3', 'member_id' => $m->id, 'amount' => 777, 'method' => 'cash', 'paid_on' => now()->subMonths(2)->toDateString()]);
        Expense::create(['category' => 'Rent', 'amount' => 1500, 'spent_on' => $thisMonth]);

        $res = $this->actingAs($owner)->get(route('admin.finance'))->assertOk();
        $res->assertViewHas('income', 4000.0)->assertViewHas('spent', 1500.0);
        $this->assertEquals(['Monthly' => 3000.0, 'Other / unassigned' => 1000.0], $res->viewData('byPlan')->all());
        $this->assertEquals(4000.0, array_sum(array_slice($res->viewData('trend')['income'], -6)) - 777);   // the 12-month trend includes the older payment
        $this->get(route('admin.dashboard'))->assertViewHas('income', 4000.0)->assertViewHas('owed', 2000.0);
        $this->get(route('admin.payments.daily', ['date' => $thisMonth]))->assertOk()->assertSee('Rs. 4,000');
    }
}
