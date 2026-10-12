<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, DietPlan, Member};
use Illuminate\Http\Request;

class DietController extends Controller
{
    public function index()
    {
        return view('admin.diets.index', ['plans' => DietPlan::with('member.user')->latest()->get()]);
    }

    public function show(DietPlan $diet)
    {
        $diet->load('member.user');
        return view('admin.diets.show', ['diet' => $diet]);
    }

    public function create(Request $r)
    {
        return view('admin.diets.create', [
            'members' => Member::with('user')->get()->sortBy(fn ($m) => $m->user->name)->values(), 'selected' => $r->query('member'),
        ]);
    }

    /** An owner-written plan replaces whatever plan the member was using (the app plan stays in history). */
    public function store(Request $r)
    {
        $d = $r->validate(['member_id' => 'required|exists:members,id', 'title' => 'required|max:120', 'owner_text' => 'required|max:6000']);
        DietPlan::where('member_id', $d['member_id'])->update(['active' => false]);
        $p = DietPlan::create($d + ['source' => 'owner', 'active' => true]);
        AuditLog::record('Wrote diet plan for member', Member::find($d['member_id'])->member_no, $d['title']);
        return redirect()->route('admin.diets.show', $p)->with('ok', 'Saved. The member now sees this plan instead of any app-made plan.');
    }

    public function destroy(DietPlan $diet)
    {
        AuditLog::record('Deleted diet plan', $diet->member->member_no, $diet->title);
        $diet->delete();
        return redirect()->route('admin.diets.index')->with('ok', 'Diet plan deleted.');
    }
}
