<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Member, MessageLog, Setting};
use App\Services\{AutoReminders, WhatsApp};
use App\Support\Messages;
use Illuminate\Http\Request;

/** Settings > Automatic sending. */
class AutomationController extends Controller
{
    public function index()
    {
        $since = now()->subDays(30);
        $counts = MessageLog::where('created_at', '>=', $since)->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $rows = collect(WhatsApp::AUTO)->map(fn ($cat, $key) => [
            'key' => $key, 'title' => Messages::all()[$key][0], 'when' => Messages::all()[$key][1], 'category' => $cat,
            'on' => WhatsApp::autoOn($key), 'status' => WhatsApp::status($key), 'name' => WhatsApp::templateName($key),
            'body' => WhatsApp::convert(Messages::body($key))['body'], 'error' => WhatsApp::known()[WhatsApp::templateName($key)]['error'] ?? null,
        ])->values();
        $last = Setting::get('wa_last_run');
        return view('admin.automation', [
            'mode' => WhatsApp::mode(), 'connected' => WhatsApp::connected(), 'phoneId' => WhatsApp::phoneId(), 'wabaId' => WhatsApp::wabaId(),
            'hasToken' => WhatsApp::token() !== '', 'lang' => WhatsApp::language(), 'hour' => WhatsApp::hour(),
            'rows' => $rows,
            'plan' => AutoReminders::plan(), 'logs' => MessageLog::with('member.user')->latest()->take(40)->get(),
            'sent30' => (int) collect(MessageLog::DELIVERED)->sum(fn ($s) => $counts[$s] ?? 0), 'failed30' => (int) ($counts['failed'] ?? 0), 'practice30' => (int) ($counts['practice'] ?? 0),
            'members' => Member::with('user')->get()->sortBy(fn ($m) => mb_strtolower($m->user->name))->values(),
            'lastRun' => $last ? \Carbon\Carbon::parse($last) : null,
        ]);
    }

    public function save(Request $r)
    {
        $d = $r->validate([
            'mode' => 'required|in:off,practice,live', 'phone_id' => 'nullable|string|max:40', 'waba_id' => 'nullable|string|max:40',
            'token' => 'nullable|string|max:600', 'lang' => 'nullable|string|max:10', 'hour' => 'required|integer|between:0,23',
        ]);
        Setting::put('wa_phone_id', preg_replace('/\D+/', '', (string) ($d['phone_id'] ?? '')), false);
        Setting::put('wa_waba_id', preg_replace('/\D+/', '', (string) ($d['waba_id'] ?? '')), false);
        Setting::put('wa_lang', trim((string) ($d['lang'] ?? '')) ?: 'en', false);
        Setting::put('wa_hour', (string) $d['hour'], false);
        WhatsApp::putSecret('wa_token', $d['token'] ?? null);
        foreach (array_keys(WhatsApp::AUTO) as $k) Setting::put('wa_auto_'.$k, $r->boolean('auto.'.$k) ? '1' : '0', false);

        if ($d['mode'] === 'live' && ! WhatsApp::connected()) {
            Setting::put('wa_mode', WhatsApp::mode() === 'live' ? 'practice' : WhatsApp::mode(), false);
            return back()->withErrors(['mode' => 'Live needs the phone number ID and the access token. Everything else was saved; sending is still in "'.WhatsApp::mode().'" mode.']);
        }
        Setting::put('wa_mode', $d['mode'], false);
        AuditLog::record('Saved automatic sending settings', null, 'mode: '.$d['mode']);
        return back()->with('ok', 'Saved. Automatic sending is '.['off' => 'off', 'practice' => 'in practice mode (nothing is sent)', 'live' => 'live'][$d['mode']].'.');
    }

    public function test()
    {
        $r = WhatsApp::check();
        return $r['ok']
            ? back()->with('ok', 'Connected. Messages will be sent from '.$r['number'].' ('.$r['name'].'). Quality rating: '.strtolower((string) $r['quality']).'.')
            : back()->with('warn', 'Not connected. '.$r['error']);
    }

    public function submit(Request $r)
    {
        $key = $r->validate(['key' => 'required|in:'.implode(',', array_keys(WhatsApp::AUTO))])['key'];
        $res = WhatsApp::submit($key);
        return $res['ok']
            ? back()->with('ok', 'Sent to WhatsApp for review. It usually takes a few minutes, sometimes a day. Use "Check status" to see the answer.')
            : back()->with('warn', 'WhatsApp did not accept it. '.$res['error']);
    }

    public function submitAll()
    {
        $n = 0; $err = null;
        foreach (array_keys(WhatsApp::AUTO) as $k) {
            if (WhatsApp::status($k) !== 'none') continue;
            $res = WhatsApp::submit($k);
            $res['ok'] ? $n++ : $err ??= $res['error'];
        }
        return $err ? back()->with('warn', "$n sent for review. One was not accepted: $err") : back()->with('ok', $n ? "$n message(s) sent to WhatsApp for review." : 'Nothing new to send. Every message already has a status.');
    }

    public function refresh()
    {
        $res = WhatsApp::syncStatuses();
        return $res['ok'] ? back()->with('ok', 'Status updated for '.$res['count'].' message(s).') : back()->with('warn', $res['error']);
    }

    /** For templates created by hand in Meta's own screen under the exact name shown. */
    public function approve(Request $r)
    {
        $key = $r->validate(['key' => 'required|in:'.implode(',', array_keys(WhatsApp::AUTO))])['key'];
        WhatsApp::markApproved($key);
        AuditLog::record('Marked WhatsApp message as approved', $key);
        return back()->with('ok', 'Marked as approved.');
    }

    public function run()
    {
        $res = AutoReminders::run();
        if ($res['mode'] === 'off') return back()->with('warn', 'Automatic sending is switched off.');
        return back()->with('ok', ($res['mode'] === 'practice' ? 'Practice run: ' : '').$res['sent'].($res['mode'] === 'practice' ? ' would be sent' : ' sent').', '.$res['failed'].' failed, '.$res['waiting'].' held back.');
    }

    /** Who has agreed to automatic messages. */
    public function consent(Request $r)
    {
        $yes = array_map('intval', (array) $r->input('optin', []));
        foreach (Member::all() as $m) {
            $want = in_array($m->id, $yes, true);
            if ($want !== (bool) $m->whatsapp_optin) $m->update(['whatsapp_optin' => $want, 'whatsapp_optin_at' => $want ? now() : null]);
        }
        AuditLog::record('Updated who receives WhatsApp messages', null, count($yes).' members');
        return back()->with('ok', count($yes).' member(s) will receive automatic messages.');
    }
}
