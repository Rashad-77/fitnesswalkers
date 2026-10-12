<?php

namespace Tests\Feature;

use App\Models\{Exercise, Member, PlanRequest, User, WorkoutPlan, WorkoutPlanItem};
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->seed(DummySeeder::class); }

    private function owner(): User { return User::where('role', 'admin')->first(); }

    /** A pending request whose member owes money (or not), with a one-exercise draft plan. */
    private function request(bool $owes): PlanRequest
    {
        $m = Member::all()->first(fn ($m) => in_array($m->status(), ['active', 'expiring']) && ($m->balanceDue() > 0) === $owes && ! $m->approvedPlan());
        $this->assertNotNull($m, 'sample data has such a member');
        $req = PlanRequest::create(['member_id' => $m->id, 'goal' => 'general', 'level' => 'beginner', 'days_per_week' => 3]);
        $plan = WorkoutPlan::create(['member_id' => $m->id, 'plan_request_id' => $req->id, 'name' => 'Draft', 'status' => 'draft']);
        WorkoutPlanItem::create(['workout_plan_id' => $plan->id, 'day_no' => 1, 'exercise_id' => Exercise::first()->id, 'sets' => 4, 'reps' => '8']);
        return $req;
    }

    public function test_a_plan_is_not_approved_while_payment_is_due_unless_the_owner_overrides(): void
    {
        $req = $this->request(true);
        $this->actingAs($this->owner())->get(route('admin.requests.show', $req))->assertOk()->assertSee('still due');
        $this->post(route('admin.requests.approve', $req))->assertSessionHas('warn');
        $this->assertSame('pending', $req->fresh()->status);
        $this->post(route('admin.requests.approve', $req), ['override' => 1])->assertRedirect(route('admin.requests.index'));
        $this->assertSame('approved', $req->fresh()->status);
    }

    public function test_a_paid_member_is_approved_straight_away_and_can_then_get_a_meal_plan(): void
    {
        $req = $this->request(false);
        $this->actingAs($this->owner())->get(route('admin.requests.show', $req))->assertSee('Paid in full');
        $this->post(route('admin.requests.approve', $req))->assertRedirect(route('admin.requests.index'));
        $member = $req->member->fresh();
        $this->assertNotNull($member->approvedPlan());
        $this->actingAs($member->user)->get(route('member.diet.create'))->assertOk();
    }

    public function test_the_owner_can_start_a_blank_plan_and_set_sets_and_reps(): void
    {
        $req = PlanRequest::create(['member_id' => Member::first()->id, 'goal' => 'general', 'level' => 'beginner', 'days_per_week' => 3]);
        $this->actingAs($this->owner())->post(route('admin.requests.draft', $req), ['template_id' => 'blank'])->assertSessionHas('ok');
        $this->assertSame(0, $req->fresh()->plan->items()->count());
        $ex = Exercise::first();
        $this->put(route('admin.requests.update', $req), ['name' => 'My plan', 'items' => [['day_no' => 1, 'exercise_id' => $ex->id, 'sets' => 5, 'reps' => '12'], ['day_no' => 1, 'exercise_id' => '', 'sets' => 3, 'reps' => '10']]])->assertSessionHas('ok');
        $item = $req->fresh()->plan->items()->first();
        $this->assertSame([5, '12'], [(int) $item->sets, (string) $item->reps]);
    }

    public function test_tutorial_gif_shows_for_a_linked_gif_and_the_library_lists_missing_ones(): void
    {
        $ex = Exercise::first();
        $ex->update(['clip_url' => 'https://example.test/bench.gif']);
        $this->assertSame('image', $ex->fresh()->mediaKind());
        $ex->update(['clip_url' => '/uploads/clips/x.mp4']);
        $this->assertSame('video', $ex->fresh()->mediaKind());
        $ex->update(['clip_url' => 'https://example.test/bench.gif']);
        $m = Member::all()->first(fn ($m) => $m->approvedPlan());
        $day = $m->approvedPlan()->items->first();
        if ($day->exercise_id !== $ex->id) $day->update(['exercise_id' => $ex->id]);
        $this->actingAs($m->user)->get(route('member.exercise', $ex))->assertOk()->assertSee('example.test/bench.gif', false);
        $this->get(route('member.workout.day', $day->day_no))->assertOk()->assertSee('example.test/bench.gif', false);
        $this->actingAs($this->owner())->get(route('admin.exercises.index', ['tutorial' => 'missing']))->assertOk()->assertSee('still need one');
    }

    public function test_member_sees_waiting_for_payment_on_a_pending_request(): void
    {
        $req = $this->request(true);
        $req->plan->delete();
        $this->actingAs($req->member->user)->get(route('member.workout'))->assertOk()->assertSee('Waiting for payment');
    }
}
