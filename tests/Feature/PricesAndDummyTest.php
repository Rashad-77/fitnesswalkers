<?php

namespace Tests\Feature;

use App\Models\{Food, Member, Setting, User};
use App\Services\PriceUpdater;
use App\Support\Dummy;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PricesAndDummyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DummySeeder::class);
    }

    private function owner(): User { return User::where('role', 'admin')->first(); }

    public function test_price_updater_applies_sane_changes_and_rejects_wild_ones(): void
    {
        $rice = Food::where('name', 'White Rice, cooked')->first();
        $egg = Food::where('name', 'Egg, boiled')->first();
        $res = PriceUpdater::apply([
            ['name' => 'white rice, cooked', 'price_per_kg' => $rice->price_per_kg * 1.2],   // +20% ok, case-insensitive match
            ['name' => 'Egg, boiled', 'price_per_kg' => $egg->price_per_kg * 3],              // +200% rejected
            ['name' => 'Unicorn meat', 'price_per_kg' => 10],
            ['name' => 'Banana', 'price_per_kg' => -5],
        ]);
        $this->assertSame(1, $res['updated']);
        $this->assertCount(2, $res['rejected']);
        $this->assertSame(['Unicorn meat'], $res['unmatched']);
        $this->assertEqualsWithDelta(144, $rice->fresh()->price_per_kg, 0.01);
        $this->assertEquals($egg->price_per_kg, $egg->fresh()->price_per_kg);
        $this->assertTrue($rice->fresh()->price_updated_on->isToday());
        $this->assertFalse($rice->fresh()->is_dummy, 'a freshly updated price counts as real');
    }

    public function test_locked_prices_are_skipped(): void
    {
        $rice = Food::where('name', 'White Rice, cooked')->first();
        $rice->update(['price_locked' => true]);
        $res = PriceUpdater::apply([['name' => 'White Rice, cooked', 'price_per_kg' => $rice->price_per_kg * 1.1]]);
        $this->assertSame(0, $res['updated']);
        $this->assertSame(1, $res['skipped_locked']);
    }

    public function test_feed_and_missing_feed(): void
    {
        config(['fitness.price_feed_url' => null]);
        $this->assertStringContainsString('No price feed', PriceUpdater::fromFeed()['error']);

        config(['fitness.price_feed_url' => 'https://example.test/prices.json']);
        $banana = Food::where('name', 'Banana')->first();
        Http::fake(['example.test/*' => Http::response([['name' => 'Banana', 'price_per_kg' => $banana->price_per_kg + 10]])]);
        $this->assertSame(1, PriceUpdater::fromFeed()['updated']);
        Http::fake(['broken.test/*' => Http::response('nope', 500)]);
        $this->assertStringContainsString('Could not read', PriceUpdater::fromFeed('https://broken.test/prices.json')['error']);
        $this->artisan('foods:update-prices')->assertSuccessful();
    }

    public function test_owner_can_import_a_csv_and_change_is_audited(): void
    {
        $banana = Food::where('name', 'Banana')->first();
        $csv = "name,price_per_kg\nBanana,".($banana->price_per_kg + 20)."\nbad line\n";
        $file = UploadedFile::fake()->createWithContent('prices.csv', $csv);
        $this->actingAs($this->owner())->post(route('admin.foods.import'), ['file' => $file])->assertRedirect();
        $this->assertEquals($banana->price_per_kg + 20, $banana->fresh()->price_per_kg);
        $this->actingAs($this->owner())->get(route('admin.audit'))->assertSee('Imported food prices');
    }

    public function test_quick_price_edit_logs_the_change(): void
    {
        $f = Food::first();
        $this->actingAs($this->owner())->put(route('admin.foods.update', $f), ['quick' => 1, 'price_per_kg' => 999, 'price_locked' => 1])->assertRedirect();
        $this->assertEquals(999, $f->fresh()->price_per_kg);
        $this->assertTrue($f->fresh()->price_locked);
        $this->get(route('admin.audit'))->assertSee('Changed food price');
    }

    public function test_everything_seeded_is_flagged_dummy_and_listed(): void
    {
        $counts = Dummy::counts();
        foreach (['members', 'payments', 'foods', 'exercises', 'equipment', 'plans', 'expenses', 'users'] as $t) {
            $this->assertGreaterThan(0, $counts[$t], $t);
            $this->assertSame(\DB::table($t)->count(), $counts[$t], "$t should be 100% dummy right after seeding");
        }
        $this->assertSame(0, Setting::where('key', 'gym_name')->first()->is_dummy ? 1 : 0, 'the gym name FitnessWalkers is real');
        $this->artisan('dummy:list')->expectsOutputToContain('Total dummy rows')->assertSuccessful();
        $this->actingAs($this->owner())->get(route('admin.placeholders'))->assertOk()->assertSee('Foods and prices');
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
        $this->assertSame(0, Food::count());
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
