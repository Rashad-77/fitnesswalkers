<?php

namespace Tests\Feature;

use App\Models\{Equipment, Exercise, Member, PlanRequest, User, WorkoutLog, WorkoutPlan, WorkoutTemplate};
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User { return User::where('role', 'admin')->first(); }

    public function test_plan_request_approval_workflow(): void
    {
        $this->seed(DummySeeder::class);
        $member = Member::find(5);                                  // active, no plan
        $this->assertSame('active', $member->status());

        // member requests a plan
        $this->actingAs($member->user)->post(route('member.workout.request'), ['goal' => 'fat_loss', 'level' => 'beginner', 'days_per_week' => 3])->assertRedirect();
        $req = PlanRequest::where('member_id', $member->id)->first();
        $this->assertSame('pending', $req->status);
        // a second request while one is pending is refused
        $this->actingAs($member->user)->post(route('member.workout.request'), ['goal' => 'general', 'level' => 'beginner', 'days_per_week' => 3]);
        $this->assertSame(1, PlanRequest::where('member_id', $member->id)->count());

        // owner drafts from template: member still sees nothing
        $tpl = WorkoutTemplate::where('goal', 'fat_loss')->first();
        $this->actingAs($this->owner())->post(route('admin.requests.draft', $req), ['template_id' => $tpl->id])->assertRedirect();
        $draft = WorkoutPlan::where('member_id', $member->id)->first();
        $this->assertSame('draft', $draft->status);
        $this->assertGreaterThan(0, $draft->items()->count());
        $this->actingAs($member->user)->get(route('member.workout.day', 1))->assertNotFound();
        $this->actingAs($member->user)->get(route('member.workout'))->assertDontSee($tpl->name)->assertSee('Request sent');

        // owner approves: member now sees it, and the old approved plan is archived
        $this->actingAs($this->owner())->post(route('admin.requests.approve', $req))->assertRedirect(route('admin.requests.index'));
        $this->assertSame('approved', $draft->fresh()->status);
        $this->assertSame('approved', $req->fresh()->status);
        $this->actingAs($member->user)->get(route('member.workout.day', 1))->assertOk();
    }

    public function test_cannot_approve_empty_plan_and_rejection_needs_note(): void
    {
        $this->seed(DummySeeder::class);
        $req = PlanRequest::first();
        $this->actingAs($this->owner())->post(route('admin.requests.draft', $req), ['template_id' => WorkoutTemplate::first()->id]);
        WorkoutPlan::where('plan_request_id', $req->id)->first()->items()->delete();
        $this->post(route('admin.requests.approve', $req))->assertSessionHas('warn');
        $this->assertSame('pending', $req->fresh()->status);
        $this->post(route('admin.requests.reject', $req), ['admin_note' => ''])->assertSessionHasErrors('admin_note');
        $this->post(route('admin.requests.reject', $req), ['admin_note' => 'Come see me first'])->assertRedirect();
        $this->assertSame('rejected', $req->fresh()->status);
    }

    public function test_second_approval_archives_the_old_plan(): void
    {
        $this->seed(DummySeeder::class);
        $member = Member::first();                                  // has an approved plan already
        $req = PlanRequest::create(['member_id' => $member->id, 'goal' => 'general', 'level' => 'beginner', 'days_per_week' => 3]);
        $this->actingAs($this->owner())->post(route('admin.requests.draft', $req), ['template_id' => WorkoutTemplate::first()->id]);
        $this->post(route('admin.requests.approve', $req));
        $this->assertSame(1, WorkoutPlan::where('member_id', $member->id)->where('status', 'approved')->count());
        $this->assertSame(1, WorkoutPlan::where('member_id', $member->id)->where('status', 'archived')->count());
    }

    public function test_out_of_order_equipment_hides_exercises_from_members(): void
    {
        $this->seed(DummySeeder::class);
        $member = Member::first();
        $press = Exercise::where('name', 'Leg Press')->first();
        $this->actingAs($member->user)->get(route('member.exercise', $press))->assertOk();
        $this->actingAs($this->owner())->put(route('admin.equipment.update', $press->equipment), ['out_of_order' => '1']);
        $this->actingAs($member->user)->get(route('member.exercise', $press))->assertNotFound();
        $this->get(route('member.workout'))->assertDontSee('href="'.route('member.exercise', $press).'"', false);     // gone from the guide
        $this->get(route('member.workout.day', 1))->assertOk()->assertSee('out of order right now');   // flagged in the plan
        // Leg Curl was already out of order in the sample data
        $curl = Exercise::where('name', 'Leg Curl')->first();
        $this->get(route('member.exercise', $curl))->assertNotFound();
        // bringing it back
        $this->actingAs($this->owner())->put(route('admin.equipment.update', $press->equipment), []);
        $this->actingAs($member->user)->get(route('member.exercise', $press))->assertOk();
    }

    public function test_logging_workouts_replaces_same_day_entry_and_ignores_foreign_exercises(): void
    {
        $this->seed(DummySeeder::class);
        $member = Member::first();
        $plan = $member->approvedPlan();
        $ex = $plan->items->first()->exercise;
        $foreign = Exercise::whereNotIn('id', $plan->items->pluck('exercise_id'))->first();
        $payload = ['sets' => [$ex->id => [1 => ['reps' => 10, 'weight' => 50], 2 => ['reps' => 9, 'weight' => 50]], $foreign->id => [1 => ['reps' => 5, 'weight' => 5]]]];
        $this->actingAs($member->user)->post(route('member.workout.log'), $payload)->assertSessionHas('ok');
        $this->post(route('member.workout.log'), $payload);
        $this->assertSame(2, WorkoutLog::where('member_id', $member->id)->where('exercise_id', $ex->id)->whereDate('logged_on', today())->count());
        $this->assertSame(0, WorkoutLog::where('exercise_id', $foreign->id)->count());
    }

    public function test_swap_only_within_same_muscle_group(): void
    {
        $this->seed(DummySeeder::class);
        $member = Member::first();
        $item = $member->approvedPlan()->items->first();
        $same = Exercise::available()->where('muscle_group', $item->exercise->muscle_group)->where('id', '!=', $item->exercise_id)->first();
        $other = Exercise::available()->where('muscle_group', '!=', $item->exercise->muscle_group)->first();
        $this->actingAs($member->user)->post(route('member.workout.swap', $item), ['exercise_id' => $other->id])->assertSessionHas('warn');
        $this->assertNotSame($other->id, $item->fresh()->exercise_id);
        $this->post(route('member.workout.swap', $item), ['exercise_id' => $same->id])->assertSessionHas('ok');
        $this->assertSame($same->id, $item->fresh()->exercise_id);
        // someone else's plan item can't be swapped
        $this->actingAs(Member::find(2)->user)->post(route('member.workout.swap', $item), ['exercise_id' => $same->id])->assertForbidden();
    }

    public function test_quiet_members_list(): void
    {
        $this->seed(DummySeeder::class);
        $res = $this->actingAs($this->owner())->get(route('admin.members.index', ['filter' => 'quiet']))->assertOk();
        $res->assertDontSee('Kasun Perera');                  // trained yesterday
        $res->assertSee('Dilshan Silva');                     // active, never logged
        $res->assertDontSee('Hasini De Silva');               // frozen, not counted
    }
}
