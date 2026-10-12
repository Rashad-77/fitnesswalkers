<?php

namespace Tests\Feature;

use App\Models\{Food, Member, Setting, User};
use App\Support\Dummy;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DummySeeder::class);
    }

    private function owner(): User { return User::where('role', 'admin')->first(); }

    public function test_everything_seeded_is_flagged_dummy_and_listed(): void
    {
        $counts = Dummy::counts();
        foreach (['members', 'payments', 'exercises', 'equipment', 'plans', 'expenses', 'users'] as $t) {
            $this->assertGreaterThan(0, $counts[$t], $t);
            $this->assertSame(\DB::table($t)->count(), $counts[$t], "$t should be 100% dummy right after seeding");
        }
        $this->assertSame(0, Setting::where('key', 'gym_name')->first()->is_dummy ? 1 : 0, 'the gym name FitnessWalkers is real');
        $this->artisan('dummy:list')->expectsOutputToContain('Total dummy rows')->assertSuccessful();
        $this->actingAs($this->owner())->get(route('admin.placeholders'))->assertOk()->assertDontSee('Foods and prices');
        $this->get(route('admin.dashboard'))->assertSee('This is sample data');
    }

    public function test_editing_a_dummy_row_makes_it_real(): void
    {
        $m = Member::first();
        $this->assertTrue($m->is_dummy);
        $this->actingAs($this->owner())->put(route('admin.members.update', $m), [
            'name' => 'Kasun P.', 'email' => 'kasun@example.test', 'joined_on' => '2026-01-01', 'phone' => '0771234567',
        ])->assertRedirect();
        $this->assertFalse($m->fresh()->is_dummy);
        $this->assertFalse($m->user->fresh()->is_dummy);
    }

    public function test_purge_refuses_without_real_owner_then_keeps_real_data(): void
    {
        $o = $this->owner();
        $this->actingAs($o)->post(route('admin.placeholders.purge'), ['confirm' => 'nope'])->assertSessionHasErrors('confirm');
        $before = Member::count();
        $this->post(route('admin.placeholders.purge'), ['confirm' => 'DELETE'])->assertSessionHasErrors('confirm');   // no real admin yet
        $this->assertSame($before, Member::count());

        // owner replaces the sample login, and adds one real member
        $this->put(route('admin.settings.account'), ['name' => 'Real Owner', 'email' => 'real@owner.lk', 'password' => 'a-strong-pass', 'password_confirmation' => 'a-strong-pass'])->assertSessionHas('ok');
        $this->assertFalse($o->fresh()->is_dummy);
        $this->post(route('admin.members.store'), ['name' => 'Real Person', 'email' => 'real@person.lk', 'password' => 'password12', 'joined_on' => today()->toDateString()]);

        $this->post(route('admin.placeholders.purge'), ['confirm' => 'DELETE'])->assertSessionHas('ok');
        $this->assertSame(1, Member::count());
        $this->assertSame('Real Person', Member::first()->user->name);
        $this->assertGreaterThan(100, Food::count(), 'the food catalogue is reference data and survives the purge');
        $this->assertSame(1, User::where('role', 'admin')->count());
        $this->assertSame(0, array_sum(array_diff_key(Dummy::counts(), ['settings' => 1])));
    }

    public function test_csv_export_neutralises_spreadsheet_formulas(): void
    {
        $this->assertSame("'=SUM(A1)", \App\Http\Controllers\Admin\SystemController::safeCell('=SUM(A1)'));
        $this->assertSame("'@cmd", \App\Http\Controllers\Admin\SystemController::safeCell('@cmd'));
        $this->assertSame('Normal', \App\Http\Controllers\Admin\SystemController::safeCell('Normal'));
        $this->assertSame(12.5, \App\Http\Controllers\Admin\SystemController::safeCell(12.5));
        $res = $this->actingAs($this->owner())->get(route('admin.export.csv', 'members'))->assertOk();
        $this->assertStringContainsString('Kasun Perera', $res->streamedContent());
        $this->get(route('admin.export.csv', 'nonsense'))->assertNotFound();
    }
}
