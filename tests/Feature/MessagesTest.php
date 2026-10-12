<?php

namespace Tests\Feature;

use App\Models\{Enquiry, Member, Reminder, Setting, User};
use App\Support\Messages;
use Database\Seeders\DummySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The editable WhatsApp messages and the reminder queue. */
class MessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DummySeeder::class);
    }

    private function owner(): User { return User::where('role', 'admin')->first(); }

    public function test_placeholders_are_filled_and_unknown_ones_are_left_alone(): void
    {
        $this->assertSame('Hi Kasun, see you at FitnessWalkers {mystery}', Messages::fill('Hi {name}, see you at {gym} {mystery}', ['name' => 'Kasun', 'gym' => 'FitnessWalkers']));
        $m = Member::find(1);
        $text = Messages::forMember('expiring_7', $m);
        $this->assertStringContainsString('Kasun', $text);
        $this->assertStringContainsString($m->currentMembership->ends_on->format('d M Y'), $text);
        $this->assertStringNotContainsString('{', $text);
        $this->assertSame('expiring_0', Messages::expiringKey(0));
        $this->assertSame('expiring_2', Messages::expiringKey(2));
        $this->assertSame('expiring_7', Messages::expiringKey(5));
        $this->assertSame('expired_3', Messages::expiredKey(10));
        $this->assertSame('expired_30', Messages::expiredKey(40));
    }

    public function test_owner_edits_a_message_and_it_is_used_everywhere(): void
    {
        $this->actingAs($this->owner())->get(route('admin.messages'))->assertOk()->assertSee('A week before it ends')->assertSee('{name}');
        $all = collect(Messages::all())->map(fn ($v, $k) => Messages::body($k))->all();
        $all['owing'] = 'Hello {name}, please settle {amount}. Thanks, {gym}.';
        $this->put(route('admin.messages.save'), ['msg' => $all])->assertRedirect()->assertSessionHas('ok');
        $this->assertSame('Hello {name}, please settle {amount}. Thanks, {gym}.', Setting::get('msg_owing'));
        $this->assertNull(Setting::get('msg_welcome'), 'unchanged messages are not stored as overrides');

        $this->get(route('admin.renewals'))->assertOk()->assertSee('please settle Rs.', false)->assertSee('Send one by one');
        $this->get(route('admin.messages'))->assertSee('Edited');

        $this->post(route('admin.messages.reset'), ['reset' => 'owing'])->assertRedirect();
        $this->assertNull(Setting::get('msg_owing'));
        $this->assertStringContainsString('friendly reminder', Messages::body('owing'));
    }

    public function test_reminders_show_the_message_and_the_queue_can_record_a_send_as_json(): void
    {
        $this->actingAs($this->owner());
        $this->get(route('admin.renewals'))->assertOk()->assertSee('See the message')->assertSee('wa.me/', false);
        $m = Member::find(3);                                                // Dilshan is expiring
        $before = Reminder::count();
        $this->postJson(route('admin.reminders.sent'), ['member_id' => $m->id, 'kind' => 'expiring'])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame($before + 1, Reminder::count());
        $this->get(route('admin.renewals'))->assertSee('Sent today');
        $this->postJson(route('admin.reminders.sent'), ['member_id' => $m->id, 'kind' => 'bogus'])->assertStatus(422);
    }

    public function test_member_profile_offers_the_right_messages_and_enquiries_use_the_template(): void
    {
        $this->actingAs($this->owner());
        $this->get(route('admin.members.show', Member::find(3)))->assertOk()->assertSee('Renewal reminder')->assertSee('Welcome message')->assertSee('Change the wording');
        $this->get(route('admin.members.show', Member::find(4)))->assertSee('Payment reminder');      // Tharushi owes money
        $this->get(route('admin.members.show', Member::find(7)))->assertSee('Come back message');     // expired

        Setting::put('msg_enquiry', 'Hello {name}, custom enquiry words.', false);
        $e = Enquiry::first();
        $this->assertStringContainsString(rawurlencode('custom enquiry words'), $e->whatsappUrl());
        $this->assertStringContainsString(rawurlencode('Hello '.explode(' ', $e->name)[0]), $e->whatsappUrl());
    }

    public function test_only_the_owner_can_edit_messages(): void
    {
        $this->actingAs(Member::find(1)->user)->get(route('admin.messages'))->assertRedirect();
        $this->put(route('admin.messages.save'), ['msg' => ['owing' => 'hacked']])->assertRedirect();
        $this->assertNull(Setting::get('msg_owing'));
    }
}
