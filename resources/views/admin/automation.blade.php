@extends('layouts.admin')
@section('title', 'Automatic sending')
@section('content')
@php
  $modes = ['off' => ['Off', 'Nothing is sent.'], 'practice' => ['Practice', 'Works out who would get what each day and records it, but contacts nobody. Use this first.'], 'live' => ['Live', 'Messages really go out to members.']];
  $stale = $mode !== 'off' && (! $lastRun || $lastRun->lt(now()->subHours(36)));
  $statusLabel = ['none' => 'Not sent to WhatsApp', 'pending' => 'Waiting for WhatsApp', 'approved' => 'Approved', 'rejected' => 'Rejected'];
  $dueMsg = fn ($k) => \App\Support\Messages::all()[$k][0];
@endphp
<div class="pagehead"><div><h1>Automatic sending</h1>
  <p>WhatsApp messages sent by the system, for three things only: membership reminders, receipts and announcements. Nobody gets the same message twice.</p></div>
  <span class="b {{ $mode === 'live' ? 'active' : ($mode === 'practice' ? 'pending' : 'none') }}">{{ $modes[$mode][0] }}</span></div>

<div class="alert info"><strong>What goes out by itself.</strong> <b>Reminders</b> run once a day (a week before, two days before, the day a membership ends, just after it ended, and money still owed). A <b>receipt</b> goes out the moment you record a payment. An <b>announcement</b> goes out when you tick "Also send on WhatsApp" while posting it. Everything else, such as check-ins, birthdays and welcomes, stays a one-tap message you send yourself.<br><strong>Send only.</strong> The system sends messages and never receives anything from WhatsApp. "Sent" means WhatsApp accepted the message; it cannot show whether it was delivered or read. Replies to the sending number are not collected, so the messages ask members to call or visit instead.</div>

@if ($stale)<div class="alert warn">The daily job has not run in the last day and a half, so nothing is going out. Ask your web host to run <code>php artisan schedule:run</code> every minute (step 6 in the README). {{ $lastRun ? 'Last run: '.$lastRun->diffForHumans().'.' : 'It has never run.' }}</div>@endif

<section class="card ledger mb" aria-label="Last 30 days">
  <div class="lg"><div class="k">Sent, last 30 days</div><div class="v">{{ $sent30 }}</div><div class="note">{{ $mode === 'practice' ? $practice30.' rehearsed in practice mode' : 'Accepted by WhatsApp' }}</div></div>
  <div class="lg"><div class="k">Failed</div><div class="v {{ $failed30 ? 'warnv' : '' }}">{{ $failed30 }}</div><div class="note">See the activity list below</div></div>
  <div class="lg"><div class="k">Due today</div><div class="v">{{ count($plan['send']) }}</div><div class="note">{{ count($plan['waiting']) }} held back</div></div>
  <div class="lg"><div class="k">Last daily run</div><div class="v" style="font-size:1.25rem">{{ $lastRun ? $lastRun->diffForHumans() : 'Never' }}</div><div class="note">Runs at {{ sprintf('%02d:00', $hour) }}</div></div>
</section>

<form method="post" action="{{ route('admin.automation.save') }}" data-noguard>@csrf @method('PUT')
  <section class="card mb"><h2>1. Choose how it runs</h2>
    <div class="opts three" role="radiogroup" aria-label="Mode">
      @foreach ($modes as $k => [$t, $d])<label class="opt"><input type="radio" name="mode" value="{{ $k }}" @checked(old('mode', $mode) === $k)><span><b>{{ $t }}</b><small>{{ $d }}</small></span></label>@endforeach
    </div>
    <div class="grid g3 mt">
      <div class="f"><label for="hour">Send at (hour of the day)</label><select id="hour" name="hour">@for ($h = 6; $h <= 20; $h++)<option value="{{ $h }}" @selected($hour === $h)>{{ date('g A', mktime($h)) }}</option>@endfor</select></div>
    </div>
  </section>

  <section class="card mb"><h2>2. Connect your WhatsApp Business number <span class="b {{ $connected ? 'active' : 'none' }}">{{ $connected ? 'Details saved' : 'Not connected' }}</span></h2>
    <details class="howto"><summary>Where do I find these? (about 20 minutes, one time)</summary>
      <ol class="steps">
        <li>Go to <strong>developers.facebook.com</strong>, create an app of type <em>Business</em>, and add the <em>WhatsApp</em> product.</li>
        <li>Under <em>WhatsApp, API Setup</em> you will see the <strong>Phone number ID</strong> and the <strong>WhatsApp Business Account ID</strong>. Add and verify the gym's number there.</li>
        <li>In <em>Business settings, System users</em>, create a system user, give it your app and the permissions <em>whatsapp_business_messaging</em> and <em>whatsapp_business_management</em>, then generate a <strong>permanent access token</strong>. (The temporary token on the API Setup page stops working after a day.)</li>
        <li>In <em>WhatsApp Manager, Payment methods</em>, add a card. Meta charges per message and will not deliver without one.</li>
        <li>Paste the values below and press <strong>Save</strong>, then <strong>Test connection</strong>. That is all the setup there is: the system only ever sends, so there is no webhook or incoming address to set up.</li>
      </ol><p class="small muted">Meta changes the layout of these screens from time to time. The names above are what to look for.</p></details>
    <div class="grid g2 mt">
      <div class="f"><label for="phone_id">Phone number ID</label><input id="phone_id" type="text" name="phone_id" inputmode="numeric" value="{{ old('phone_id', $phoneId) }}" placeholder="e.g. 109876543210987"></div>
      <div class="f"><label for="waba_id">WhatsApp Business Account ID</label><input id="waba_id" type="text" name="waba_id" inputmode="numeric" value="{{ old('waba_id', $wabaId) }}" placeholder="e.g. 123456789012345"></div>
      <div class="f"><label for="token">Permanent access token</label><input id="token" type="password" name="token" autocomplete="off" placeholder="{{ $hasToken ? 'Saved. Type a new one only to replace it.' : 'Paste the token here' }}"></div>
      <div class="f"><label for="lang">Message language code</label><input id="lang" type="text" name="lang" value="{{ old('lang', $lang) }}" placeholder="en" style="max-width:120px"></div>
    </div>
    <div class="row mt"><button class="btn">Save settings</button>
      <button class="btn ghost" formaction="{{ route('admin.automation.test') }}">Test connection</button></div>
  </section>

  <section class="card mb"><h2>3. Pick the messages that go out by themselves</h2>
    <p class="small muted" style="margin-top:0">WhatsApp only lets a business start a chat with a message it has approved first. Press <strong>Send all for approval</strong> once, then <strong>Check status</strong> a few minutes later. If you change the wording in Settings, Messages, it needs approval again (a new code appears in the "Approval code" column).</p>
    <div class="autolist">
      @foreach ($rows as $r)
        <div class="autorow">
          <div class="ar-main"><label class="tg" style="display:inline-block"><input type="checkbox" name="auto[{{ $r['key'] }}]" value="1" @checked(old('auto.'.$r['key'], $r['on']))><span style="text-transform:none">{{ $r['title'] }}</span></label>
            <div class="small muted" style="margin-top:.3rem">{{ $r['when'] }} <span title="{{ $r['category'] === 'MARKETING' ? 'WhatsApp treats friendly check-ins as marketing messages, which cost more per message than reminders.' : 'Reminders about a membership or payment are cheaper utility messages.' }}">&middot; {{ $r['category'] === 'MARKETING' ? 'Marketing rate' : 'Utility rate' }}</span></div></div>
          <div class="ar-state"><span class="b {{ ['approved' => 'active', 'pending' => 'pending', 'rejected' => 'expired', 'none' => 'none'][$r['status']] ?? 'none' }}">{{ $statusLabel[$r['status']] ?? $r['status'] }}</span>
            @if ($r['error'])<div class="small" style="color:var(--chilli-d);margin-top:.3rem">{{ $r['error'] }}</div>@endif
            <details class="small" style="margin-top:.3rem"><summary class="muted" style="cursor:pointer">Approval code and text</summary><div style="margin-top:.3rem"><code>{{ $r['name'] }}</code></div><div class="wabubble" style="margin-top:.3rem">{{ $r['body'] }}</div></details></div>
          <div class="ar-act">
            @if ($r['status'] === 'none' || $r['status'] === 'rejected')<button class="btn ghost sm" formaction="{{ route('admin.automation.submit') }}" name="key" value="{{ $r['key'] }}">Send for approval</button>@endif
            @if ($r['status'] !== 'approved')<button class="btn ghost sm" formaction="{{ route('admin.automation.approve') }}" name="key" value="{{ $r['key'] }}" data-confirm="Only do this if you already created this exact message in WhatsApp Manager and it shows as Approved.">Mark approved</button>@endif
          </div>
        </div>
      @endforeach
    </div>
    <div class="row mt"><button class="btn">Save choices</button>
      <button class="btn ghost" formaction="{{ route('admin.automation.submit-all') }}">Send all for approval</button>
      <button class="btn ghost" formaction="{{ route('admin.automation.refresh') }}">Check status</button></div>
  </section>
</form>

<section class="card mb"><div class="charthead"><div><h2>4. Reminders due today</h2><p>(Receipts and announcements are sent when you record or post them.){{ count($plan['send']) }} ready{{ $plan['no_consent'] ? ', '.$plan['no_consent'].' skipped because the member has not agreed to WhatsApp' : '' }}.</p></div>
    <form method="post" action="{{ route('admin.automation.run') }}">@csrf<button class="btn" {{ $mode === 'off' ? 'disabled' : '' }} data-confirm="{{ $mode === 'live' ? 'Send these messages now?' : 'Run a practice pass now? Nothing will be sent.' }}">{{ $mode === 'live' ? 'Send now' : 'Run a practice pass' }}</button></form></div>
  @forelse ($plan['send'] as $row)
    <div class="pay"><span><a href="{{ route('admin.members.show', $row['member']) }}">{{ $row['member']->user->name }}</a><span class="sub">{{ $dueMsg($row['key']) }}</span></span><span class="b pending">Ready</span></div>
  @empty<div class="empty">Nothing is due right now.</div>@endforelse
  @foreach ($plan['waiting'] as $row)
    <div class="pay"><span><a href="{{ route('admin.members.show', $row['member']) }}">{{ $row['member']->user->name }}</a><span class="sub">{{ $dueMsg($row['key']) }}: {{ $row['why'] }}</span></span><span class="b none">Held back</span></div>
  @endforeach
</section>

<section class="card mb"><h2>5. Who may receive messages</h2>
  <p class="small muted" style="margin-top:0">Only members you tick get automatic messages. Tick someone only if they told you they are happy to get WhatsApp messages from the gym. Every message says to tell the gym to stop them, so if a member asks, untick them here.</p>
  <form method="post" action="{{ route('admin.automation.consent') }}" data-noguard>@csrf
    <label class="small" style="display:inline-flex;gap:.5rem;align-items:center;margin-bottom:.6rem"><input type="checkbox" id="tickall"> Tick everyone shown</label>
    <div class="consent">
      @foreach ($members as $m)
        <label class="chip"><input type="checkbox" name="optin[]" value="{{ $m->id }}" @checked($m->whatsapp_optin) @disabled(! \App\Services\WhatsApp::number($m))> {{ $m->user->name }} <span class="n">{{ \App\Services\WhatsApp::number($m) ? '' : 'no phone' }}</span></label>
      @endforeach
    </div>
    <button class="btn mt">Save who may receive messages</button>
  </form></section>

<section class="card"><h2>6. Activity</h2>
  <div class="tw"><table>
    <thead><tr><th>When</th><th>Member</th><th>Message</th><th>Result</th></tr></thead>
    <tbody>
    @forelse ($logs as $l)
      <tr><td class="nowrap small">{{ $l->created_at->format('d M, H:i') }}</td>
        <td>@if ($l->member)<a href="{{ route('admin.members.show', $l->member) }}">{{ $l->member->user->name }}</a>@else<span class="muted">Removed</span>@endif</td>
        <td class="small">{{ \App\Support\Messages::all()[$l->key][0] ?? $l->key }}<details><summary class="muted" style="cursor:pointer">Text</summary>{{ $l->text }}</details></td>
        <td><span class="b {{ ['sent' => 'active', 'failed' => 'expired', 'practice' => 'none'][$l->status] ?? 'none' }}">{{ ['sent' => 'Sent', 'failed' => 'Failed', 'practice' => 'Practice only'][$l->status] ?? $l->status }}</span>
          @if ($l->error)<div class="small" style="color:var(--chilli-d)">{{ $l->error }}</div>@endif</td></tr>
    @empty<tr><td colspan="4" class="empty">Nothing has been sent yet.</td></tr>@endforelse
    </tbody>
  </table></div></section>
@push('scripts')
<script>
document.getElementById('tickall').addEventListener('change', function (e) { document.querySelectorAll('.consent input:not(:disabled)').forEach(function (i) { i.checked = e.target.checked; }); });
</script>
@endpush
@endsection
