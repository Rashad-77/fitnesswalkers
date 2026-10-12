<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use Illuminate\Http\Request;

/** Feeds the live "Find a member" box in the owner's top bar. */
class SearchController extends Controller
{
    public function members(Request $r)
    {
        $q = trim((string) $r->query('q'));
        if (mb_strlen($q) < 2) return response()->json([]);
        $like = '%'.$q.'%';
        $members = Member::with(['user', 'currentMembership'])
            ->where(fn ($w) => $w->where('member_no', 'like', $like)
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like)))
            ->limit(8)->get();
        return response()->json($members->map(fn ($m) => [
            'name' => $m->user->name, 'initials' => initials($m->user->name), 'no' => $m->member_no, 'phone' => $m->user->phone,
            'status' => $m->status(), 'url' => route('admin.members.show', $m),
        ])->values());
    }
}
