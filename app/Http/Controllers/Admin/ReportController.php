<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Member, Membership, Reminder, RenewalRequest, Setting};
use Illuminate\Http\Request;

/** The Reminders tab: everyone the owner should message today, with a ready WhatsApp text and a "sent" tick. */
class ReportController extends Controller
{
    public function renewals()
    {
        $gym = Setting::get('gym_name', 'FitnessWalkers');
        $members = Member::with(['user', 'currentMembership.plan', 'memberships.payments'])->get();
        $sent = Reminder::where('sent_on', '>=', today()->subDays(14))->get()
            ->groupBy(fn ($r) => $r->member_id.'|'.$r->kind)->map(fn ($g) => $g->max('sent_on'));

        $row = function (Member $m, string $kind, string $text, ?string $detail = null) use ($sent) {
            $when = $sent[$m->id.'|'.$kind] ?? null;
            return [
                'm' => $m, 'kind' => $kind, 'detail' => $detail, 'wa' => $m->whatsappUrl($text),
                'sent' => $when ? \Carbon\Carbon::parse($when) : null,
            ];
        };
        $ends = fn (Member $m) => $m->currentMembership->ends_on->format('d M Y');

        $expiring = $members->filter(fn ($m) => $m->status() === 'expiring')->sortBy(fn ($m) => $m->daysLeft())
            ->map(fn ($m) => $row($m, 'expiring', "Hi {$m->user->name}, this is {$gym}. Your membership expires on {$ends($m)}. Reply here or visit us to renew. Thank you!",
                ($m->daysLeft() === 0 ? 'Ends today' : 'Ends in '.$m->daysLeft().' days').' · '.$m->currentMembership->plan->name))->values();

        $expired = $members->filter(fn ($m) => $m->status() === 'expired' && $m->daysLeft() >= -60)->sortByDesc(fn ($m) => $m->daysLeft())
            ->map(fn ($m) => $row($m, 'expired', "Hi {$m->user->name}, this is {$gym}. Your membership expired on {$ends($m)}. We would love to see you back. Reply here or visit us to renew!",
                'Expired '.abs($m->daysLeft()).' days ago'))->values();

        $owing = $members->filter(fn ($m) => $m->balanceDue() > 0)->sortByDesc(fn ($m) => $m->balanceDue())
            ->map(fn ($m) => $row($m, 'owing', "Hi {$m->user->name}, a friendly reminder from {$gym}: Rs. ".number_format($m->balanceDue(), 2).' is still due on your membership. Thank you!',
                rs($m->balanceDue()).' due'))->values();

        $cut = now()->subDays(config('fitness.quiet_days'))->toDateString();
        $quiet = $members->filter(fn ($m) => in_array($m->status(), ['active', 'expiring']) && (! ($l = $m->lastVisit()) || $l < $cut))
            ->map(fn ($m) => $row($m, 'quiet', "Hi {$m->user->name}, we have missed you at {$gym}! Is everything okay? Come in this week and let's get back on track.",
                ($l = $m->lastVisit()) ? 'Last visit '.\Carbon\Carbon::parse($l)->diffForHumans() : 'No visits recorded'))->values();

        $birthdays = $members->filter(function ($m) {
            if (! $m->dob) return false;
            $next = $m->dob->copy()->year(today()->year);
            if ($next->lt(today())) $next->addYear();
            return today()->diffInDays($next) <= 7;
        })->map(fn ($m) => $row($m, 'birthday', "Happy birthday {$m->user->name}! From everyone at {$gym}. Have a great day!",
            $m->dob->format('d M')))->values();

        return view('admin.renewals', [
            'groups' => [
                'expiring' => ['title' => 'Expiring this week', 'rows' => $expiring, 'empty' => 'No memberships expiring this week.'],
                'expired' => ['title' => 'Expired in the last 60 days', 'rows' => $expired, 'empty' => 'No recent expiries.'],
                'owing' => ['title' => 'Outstanding balances', 'rows' => $owing, 'empty' => 'No outstanding balances.'],
                'quiet' => ['title' => 'Low attendance', 'rows' => $quiet, 'empty' => 'No members with low attendance.'],
                'birthday' => ['title' => 'Birthdays in the next 7 days', 'rows' => $birthdays, 'empty' => 'No birthdays this week.'],
            ],
            'requests' => RenewalRequest::with(['member.user', 'plan'])->where('status', 'pending')->latest()->get(),
        ]);
    }

    public function sent(Request $r)
    {
        $d = $r->validate(['member_id' => 'required|exists:members,id', 'kind' => 'required|in:expiring,expired,owing,quiet,birthday']);
        Reminder::create(['member_id' => $d['member_id'], 'kind' => $d['kind'], 'sent_on' => today()]);
        return back()->with('ok', 'Marked as sent.');
    }

    public function renewalDone(RenewalRequest $renewalRequest)
    {
        $renewalRequest->update(['status' => 'done']);
        \Illuminate\Support\Facades\Cache::forget('admin_nav_counts');
        AuditLog::record('Closed renewal request', $renewalRequest->member->member_no);
        return back()->with('ok', 'Marked as done.');
    }
}
