<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Plan};
use Illuminate\Http\Request;

class PlanController extends Controller
{
    public function index()
    {
        return view('admin.plans.index', ['plans' => Plan::orderBy('duration_days')->get(),
            'counts' => \App\Models\Membership::where('ends_on', '>=', today())->selectRaw('plan_id, count(*) as n')->groupBy('plan_id')->pluck('n', 'plan_id')]);
    }

    public function create()
    {
        return view('admin.plans.form', ['plan' => new Plan(['active' => true])]);
    }

    public function store(Request $r)
    {
        $plan = Plan::create($this->data($r));
        $this->onlyOneFeatured($plan);
        AuditLog::record('Added membership plan', $plan->name, 'Rs. '.$plan->price);
        return redirect()->route('admin.plans.index')->with('ok', 'Plan added.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.form', compact('plan'));
    }

    public function update(Request $r, Plan $plan)
    {
        $plan->update($this->data($r) + ['is_dummy' => false]);
        $this->onlyOneFeatured($plan);
        AuditLog::record('Edited membership plan', $plan->name, 'Rs. '.$plan->price);
        return redirect()->route('admin.plans.index')->with('ok', 'Plan updated.');
    }

    public function destroy(Plan $plan)
    {
        if ($plan->id && \App\Models\Membership::where('plan_id', $plan->id)->exists()) {
            $plan->update(['active' => false]);
            return back()->with('ok', 'This plan has members, so it was switched off (hidden from new sign-ups) instead of deleted.');
        }
        AuditLog::record('Deleted membership plan', $plan->name);
        $plan->delete();
        return back()->with('ok', 'Plan deleted.');
    }

    private function data(Request $r): array
    {
        $d = $r->validate(['name' => 'required|max:80', 'duration_days' => 'required|integer|min:1|max:1000', 'price' => 'required|numeric|min:0',
            'tagline' => 'nullable|max:120', 'features' => 'nullable|max:1500']);
        $d['tagline'] = $d['tagline'] ?? null; $d['features'] = $d['features'] ?? null;
        return $d + ['active' => $r->boolean('active'), 'featured' => $r->boolean('featured')];
    }

    /** Only one package carries the "Most popular" label. The one just saved wins. */
    private function onlyOneFeatured(Plan $plan): void
    {
        if ($plan->featured) Plan::where('id', '!=', $plan->id)->update(['featured' => false]);
    }
}
