<?php

namespace Tests\Feature;

use App\Models\{Goal, Member, User};
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/app')->assertRedirect('/login');
    }

    public function test_login_and_role_redirects(): void
    {
        $this->seed(DummySeeder::class);
        $this->post('/login', ['email' => 'owner@fitnesswalkers.test', 'password' => 'password'])->assertRedirect('/admin');
        $this->post('/logout');
        $this->post('/login', ['email' => 'kasun@example.test', 'password' => 'password'])->assertRedirect('/app');
        $this->post('/logout');
        $this->post('/login', ['email' => 'kasun@example.test', 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    public function test_members_cannot_open_owner_pages_and_vice_versa(): void
    {
        $this->seed(DummySeeder::class);
        $member = Member::first();
        $this->actingAs($member->user)->get('/admin')->assertRedirect('/app');
        $this->actingAs($member->user)->get('/admin/payments')->assertRedirect('/app');
        $this->actingAs($member->user)->post('/admin/expenses', ['category' => 'Rent', 'amount' => 5, 'spent_on' => '2026-01-01'])->assertRedirect('/app');
        $this->actingAs(User::where('role', 'admin')->first())->get('/app')->assertRedirect('/admin');
    }

    public function test_member_cannot_touch_another_members_goal(): void
    {
        $this->seed(DummySeeder::class);
        $goal = Goal::first();                               // belongs to member 1
        $other = Member::where('id', '!=', $goal->member_id)->first();
        $this->actingAs($other->user)->post(route('member.progress.goal.toggle', $goal))->assertForbidden();
        $this->assertFalse($goal->fresh()->done);
    }

    public function test_member_cannot_see_workout_day_without_approved_plan(): void
    {
        $this->seed(DummySeeder::class);
        $noPlan = Member::where('id', 5)->first(); // seeded plan only for member 1
        $this->actingAs($noPlan->user)->get(route('member.workout.day', 1))->assertNotFound();
    }
}
