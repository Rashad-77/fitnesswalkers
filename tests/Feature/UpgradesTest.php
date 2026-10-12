<?php

namespace Tests\Feature;

use App\Models\{Attendance, Enquiry, Member, Setting, User};
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Check-in, enquiries, joining form, renewals, and the new menus. */
class UpgradesTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User { return User::where('role', 'admin')->first(); }

    private function freshMember(): Member
    {
        // Member 1 is active in the seed; clear today's visit so we can test check-in cleanly.
        $m = Member::find(1);
        Attendance::where('member_id', $m->id)->delete();
        return $m;
    }

    public function test_member_scans_door_qr_and_gets_one_visit_per_day(): void
    {
        $this->seed(DummySeeder::class);
        $m = $this->freshMember();
        $code = Setting::checkinCode();

        $this->actingAs($m->user)->get(route('member.checkin', ['code' => $code]))->assertRedirect(route('member.home'))->assertSessionHas('ok');
        $this->assertSame(1, Attendance::where('member_id', $m->id)->whereDate('visited_on', today())->count());
        $this->get(route('member.checkin', ['code' => $code]))->assertSessionHas('ok'); // scanning twice must not double count or crash
        $this->assertSame(1, Attendance::where('member_id', $m->id)->whereDate('visited_on', today())->count());
    }

    public function test_wrong_code_or_expired_member_is_not_checked_in(): void
    {
        $this->seed(DummySeeder::class);
        $m = $this->freshMember();
        $this->actingAs($m->user)->get(route('member.checkin', ['code' => 'nope']))->assertSessionHas('warn');
        $this->assertSame(0, Attendance::where('member_id', $m->id)->count());

        $m->currentMembership->update(['ends_on' => today()->subDays(3)]);
        $this->get(route('member.checkin', ['code' => Setting::checkinCode()]))->assertSessionHas('warn');
        $this->assertSame(0, Attendance::where('member_id', $m->id)->count());
    }

    public function test_guest_who_scans_the_qr_is_sent_to_login_then_back(): void
    {
        $this->seed(DummySeeder::class);
        $url = route('member.checkin', ['code' => Setting::checkinCode()]);
        $this->get($url)->assertRedirect(route('login'));
        $this->post(route('login'), ['email' => Member::find(1)->user->email, 'password' => 'password'])->assertRedirect($url);
    }

    public function test_owner_can_check_in_by_button_and_by_typing_a_name(): void
    {
        $this->seed(DummySeeder::class);
        $m = $this->freshMember();
        $this->actingAs($this->owner())->post(route('admin.members.checkin', $m))->assertSessionHas('ok');
        $this->assertTrue($m->fresh()->checkedInToday());

        $m2 = Member::find(3);
        $this->post(route('admin.checkin.quick'), ['who' => $m2->user->name.' ('.$m2->member_no.')'])->assertSessionHas('ok');
        $this->assertTrue($m2->fresh()->checkedInToday());
        $this->post(route('admin.checkin.quick'), ['who' => 'Nobody Here'])->assertSessionHasErrors('who');
    }

    public function test_qr_poster_shows_and_new_code_replaces_the_old_one(): void
    {
        $this->seed(DummySeeder::class);
        $old = Setting::checkinCode();
        $this->actingAs($this->owner())->get(route('admin.checkin.poster'))->assertOk()->assertSee('<svg', false)->assertSee('Scan to check in');
        $this->post(route('admin.checkin.renew'))->assertRedirect();
        $this->assertNotSame($old, Setting::checkinCode());
        $this->actingAs(Member::find(1)->user)->get(route('member.checkin', ['code' => $old]))->assertSessionHas('warn');
    }

    public function test_members_cannot_reach_owner_pages(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs(Member::find(1)->user);
        foreach (['admin.checkin.poster', 'admin.enquiries.index', 'admin.account', 'admin.renewals'] as $r) $this->get(route($r))->assertRedirect(route('member.home'));
    }

    public function test_public_enquiry_is_saved_and_shows_in_owner_list(): void
    {
        $this->seed(DummySeeder::class);
        $this->post(route('enquiry.store'), ['name' => 'Test Person', 'phone' => '0771234567', 'interest' => 'Weight loss'])->assertRedirect(route('contact'))->assertSessionHas('ok');
        $e = Enquiry::where('name', 'Test Person')->first();
        $this->assertSame('website', $e->source);
        $this->assertSame('new', $e->status);
        $this->assertFalse((bool) $e->is_dummy);
        $this->actingAs($this->owner())->get(route('admin.enquiries.index'))->assertOk()->assertSee('Test Person');
    }

    public function test_enquiry_honeypot_and_validation(): void
    {
        $this->seed(DummySeeder::class);
        $before = Enquiry::count();
        $this->post(route('enquiry.store'), ['name' => 'Bot', 'phone' => '0771234567', 'website' => 'http://spam'])->assertRedirect();
        $this->assertSame($before, Enquiry::count());
        $this->post(route('enquiry.store'), ['name' => '', 'phone' => '1'])->assertSessionHasErrors(['name', 'phone']);
    }

    public function test_enquiry_form_is_rate_limited(): void
    {
        $this->seed(DummySeeder::class);
        for ($i = 0; $i < 5; $i++) $this->post(route('enquiry.store'), ['name' => "P$i", 'phone' => '0771234567']);
        $this->post(route('enquiry.store'), ['name' => 'Too many', 'phone' => '0771234567'])->assertStatus(429);
    }

    public function test_owner_adds_walk_in_updates_status_and_converts_to_member(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs($this->owner())->post(route('admin.enquiries.store'), ['name' => 'Walker In', 'phone' => '0779999999', 'source' => 'walk-in'])->assertRedirect(route('admin.enquiries.index'));
        $e = Enquiry::where('name', 'Walker In')->first();
        $this->put(route('admin.enquiries.update', $e), ['status' => 'contacted', 'notes' => 'called'])->assertSessionHas('ok');
        $this->assertSame('contacted', $e->fresh()->status);

        $this->get(route('admin.members.create', ['enquiry' => $e->id]))->assertOk()->assertSee('Walker In')->assertSee('0779999999');
        $this->post(route('admin.members.store'), [
            'name' => 'Walker In', 'phone' => '0779999999', 'email' => 'walker@example.test', 'password' => 'secret123',
            'joined_on' => today()->toDateString(), 'enquiry_id' => $e->id,
        ])->assertRedirect();
        $e->refresh();
        $this->assertSame('joined', $e->status);
        $this->assertNotNull($e->member_id);
    }

    public function test_joining_form_stores_health_answers_flag_and_terms(): void
    {
        $this->seed(DummySeeder::class);
        $base = ['phone' => '0770001111', 'password' => 'secret123', 'joined_on' => today()->toDateString(), 'health_form' => 1];
        $this->actingAs($this->owner())->post(route('admin.members.store'), $base + ['name' => 'Healthy One', 'email' => 'h1@example.test', 'waiver' => 1]);
        $a = Member::whereHas('user', fn ($u) => $u->where('email', 'h1@example.test'))->first();
        $this->assertFalse($a->health_flag);
        $this->assertNotNull($a->waiver_accepted_at);

        $this->post(route('admin.members.store'), $base + ['name' => 'Careful One', 'email' => 'h2@example.test', 'health' => ['heart' => 1], 'health_notes' => 'on tablets']);
        $b = Member::whereHas('user', fn ($u) => $u->where('email', 'h2@example.test'))->first();
        $this->assertTrue($b->health_flag);
        $this->assertTrue($b->health_answers['heart']);
        $this->assertFalse($b->health_answers['chest']);
        $this->assertNull($b->waiver_accepted_at);

        $this->get(route('admin.members.show', $b))->assertOk()->assertSee('Some answers were')->assertSee('not recorded');
        $this->get(route('admin.members.show', [$b, 'tab' => 'workout']))->assertOk()->assertSee('health form has');
    }

    public function test_renewals_page_lists_requests_and_ending_memberships_without_whatsapp(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs($this->owner())->get(route('admin.renewals'))->assertOk()
            ->assertSee('Expiring this week')->assertSee('Expired in the last 60 days')->assertDontSee('wa.me/', false);
    }

    public function test_members_filters_have_counts_and_work(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs($this->owner());
        $res = $this->get(route('admin.members.index'))->assertOk()->assertSee('Low attendance')->assertSee('Last visit');
        $res->assertSee('Outstanding balance');
        $this->get(route('admin.members.index', ['filter' => 'owes']))->assertOk()->assertSee('Tharushi Jayasinghe')->assertDontSee('Kasun Perera');
        $this->get(route('admin.members.index', ['filter' => 'frozen']))->assertOk()->assertSee('Hasini De Silva')->assertDontSee('Kasun Perera');
        $this->get(route('admin.members.index', ['filter' => 'nonsense']))->assertOk()->assertSee('Kasun Perera'); // falls back to All
    }

    public function test_member_profile_tabs_all_load(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs($this->owner());
        foreach (['overview', 'payments', 'workout', 'meals', 'progress'] as $tab) {
            $this->get(route('admin.members.show', [Member::find(1), 'tab' => $tab]))->assertOk();
        }
        $this->get(route('admin.members.show', [Member::find(1), 'tab' => 'payments']))->assertSee('Record a payment')->assertSee('Add or renew a package');
        $this->get(route('admin.members.show', [Member::find(1), 'tab' => 'progress']))->assertSee('Recent visits');
    }

    public function test_today_page_shows_needs_you_checkin_and_here_today(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs($this->owner())->get(route('admin.dashboard'))->assertOk()
            ->assertSee('Requires attention')->assertSee('Expiring this week')->assertSee('Outstanding balance')->assertSee('Workout requests')->assertSee('New enquiries')
            ->assertSee('Amaya Wijesinghe')->assertSee('Present today')->assertSee('FW-0001', false);
    }

    public function test_admin_menu_lists_every_page_always_visible_with_no_tab_rows(): void
    {
        $this->seed(DummySeeder::class);
        $html = $this->actingAs($this->owner())->get(route('admin.members.index'))->assertOk()->getContent();
        foreach (['Overview', 'People', 'Training', 'Money', 'Gym', 'Today', 'Members', 'Enquiries', 'Renewals', 'Announcements', 'Workout requests', 'Meal plans', 'Exercises',
            'Workout templates', 'Equipment', 'Foods', 'Payments', 'Packages', 'Expenses', 'Reports', 'Check-in QR', 'Gym profile', 'My account', 'Data export', 'Activity log'] as $w) $this->assertStringContainsString($w, $html);
        $this->assertStringNotContainsString('class="subnav"', $html, 'the old tab row is gone');
        foreach (['Renewals &amp; overdue', 'Quiet members', 'Change history', '>Money<', 'Not training lately', 'Food prices'] as $old) $this->assertStringNotContainsString($old, $html);
        $this->get(route('admin.quiet'))->assertRedirect(route('admin.members.index', ['filter' => 'quiet']));
    }

    public function test_settings_and_account_are_separate_pages(): void
    {
        $this->seed(DummySeeder::class);
        $this->actingAs($this->owner());
        $this->get(route('admin.settings'))->assertOk()->assertSee('Gym profile')->assertDontSee('Owner login');
        $this->get(route('admin.account'))->assertOk()->assertSee('Owner login');
        $this->get(route('admin.plans.index'))->assertOk()->assertSee('Packages');
    }

    public function test_member_app_tabs_me_page_and_bmi_redirect(): void
    {
        $this->seed(DummySeeder::class);
        $m = Member::find(1);
        $this->actingAs($m->user);
        $this->get(route('member.me'))->assertOk();
        $this->get(route('member.home'))->assertOk()->assertSee('Food');
        $this->get(route('member.diet'))->assertOk()->assertSee('Meal plan');
        $this->get(route('member.bmi'))->assertRedirect();
        $this->get(route('member.progress'))->assertOk()->assertSee('BMI');
    }

    public function test_sample_attendance_and_enquiries_are_flagged_and_purgeable(): void
    {
        $this->seed(DummySeeder::class);
        $this->assertSame(Attendance::count(), Attendance::where('is_dummy', true)->count());
        $this->assertSame(Enquiry::count(), Enquiry::where('is_dummy', true)->count());
        $this->assertGreaterThan(0, Enquiry::count());
        $this->artisan('dummy:purge', ['--force' => true])->assertSuccessful();
        $this->assertSame(0, Attendance::count());
        $this->assertSame(0, Enquiry::count());
    }

    public function test_privacy_policy_mentions_enquiries_visits_and_health_form(): void
    {
        $this->get(route('privacy'))->assertOk()->assertSee('enquiry through this website')->assertSee('days you visit')->assertSee('health questions');
    }
}
