<?php

namespace Tests\Feature;

use App\Models\{Member, Membership, Payment, Plan, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create(['name' => 'Owner', 'email' => 'o@x.test', 'role' => 'admin', 'password' => 'password12']);
    }

    public function test_adding_a_member_with_plan_and_partial_payment(): void
    {
        $plan = Plan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 5000]);
        $this->actingAs($this->owner())->post(route('admin.members.store'), [
            'name' => 'Test Member', 'email' => 'tm@x.test', 'password' => 'secret123', 'joined_on' => today()->toDateString(),
            'plan_id' => $plan->id, 'starts_on' => today()->toDateString(), 'initial_payment' => 2000, 'method' => 'cash',
        ])->assertRedirect();

        $m = Member::with('currentMembership')->first();
        $this->assertSame('FW-0001', $m->member_no);
        $this->assertSame('active', $m->status());
        $this->assertEquals(30, $m->daysLeft());
        $this->assertEquals(3000.0, $m->balanceDue());
        $this->assertSame('R-'.now()->format('Y').'-00001', Payment::first()->receipt_no);
    }

    public function test_cannot_pay_more_than_balance_and_receipts_are_unique(): void
    {
        $plan = Plan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 5000]);
        $u = User::create(['name' => 'M', 'email' => 'm@x.test', 'role' => 'member', 'password' => 'password12']);
        $m = Member::create(['user_id' => $u->id, 'member_no' => 'FW-0001', 'joined_on' => today()]);
        $ms = Membership::create(['member_id' => $m->id, 'plan_id' => $plan->id, 'starts_on' => today(), 'ends_on' => today()->addDays(30), 'price' => 5000]);
        $owner = $this->owner();

        $pay = fn ($amt) => $this->actingAs($owner)->post(route('admin.payments.store'), [
            'member_id' => $m->id, 'membership_id' => $ms->id, 'amount' => $amt, 'method' => 'bank', 'paid_on' => today()->toDateString(),
        ]);
        $pay(3000)->assertRedirect();
        $pay(2500)->assertSessionHasErrors('amount');        // only 2000 left
        $pay(2000)->assertRedirect();
        $this->assertEquals(0.0, $ms->fresh()->balance());
        $this->assertCount(2, Payment::all());
        $this->assertCount(2, Payment::pluck('receipt_no')->unique());
        $this->get(route('admin.payments.show', Payment::first()))->assertOk()->assertSee('Payment receipt');
    }

    public function test_membership_for_a_different_member_is_rejected(): void
    {
        $plan = Plan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 5000]);
        $mk = fn ($n) => Member::create(['user_id' => User::create(['name' => $n, 'email' => "$n@x.test", 'role' => 'member', 'password' => 'password12'])->id, 'member_no' => "FW-$n", 'joined_on' => today()]);
        [$a, $b] = [$mk('a'), $mk('b')];
        $ms = Membership::create(['member_id' => $a->id, 'plan_id' => $plan->id, 'starts_on' => today(), 'ends_on' => today()->addDays(30), 'price' => 5000]);
        $this->actingAs($this->owner())->post(route('admin.payments.store'), [
            'member_id' => $b->id, 'membership_id' => $ms->id, 'amount' => 100, 'method' => 'cash', 'paid_on' => today()->toDateString(),
        ])->assertSessionHasErrors('membership_id');
        $this->assertSame(0, Payment::count());
    }

    public function test_status_logic(): void
    {
        $plan = Plan::create(['name' => 'Monthly', 'duration_days' => 30, 'price' => 5000]);
        $mk = function (int $endsInDays, bool $frozen = false) use ($plan) {
            static $i = 0; $i++;
            $m = Member::create(['user_id' => User::create(['name' => "u$i", 'email' => "u$i@x.test", 'role' => 'member', 'password' => 'password12'])->id, 'member_no' => "N$i", 'joined_on' => today(), 'frozen' => $frozen]);
            Membership::create(['member_id' => $m->id, 'plan_id' => $plan->id, 'starts_on' => today()->subDays(30), 'ends_on' => today()->addDays($endsInDays), 'price' => 1]);
            return Member::with('currentMembership')->find($m->id);
        };
        $this->assertSame('active', $mk(20)->status());
        $this->assertSame('expiring', $mk(7)->status());
        $this->assertSame('expiring', $mk(0)->status());
        $this->assertSame('expired', $mk(-1)->status());
        $this->assertSame('frozen', $mk(20, true)->status());
        $u = User::create(['name' => 'x', 'email' => 'x@x.test', 'role' => 'member', 'password' => 'password12']);
        $this->assertSame('none', Member::create(['user_id' => $u->id, 'member_no' => 'Z', 'joined_on' => today()])->status());
    }

    public function test_whatsapp_link_uses_sri_lanka_country_code(): void
    {
        $u = User::create(['name' => 'x', 'email' => 'w@x.test', 'phone' => '077 123 4567', 'role' => 'member', 'password' => 'password12']);
        $m = Member::create(['user_id' => $u->id, 'member_no' => 'W', 'joined_on' => today()]);
        $this->assertStringStartsWith('https://wa.me/94771234567?text=', $m->whatsappUrl('Hi there'));
    }
}
