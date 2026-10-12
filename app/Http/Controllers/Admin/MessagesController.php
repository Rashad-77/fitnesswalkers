<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Setting};
use App\Support\Messages;
use Illuminate\Http\Request;

/** Settings > Messages: the owner edits the wording of every WhatsApp message. */
class MessagesController extends Controller
{
    public function index()
    {
        return view('admin.messages', ['all' => Messages::all(), 'groups' => Messages::GROUPS, 'vars' => Messages::VARS, 'sample' => Messages::sample()]);
    }

    public function save(Request $r)
    {
        $keys = array_keys(Messages::all());
        $d = $r->validate(collect($keys)->mapWithKeys(fn ($k) => ['msg.'.$k => 'nullable|string|max:700'])->all());
        foreach ($keys as $k) {
            $text = trim((string) ($d['msg'][$k] ?? ''));
            // Saving the default text clears the override, so a future better default still reaches you.
            if ($text === '' || $text === Messages::default($k)) Setting::where('key', 'msg_'.$k)->delete();
            else Setting::put('msg_'.$k, $text, false);
        }
        AuditLog::record('Edited WhatsApp messages');
        return back()->with('ok', 'Messages saved. They are used from now on.');
    }

    public function reset(Request $r)
    {
        $k = $r->validate(['reset' => 'required|in:'.implode(',', array_keys(Messages::all()))])['reset'];
        Setting::where('key', 'msg_'.$k)->delete();
        return back()->with('ok', 'That message is back to the original wording.');
    }
}
