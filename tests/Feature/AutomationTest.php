<?php

namespace Tests\Feature;

use App\Models\{Member, MessageLog, Reminder, Setting, User};
use App\Services\{AutoReminders, WhatsApp};
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Automatic WhatsApp reminders, with Meta's API faked so nothing real is ever sent. */
class AutomationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DummySeeder::class);
    }

    private function owner(): User { return User::where('role', 'admin')->first(); }

    private function connect(string $mode, array $on = ['expiring_7'], bool $approve = true): void
    {
        Setting::put('wa_phone_id', '1098765', false); Setting::put('wa_waba_id', '5551234', false);
        WhatsApp::putSecret('wa_token', 'secret-token-123');
        Setting::put('wa_mode', $mode, false);
        foreach ($on as $k) { Setting::put('wa_auto_'.$k, '1', false); if ($approve) WhatsApp::markApproved($k); }
    }

    private function ok(string $id = 'wamid.ABC'): array
    {
        return ['graph.facebook.com/*' => Http::response(['messaging_product' => 'whatsapp', 'messages' => [['id' => $id]]], 200)];
    }

    public function test_wording_is_converted_to_a_template_and_a_new_wording_means_a_new_template(): void
    {
        $c = WhatsApp::convert('Hi {name}, your {package} ends on {date}. Bye {name}! {mystery}');
        $this->assertSame('Hi {{1}}, your {{2}} ends on {{3}}. Bye {{1}}! {mystery}', $c['body']);
        $this->assertSame(['name', 'package', 'date'], $c['order']);

        $before = WhatsApp::templateName('expiring_7');
        $this->assertMatchesRegularExpression('/^fw_expiring_7_[a-f0-9]{6}$/', $before);
        WhatsApp::markApproved('expiring_7');
        $this->assertTrue(WhatsApp::approved('expiring_7'));
        Setting::put('msg_expiring_7', 'Hello {name}, new wording about {date}.', false);
        $this->assertNotSame($before, WhatsApp::templateName('expiring_7'));
        $this->assertFalse(WhatsApp::approved('expiring_7'), 'a new wording must be approved again');
    }

    public function test_the_right_members_are_due_on_the_right_days(): void
    {
        Setting::put('wa_mode', 'practice', false);
        foreach (array_keys(WhatsApp::AUTO) as $k) Setting::put('wa_auto_'.$k, '1', false);
        $plan = AutoReminders::plan();
        $due = collect($plan['send'])->mapWithKeys(fn ($r) => [$r['member']->id.':'.$r['key'] => true]);
        $this->assertTrue($due->has('3:expiring_7'), 'Dilshan ends in 3 days');
        $this->assertTrue($due->has('4:expiring_7'));
        $this->assertTrue($due->has('4:owing'), 'Tharushi owes money');
        $this->assertFalse($due->has('1:expiring_7'), 'Kasun has 20 days left');
        $this->assertFalse($due->keys()->contains(fn ($k) => str_starts_with($k, '10:')), 'a frozen member gets nothing');
        // exact windows
        $m = Member::find(3);
        $m->currentMembership->update(['ends_on' => today()->addDays(2)]);
        $this->assertContains('expiring_2', collect(AutoReminders::dueFor($m->fresh()))->pluck(0)->all());
        $m->currentMembership->update(['ends_on' => today()]);
        $this->assertContains('expiring_0', collect(AutoReminders::dueFor($m->fresh()))->pluck(0)->all());
        $m->currentMembership->update(['ends_on' => today()->subDays(5)]);
        $this->assertContains('expired_3', collect(AutoReminders::dueFor($m->fresh()))->pluck(0)->all());
        $m->currentMembership->update(['ends_on' => today()->subDays(30)]);
        $this->assertSame([], collect(AutoReminders::dueFor($m->fresh()))->pluck(0)->intersect(['expired_3', 'expired_30'])->all(), 'no automatic message weeks after it ended');
    }

    public function test_practice_mode_records_but_contacts_nobody_and_does_not_repeat(): void
    {
        Http::fake();
        $this->connect('practice');
        $r = AutoReminders::run();
        $this->assertGreaterThan(0, $r['sent']);
        Http::assertNothingSent();
        $n = MessageLog::where('status', 'practice')->count();
        $this->assertSame($r['sent'], $n);
        $this->assertSame(0, AutoReminders::run()['sent'], 'a second run the same day does nothing new');
        $this->assertSame(0, Reminder::count());
    }

    public function test_live_mode_sends_the_approved_template_once_with_the_right_values(): void
    {
        Http::fake($this->ok());
        $this->connect('live');
        $m = Member::find(3);
        $res = AutoReminders::run();
        $this->assertSame(3, $res['sent']);                                         // Dilshan, Tharushi, Sanduni
        Http::assertSent(function ($req) use ($m) {
            $d = $req->data();
            if (! str_contains($req->url(), '/1098765/messages')) return false;
            if (($d['to'] ?? '') !== '94770000003') return false;                  // Dilshan: 077 0000003 becomes 94 77...
            $p = array_column($d['template']['components'][0]['parameters'], 'text');
            return $req->hasHeader('Authorization', 'Bearer secret-token-123')
                && $d['type'] === 'template' && $d['template']['name'] === WhatsApp::templateName('expiring_7')
                && $d['template']['language']['code'] === 'en'
                && $p === ['Dilshan', 'Monthly', 'FitnessWalkers', $m->currentMembership->ends_on->format('d M Y'), '077 000 0000'];
        });
        $log = MessageLog::where(['member_id' => 3, 'key' => 'expiring_7'])->first();
        $this->assertSame('sent', $log->status);
        $this->assertSame('wamid.ABC', $log->wamid);
        $this->assertStringContainsString('Dilshan', $log->text);
        $this->assertTrue(Reminder::where(['member_id' => 3, 'kind' => 'expiring'])->exists(), 'the manual Reminders page shows it as sent');

        $count = count(Http::recorded());
        $this->assertSame(0, AutoReminders::run()['sent']);
        $this->assertCount($count, Http::recorded(), 'nothing is sent twice');
    }

    public function test_nothing_goes_out_without_approval_consent_or_a_switch(): void
    {
        Http::fake($this->ok());
        $this->connect('live', ['expiring_7'], approve: false);
        $this->assertSame(0, AutoReminders::run()['sent']);
        $this->assertNotEmpty(AutoReminders::plan()['waiting']);
        Http::assertNothingSent();

        WhatsApp::markApproved('expiring_7');
        Member::find(3)->update(['whatsapp_optin' => false]);
        $res = AutoReminders::run();
        $this->assertSame(2, $res['sent'], 'Dilshan has not agreed, so only two are sent');
        $this->assertFalse(MessageLog::where('member_id', 3)->exists());

        Setting::put('wa_auto_expiring_7', '0', false);
        Http::fake($this->ok('wamid.2'));
        $this->assertSame(0, AutoReminders::run()['sent'], 'switched off');
        Setting::put('wa_mode', 'off', false);
        $this->assertSame(0, AutoReminders::run()['sent']);
    }

    public function test_a_failure_is_logged_retried_and_then_given_up_on(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'Template does not exist', 'code' => 132001]], 400)]);
        $this->connect('live');
        $r = AutoReminders::run();
        $this->assertSame(3, $r['failed']);
        $log = MessageLog::where('member_id', 3)->first();
        $this->assertSame('failed', $log->status);
        $this->assertStringContainsString('Template does not exist', $log->error);
        $this->assertSame(0, Reminder::count());
        AutoReminders::run(); AutoReminders::run();                                // attempts 2 and 3
        $this->assertSame(3, MessageLog::where(['member_id' => 3, 'status' => 'failed'])->count());
        $this->assertSame(0, AutoReminders::run()['failed'], 'after three tries it stops');
    }

    public function test_check_ins_birthdays_and_welcomes_are_never_automatic(): void
    {
        Setting::put('wa_mode', 'practice', false);
        foreach (['quiet', 'birthday', 'welcome', 'expired_30', 'enquiry', 'plan_ready'] as $k) $this->assertArrayNotHasKey($k, WhatsApp::AUTO);
        $m = Member::find(1);
        $m->update(['dob' => today()->subYears(30), 'joined_on' => today()]);
        Member::find(1)->attendances()->delete();
        $keys = collect(AutoReminders::dueFor($m->fresh()))->pluck(0)->all();
        $this->assertSame([], array_values(array_intersect($keys, ['quiet', 'birthday', 'welcome'])));
        foreach (array_keys(WhatsApp::AUTO) as $k) Setting::put('wa_auto_'.$k, '1', false);
        $this->assertSame([], array_values(array_intersect(collect(AutoReminders::plan()['send'])->pluck('key')->all(), ['quiet', 'birthday', 'welcome'])));
    }

    public function test_a_receipt_is_sent_right_after_a_payment_when_switched_on(): void
    {
        $this->actingAs($this->owner());
        Http::fake($this->ok('wamid.REC'));
        $m = Member::find(4);                                                         // Tharushi owes money
        $ms = $m->currentMembership;
        $pay = fn (array $over = []) => $this->post(route('admin.payments.store'), $over + ['member_id' => $m->id, 'membership_id' => $ms->id, 'amount' => 500, 'method' => 'cash', 'paid_on' => today()->toDateString()])->assertRedirect();

        $pay();                                                                       // everything off: nothing happens
        Http::assertNothingSent();
        $this->assertSame(0, MessageLog::where('key', 'payment')->count());

        $this->connect('live', ['payment']);
        $pay();
        $p = \App\Models\Payment::latest('id')->first();
        Http::assertSent(function ($req) use ($p) {
            $d = $req->data();
            return ($d['to'] ?? '') === '94770000004' && $d['template']['name'] === WhatsApp::templateName('payment')
                && array_column($d['template']['components'][0]['parameters'], 'text') === ['Tharushi', 'Rs. 500', today()->format('d M Y'), $p->receipt_no];
        });
        $this->assertSame('sent', MessageLog::where('key', 'payment')->latest('id')->first()->status);

        Member::find(4)->update(['whatsapp_optin' => false]);                         // no consent: the payment still saves, no message
        $count = count(Http::recorded());
        $pay();
        $this->assertCount($count, Http::recorded());

        Member::find(4)->update(['whatsapp_optin' => true]);
        $pay(['paid_on' => today()->subDays(3)->toDateString()]);                      // an old payment entered later: no message
        $this->assertCount($count, Http::recorded());

        Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['message' => 'boom', 'code' => 1]], 500)]);
        $before = \App\Models\Payment::count();
        $pay();                                                                       // WhatsApp down: the payment is still recorded
        $this->assertSame($before + 1, \App\Models\Payment::count());
    }

    public function test_practice_mode_receipts_are_recorded_but_not_sent_and_unapproved_wording_waits(): void
    {
        $this->actingAs($this->owner());
        Http::fake();
        $this->connect('practice', ['payment']);
        $this->post(route('admin.payments.store'), ['member_id' => 4, 'membership_id' => Member::find(4)->currentMembership->id, 'amount' => 100, 'method' => 'cash', 'paid_on' => today()->toDateString()]);
        Http::assertNothingSent();
        $this->assertSame('practice', MessageLog::where('key', 'payment')->first()->status);

        MessageLog::query()->delete();
        Setting::where('key', 'wa_templates')->delete();                              // forget the approval from above
        $this->connect('live', ['payment'], approve: false);
        $this->post(route('admin.payments.store'), ['member_id' => 4, 'membership_id' => Member::find(4)->currentMembership->id, 'amount' => 100, 'method' => 'cash', 'paid_on' => today()->toDateString()]);
        Http::assertNothingSent();
        $this->assertSame(0, MessageLog::count(), 'not approved yet, so nothing is attempted');
    }

    public function test_an_announcement_can_also_go_out_on_whatsapp_to_active_members_who_agreed(): void
    {
        $this->actingAs($this->owner());
        Http::fake($this->ok('wamid.ANN'));
        $form = ['title' => 'Closed on Friday', 'body' => "We are closed for the holiday.\nOpen again Saturday."];

        // not switched on: the checkbox is replaced by a pointer to the settings
        $this->get(route('admin.announcements.create'))->assertOk()->assertDontSee('Also send on WhatsApp')->assertSee('not ready for announcements');
        $this->post(route('admin.announcements.store'), $form + ['whatsapp' => 1])->assertRedirect()->assertSessionHas('warn');
        Http::assertNothingSent();

        $this->connect('live', ['announcement']);
        Member::find(2)->update(['whatsapp_optin' => false]);                         // has not agreed
        $expected = AutoReminders::announcementRecipients();
        $this->assertFalse($expected->contains('id', 2));
        $this->assertFalse($expected->contains('id', 10), 'a frozen member is not messaged');
        $this->assertTrue($expected->contains('id', 1));
        $this->get(route('admin.announcements.create'))->assertSee('Also send on WhatsApp')->assertSee('active members who agreed');

        $this->post(route('admin.announcements.store'), $form)->assertRedirect();     // posted without ticking the box: nothing sent
        Http::assertNothingSent();

        $this->post(route('admin.announcements.store'), ['title' => 'Closed on Friday 10 Oct.'] + $form + ['whatsapp' => 1])->assertRedirect()->assertSessionHas('ok', fn ($m) => str_contains($m, 'WhatsApp: '.$expected->count().' sent'));
        Http::assertSentCount($expected->count());
        Http::assertSent(function ($req) {
            $p = array_column($req->data()['template']['components'][0]['parameters'], 'text');
            return $req->data()['template']['name'] === WhatsApp::templateName('announcement') && $p[2] === 'Closed on Friday 10 Oct. We are closed for the holiday. Open again Saturday.';
        });
        $this->assertSame($expected->count(), MessageLog::where('key', 'announcement')->where('status', 'sent')->count());
        $this->assertStringContainsString('a notice from', MessageLog::where('key', 'announcement')->first()->text);
    }

    public function test_announcement_text_is_one_clean_line_and_has_a_limit(): void
    {
        $this->assertSame('Closed. Back on Monday.', AutoReminders::announcementText('Closed.', "Back\n\n  on   Monday."));
        $this->assertLessThanOrEqual(480, mb_strlen(AutoReminders::announcementText('T', str_repeat('word ', 300))));
    }

    public function test_submitting_templates_and_reading_their_status(): void
    {
        Http::fake([
            'graph.facebook.com/*/5551234/message_templates*' => function ($req) {
                if ($req->method() === 'POST') return Http::response(['id' => '1', 'status' => 'PENDING', 'category' => 'UTILITY'], 200);
                return Http::response(['data' => [['name' => WhatsApp::templateName('expiring_7'), 'status' => 'APPROVED'], ['name' => 'other_template', 'status' => 'APPROVED']]], 200);
            },
        ]);
        $this->connect('practice', [], approve: false);
        $res = WhatsApp::submit('expiring_7');
        $this->assertTrue($res['ok']);
        Http::assertSent(function ($req) {
            $d = $req->data();
            return $req->method() === 'POST' && str_contains($req->url(), '/5551234/message_templates')
                && $d['name'] === WhatsApp::templateName('expiring_7') && $d['category'] === 'UTILITY' && $d['language'] === 'en'
                && str_contains($d['components'][0]['text'], 'Hi {{1}}')
                && $d['components'][1] === ['type' => 'FOOTER', 'text' => WhatsApp::FOOTER] && count($d['components'][0]['example']['body_text'][0]) === 5;
        });
        $this->assertSame('pending', WhatsApp::status('expiring_7'));
        $this->assertSame(['expiring_7', 'expiring_2', 'expiring_0', 'expired_3', 'owing', 'payment', 'announcement'], array_keys(WhatsApp::AUTO));

        $this->assertTrue(WhatsApp::syncStatuses()['ok']);
        $this->assertSame('approved', WhatsApp::status('expiring_7'));
    }

    public function test_the_system_only_sends_it_has_no_incoming_address_and_messages_never_ask_for_a_reply(): void
    {
        // nothing can call us: the old webhook address does not exist, for GET (verification) or POST (receipts, replies)
        $this->get('/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=x&hub_challenge=1')->assertNotFound();
        $this->postJson('/webhooks/whatsapp', ['entry' => []])->assertNotFound();
        $this->assertFileDoesNotExist(app_path('Http/Controllers/WhatsAppWebhookController.php'));

        // every message the system can send automatically points to a phone call or a visit, never to "reply"
        foreach (array_keys(WhatsApp::AUTO) as $k) {
            $this->assertDoesNotMatchRegularExpression('/reply|respond|send us a message/i', \App\Support\Messages::default($k), $k);
        }
        $this->assertStringContainsString('{phone}', \App\Support\Messages::default('expiring_7'));
        $this->assertStringContainsString('tell us at the gym', WhatsApp::FOOTER);

        // the only statuses are sent, failed and practice
        $this->actingAs($this->owner())->get(route('admin.automation'))->assertOk()
            ->assertSee('Send only')->assertDontSee('Webhook address')->assertDontSee('Verify token')->assertDontSee('App secret')->assertDontSee('replying STOP');
    }

    public function test_control_panel_saves_settings_keeps_the_token_secret_and_refuses_live_without_details(): void
    {
        $this->actingAs($this->owner());
        $this->get(route('admin.automation'))->assertOk()->assertSee('Automatic sending')->assertSee('Choose how it runs')->assertSee('Send all for approval');

        $this->put(route('admin.automation.save'), ['mode' => 'live', 'hour' => 9])->assertSessionHasErrors('mode');
        $this->assertSame('off', WhatsApp::mode());

        $this->put(route('admin.automation.save'), ['mode' => 'live', 'hour' => 8, 'phone_id' => '1098765', 'waba_id' => '5551234', 'token' => 'secret-token-123', 'auto' => ['expiring_7' => 1]])->assertSessionHas('ok');
        $this->assertSame('live', WhatsApp::mode());
        $this->assertSame(8, WhatsApp::hour());
        $this->assertSame('secret-token-123', WhatsApp::token());
        $this->assertNotSame('secret-token-123', Setting::get('wa_token'), 'stored encrypted');
        $this->assertTrue(WhatsApp::autoOn('expiring_7'));
        $this->assertFalse(WhatsApp::autoOn('quiet'));
        $this->get(route('admin.automation'))->assertDontSee('secret-token-123');

        $this->put(route('admin.automation.save'), ['mode' => 'practice', 'hour' => 8, 'phone_id' => '1098765', 'waba_id' => '5551234', 'token' => ''])->assertSessionHas('ok');
        $this->assertSame('secret-token-123', WhatsApp::token(), 'an empty box keeps the saved token');
    }

    public function test_test_connection_run_now_and_consent_buttons(): void
    {
        $this->actingAs($this->owner());
        Http::fake(['graph.facebook.com/*/1098765*' => Http::response(['display_phone_number' => '+94 77 000 0000', 'verified_name' => 'FitnessWalkers', 'quality_rating' => 'GREEN'], 200)]);
        $this->connect('practice');
        $this->post(route('admin.automation.test'))->assertSessionHas('ok', fn ($m) => str_contains($m, 'FitnessWalkers') && str_contains($m, '+94 77 000 0000'));
        $this->post(route('admin.automation.run'))->assertSessionHas('ok', fn ($m) => str_contains($m, 'Practice run'));

        $this->post(route('admin.automation.consent'), ['optin' => [1, 2]])->assertSessionHas('ok');
        $this->assertSame(2, Member::where('whatsapp_optin', true)->count());
        $this->assertNotNull(Member::find(1)->whatsapp_optin_at);
        $this->get(route('admin.automation'))->assertOk()->assertSee('Kasun Perera');
    }

    public function test_the_command_lists_runs_and_only_acts_in_the_chosen_hour(): void
    {
        Http::fake($this->ok());
        $this->connect('live');
        $this->artisan('reminders:send', ['--list' => true])->expectsOutputToContain('would send  expiring_7')->assertSuccessful();
        Http::assertNothingSent();
        Setting::put('wa_hour', (string) ((now()->hour + 5) % 24), false);
        $this->artisan('reminders:send')->assertSuccessful();
        Http::assertNothingSent();                                                  // wrong hour: quiet
        $this->artisan('reminders:send', ['--force' => true])->expectsOutputToContain('live mode: 3 sent')->assertSuccessful();
        $this->assertNotNull(Setting::get('wa_last_run'));
    }

    public function test_joining_form_records_consent_and_members_see_it(): void
    {
        $this->actingAs($this->owner());
        $base = ['phone' => '0771234567', 'password' => 'secret123', 'joined_on' => today()->toDateString(), 'health_form' => 1];
        $this->post(route('admin.members.store'), $base + ['name' => 'Agrees', 'email' => 'agrees@x.test', 'whatsapp_optin' => 1])->assertRedirect();
        $this->post(route('admin.members.store'), $base + ['name' => 'Declines', 'email' => 'declines@x.test'])->assertRedirect();
        $a = Member::whereHas('user', fn ($u) => $u->where('email', 'agrees@x.test'))->first();
        $d = Member::whereHas('user', fn ($u) => $u->where('email', 'declines@x.test'))->first();
        $this->assertTrue((bool) $a->whatsapp_optin);
        $this->assertNotNull($a->whatsapp_optin_at);
        $this->assertFalse((bool) $d->whatsapp_optin);
        $this->get(route('admin.members.edit', $a))->assertOk()->assertSee('They said yes to WhatsApp messages');
    }
}
