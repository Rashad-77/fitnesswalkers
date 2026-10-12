<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Member, Membership, Payment, Plan, User};
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public const FILTERS = [
        'all' => 'All', 'active' => 'Active', 'expiring' => 'Expiring', 'owes' => 'Outstanding balance', 'expired' => 'Expired',
        'frozen' => 'Frozen', 'requested' => 'Workout requested', 'quiet' => 'Low attendance',
    ];

    public function index(Request $r)
    {
        $q = trim((string) $r->query('q'));
        $filter = $r->query('filter') ?: ($r->query('status') ?: 'all');
        if (! isset(self::FILTERS[$filter])) $filter = 'all';

        $all = Member::with(['user', 'currentMembership.plan', 'memberships.payments'])
            ->when($q, fn ($x) => $x->where(fn ($w) => $w->where('member_no', 'like', "%$q%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$q%")->orWhere('phone', 'like', "%$q%")->orWhere('email', 'like', "%$q%"))))
            ->get()->sortBy(fn ($m) => mb_strtolower($m->user->name))->values();

        $requested = \App\Models\PlanRequest::where('status', 'pending')->pluck('member_id')->all();
        $cut = now()->subDays(config('fitness.quiet_days'))->toDateString();
        $last = $all->mapWithKeys(fn ($m) => [$m->id => $m->lastVisit()]);

        $match = fn ($m, $f) => match ($f) {
            'active' => in_array($m->status(), ['active', 'expiring']),
            'expiring' => $m->status() === 'expiring',
            'owes' => $m->balanceDue() > 0,
            'expired' => $m->status() === 'expired',
            'frozen' => $m->status() === 'frozen',
            'requested' => in_array($m->id, $requested),
            'quiet' => in_array($m->status(), ['active', 'expiring']) && (! $last[$m->id] || $last[$m->id] < $cut),
            default => true,
        };
        $counts = [];
        foreach (array_keys(self::FILTERS) as $f) $counts[$f] = $all->filter(fn ($m) => $match($m, $f))->count();
        $sort = $r->query('sort'); $dir = $r->query('dir') === 'desc' ? 'desc' : 'asc';
        $keys = [
            'no' => fn ($m) => $m->member_no, 'name' => fn ($m) => mb_strtolower($m->user->name), 'phone' => fn ($m) => (string) $m->user->phone,
            'package' => fn ($m) => mb_strtolower((string) $m->currentMembership?->plan->name), 'status' => fn ($m) => $m->status(),
            'ends' => fn ($m) => (string) $m->currentMembership?->ends_on?->format('Y-m-d'), 'visit' => fn ($m) => (string) ($last[$m->id] ?? ''), 'balance' => fn ($m) => $m->balanceDue(),
        ];
        if (! isset($keys[$sort])) { $sort = 'name'; }
        $members = $all->filter(fn ($m) => $match($m, $filter))->sortBy($keys[$sort], SORT_NATURAL, $dir === 'desc')->values();

        return view('admin.members.index', ['members' => $members, 'q' => $q, 'filter' => $filter, 'filters' => self::FILTERS, 'counts' => $counts, 'last' => $last, 'sort' => $sort, 'dir' => $dir]);
    }

    public function create(Request $r)
    {
        $member = new Member(['joined_on' => today()]);
        $enquiry = $r->query('enquiry') ? \App\Models\Enquiry::find($r->query('enquiry')) : null;
        return view('admin.members.form', ['member' => $member, 'plans' => Plan::where('active', true)->get(), 'enquiry' => $enquiry]);
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->rules() + [
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'plan_id' => 'nullable|exists:plans,id',
            'starts_on' => 'nullable|date',
            'initial_payment' => 'nullable|numeric|min:0',
            'method' => 'nullable|in:cash,bank,card',
            'enquiry_id' => 'nullable|exists:enquiries,id',
        ]);
        $user = User::create(['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null, 'password' => $d['password'], 'role' => 'member']);
        $member = Member::create(['user_id' => $user->id, 'member_no' => Member::nextNumber()] + $this->memberFields($d) + $this->healthFields($r));
        if (! empty($d['plan_id'])) {
            $this->createMembership($member, Plan::find($d['plan_id']), $d['starts_on'] ?? today()->toDateString(), null, (float) ($d['initial_payment'] ?? 0), $d['method'] ?? 'cash');
        }
        if (! empty($d['enquiry_id'])) {
            \App\Models\Enquiry::whereKey($d['enquiry_id'])->update(['status' => 'joined', 'member_id' => $member->id]);
        }
        \Illuminate\Support\Facades\Cache::forget('admin_nav_counts');
        AuditLog::record('Added member', $member->member_no, $user->name);
        return redirect()->route('admin.members.show', $member)->with('ok', 'Member added. Share their email and password with them.');
    }

    public function show(Request $r, Member $member)
    {
        $member->load(['user', 'memberships.plan', 'memberships.payments', 'payments.membership.plan', 'currentMembership.plan', 'workoutPlans', 'dietPlans', 'planRequests']);
        $tab = in_array($r->query('tab'), ['payments', 'workout', 'meals', 'progress']) ? $r->query('tab') : 'overview';
        $gym = \App\Models\Setting::get('gym_name', 'FitnessWalkers');
        $d = $member->daysLeft();
        $msg = "Hi {$member->user->name}, this is {$gym}.";
        return view('admin.members.show', [
            'member' => $member, 'tab' => $tab, 'plans' => Plan::where('active', true)->get(), 'lastVisit' => $member->lastVisit(),
            'bodyLogs' => $member->bodyLogs()->latest('logged_on')->take(8)->get(),
            'goals' => $member->goals()->latest()->take(6)->get(),
            'visits' => $member->attendances()->take(10)->get(),
            'wa' => $member->whatsappUrl($msg),
            'questions' => config('fitness.health_questions'),
        ]);
    }

    public function edit(Member $member)
    {
        $member->load('user');
        return view('admin.members.form', ['member' => $member, 'plans' => collect(), 'enquiry' => null]);
    }

    public function update(Request $r, Member $member)
    {
        $d = $r->validate($this->rules() + ['email' => ['required', 'email', Rule::unique('users', 'email')->ignore($member->user_id)]]);
        $member->user->update(['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null, 'is_dummy' => false]);
        $member->update($this->memberFields($d) + $this->healthFields($r, $member) + ['is_dummy' => false]);
        AuditLog::record('Edited member', $member->member_no, $member->user->name);
        return redirect()->route('admin.members.show', $member)->with('ok', 'Member updated.');
    }

    public function checkin(Member $member)
    {
        $new = \App\Models\Attendance::record($member, 'owner');
        return back()->with('ok', $new ? $member->user->name.' checked in.' : $member->user->name.' was already checked in today.');
    }

    /** Quick check-in from the Today page: type a name or member number. */
    public function quickCheckin(Request $r)
    {
        $v = trim((string) $r->input('who'));
        $m = null;
        if (preg_match('/FW-\d+/i', $v, $mm)) $m = Member::where('member_no', strtoupper($mm[0]))->first();
        if (! $m && $v !== '') $m = Member::whereHas('user', fn ($u) => $u->where('name', 'like', $v))->first();
        if (! $m) return back()->withErrors(['who' => 'No member found. Pick a name from the list.']);
        return $this->checkin($m);
    }

    public function destroy(Member $member)
    {
        AuditLog::record('Deleted member', $member->member_no, $member->user->name);
        $member->user->delete(); // cascades to the member and all their records
        return redirect()->route('admin.members.index')->with('ok', 'Member deleted.');
    }

    public function resetPassword(Request $r, Member $member)
    {
        $d = $r->validate(['password' => 'required|min:8']);
        $member->user->update(['password' => $d['password']]);
        AuditLog::record('Reset member password', $member->member_no);
        return back()->with('ok', 'Password reset. Tell the member their new password.');
    }

    public function toggleFreeze(Member $member)
    {
        if (! $member->frozen) {
            $member->update(['frozen' => true, 'frozen_at' => today()]);
            AuditLog::record('Froze membership', $member->member_no);
            return back()->with('ok', 'Membership frozen. The frozen days are added back when you unfreeze.');
        }
        // Unfreezing gives the frozen days back: the membership ends later by as many days as it was frozen.
        $days = $member->frozen_at ? (int) $member->frozen_at->diffInDays(today()) : 0;
        $ms = $member->currentMembership;
        if ($ms && $days > 0) $ms->update(['ends_on' => $ms->ends_on->copy()->addDays($days)]);
        $member->update(['frozen' => false, 'frozen_at' => null]);
        AuditLog::record('Unfroze membership', $member->member_no, $days > 0 ? $days.' days added back' : null);
        return back()->with('ok', 'Membership unfrozen.'.($ms && $days > 0 ? ' '.$days.' '.\Illuminate\Support\Str::plural('day', $days).' added back to the end date.' : ''));
    }

    public function addMembership(Request $r, Member $member)
    {
        $d = $r->validate([
            'plan_id' => 'required|exists:plans,id', 'starts_on' => 'required|date', 'price' => 'nullable|numeric|min:0',
            'initial_payment' => 'nullable|numeric|min:0', 'method' => 'nullable|in:cash,bank,card',
        ]);
        $plan = Plan::find($d['plan_id']);
        $this->createMembership($member, $plan, $d['starts_on'], isset($d['price']) ? (float) $d['price'] : null, (float) ($d['initial_payment'] ?? 0), $d['method'] ?? 'cash');
        $member->renewalRequests()->where('status', 'pending')->update(['status' => 'done']);
        AuditLog::record('Added membership', $member->member_no, $plan->name);
        return back()->with('ok', 'Membership added.');
    }

    private function createMembership(Member $member, Plan $plan, string $start, ?float $price, float $paid, string $method): Membership
    {
        $price ??= (float) $plan->price;
        $ms = Membership::create([
            'member_id' => $member->id, 'plan_id' => $plan->id, 'starts_on' => $start,
            'ends_on' => \Carbon\Carbon::parse($start)->addDays($plan->duration_days), 'price' => $price,
        ]);
        if ($paid > 0) {
            Payment::create(['receipt_no' => Payment::nextReceiptNo(), 'member_id' => $member->id, 'membership_id' => $ms->id,
                'amount' => min($paid, $price), 'method' => $method, 'paid_on' => today()]);
        }
        return $ms;
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:120', 'phone' => 'nullable|string|max:30',
            'dob' => 'nullable|date|before:today', 'sex' => 'nullable|in:male,female', 'height_cm' => 'nullable|numeric|between:80,250',
            'address' => 'nullable|string|max:255', 'emergency_contact' => 'nullable|string|max:255',
            'joined_on' => 'required|date', 'notes' => 'nullable|string|max:2000', 'health_notes' => 'nullable|string|max:2000',
        ];
    }

    private function memberFields(array $d): array
    {
        return collect($d)->only(['dob', 'sex', 'height_cm', 'address', 'emergency_contact', 'joined_on', 'notes', 'health_notes'])->all();
    }

    /** Health answers and the signed-terms tick from the joining form. */
    private function healthFields(Request $r, ?Member $member = null): array
    {
        $out = [];
        if ($r->has('health_form')) {
            $answers = [];
            foreach (array_keys(config('fitness.health_questions')) as $k) $answers[$k] = $r->boolean("health.$k");
            $out['health_answers'] = $answers;
            $out['health_flag'] = in_array(true, $answers, true);
        }
        if ($r->boolean('waiver') && ! $member?->waiver_accepted_at) $out['waiver_accepted_at'] = now();
        if ($member && $r->has('health_form') && ! $r->boolean('waiver')) $out['waiver_accepted_at'] = null;
        return $out;
    }
}
