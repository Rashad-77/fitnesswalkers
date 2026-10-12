<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Member, RenewalRequest};

/** The Renewals tab: renewal requests from members, and who is about to expire or has just expired. */
class ReportController extends Controller
{
    public function renewals()
    {
        $members = Member::with(['user', 'currentMembership.plan'])->get();

        $expiring = $members->filter(fn ($m) => $m->status() === 'expiring')->sortBy(fn ($m) => $m->daysLeft())
            ->map(fn ($m) => ['m' => $m, 'detail' => ($m->daysLeft() === 0 ? 'Ends today' : 'Ends in '.$m->daysLeft().' days').' · '.$m->currentMembership->plan->name])->values();

        $expired = $members->filter(fn ($m) => $m->status() === 'expired' && $m->daysLeft() >= -60)->sortByDesc(fn ($m) => $m->daysLeft())
            ->map(fn ($m) => ['m' => $m, 'detail' => 'Expired '.abs($m->daysLeft()).' days ago'])->values();

        return view('admin.renewals', [
            'groups' => [
                'expiring' => ['title' => 'Expiring this week', 'rows' => $expiring, 'empty' => 'No memberships expiring this week.'],
                'expired' => ['title' => 'Expired in the last 60 days', 'rows' => $expired, 'empty' => 'No recent expiries.'],
            ],
            'requests' => RenewalRequest::with(['member.user', 'plan'])->where('status', 'pending')->latest()->get(),
        ]);
    }

    public function renewalDone(RenewalRequest $renewalRequest)
    {
        $renewalRequest->update(['status' => 'done']);
        \Illuminate\Support\Facades\Cache::forget('admin_nav_counts');
        AuditLog::record('Closed renewal request', $renewalRequest->member->member_no);
        return back()->with('ok', 'Marked as done.');
    }
}
