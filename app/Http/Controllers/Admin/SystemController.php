<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Expense, Food, Member, Membership, Payment, Setting};
use App\Support\Dummy;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public const SETTINGS = [
        'gym_name' => 'Gym name', 'owner_name' => 'Owner name', 'gym_phone' => 'Gym phone', 'gym_address' => 'Address',
        'gym_hours' => 'Opening hours', 'gym_email' => 'Gym email', 'tagline' => 'Short tagline (homepage)', 'about_text' => 'About us text (homepage and About page)', 'bank_details' => 'Bank details (for transfers)', 'receipt_footer' => 'Receipt footer text',
    ];

    public function audit()
    {
        return view('admin.audit', ['logs' => AuditLog::with('user')->latest()->paginate(50)]);
    }

    public function exportPage()
    {
        return view('admin.export', ['types' => [
            'members' => 'Members', 'memberships' => 'Memberships', 'payments' => 'Payments', 'expenses' => 'Expenses', 'foods' => 'Foods and prices',
        ]]);
    }

    public function export(string $type)
    {
        [$head, $rows] = match ($type) {
            'members' => [['Member no', 'Name', 'Phone', 'Email', 'Sex', 'DOB', 'Joined', 'Status', 'Days left', 'Balance due'],
                Member::with(['user', 'currentMembership.plan'])->get()->map(fn ($m) => [$m->member_no, $m->user->name, $m->user->phone, $m->user->email, $m->sex, $m->dob?->toDateString(), $m->joined_on->toDateString(), $m->status(), $m->daysLeft(), $m->balanceDue()])],
            'memberships' => [['Member no', 'Name', 'Plan', 'Starts', 'Ends', 'Price', 'Paid', 'Balance'],
                Membership::with(['member.user', 'plan'])->get()->map(fn ($x) => [$x->member->member_no, $x->member->user->name, $x->plan->name, $x->starts_on->toDateString(), $x->ends_on->toDateString(), $x->price, $x->paid(), $x->balance()])],
            'payments' => [['Receipt', 'Date', 'Member no', 'Name', 'Amount', 'Method', 'Plan', 'Note'],
                Payment::with(['member.user', 'membership.plan'])->orderBy('paid_on')->get()->map(fn ($p) => [$p->receipt_no, $p->paid_on->toDateString(), $p->member->member_no, $p->member->user->name, $p->amount, $p->method, $p->membership?->plan?->name, $p->note])],
            'expenses' => [['Date', 'Category', 'Amount', 'Description'], Expense::orderBy('spent_on')->get()->map(fn ($e) => [$e->spent_on->toDateString(), $e->category, $e->amount, $e->description])],
            'foods' => [['Name', 'Category', 'Diet type', 'Allergens', 'kcal/100g', 'Protein/100g', 'Carbs/100g', 'Fat/100g', 'Rs per kg', 'Price updated'],
                Food::orderBy('name')->get()->map(fn ($f) => [$f->name, $f->category, $f->diet_type, $f->allergens, $f->kcal_100g, $f->protein_100g, $f->carbs_100g, $f->fat_100g, $f->price_per_kg, $f->price_updated_on?->toDateString()])],
            default => abort(404),
        };
        AuditLog::record('Exported data', $type);
        return response()->streamDownload(function () use ($head, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // so Excel reads it correctly
            fputcsv($out, $head);
            foreach ($rows as $row) fputcsv($out, array_map(fn ($v) => self::safeCell($v), $row instanceof \Illuminate\Support\Collection ? $row->all() : $row));
            fclose($out);
        }, "fitnesswalkers-$type-".now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    /** Stops spreadsheet formula injection (a cell starting with = + - @ would run as a formula in Excel). */
    public static function safeCell($v)
    {
        return is_string($v) && $v !== '' && in_array($v[0], ['=', '+', '-', '@'], true) ? "'".$v : $v;
    }

    public function settings()
    {
        return view('admin.settings', ['fields' => self::SETTINGS, 'dummy' => Setting::where('is_dummy', true)->pluck('key')->all()]);
    }

    public function account()
    {
        return view('admin.account');
    }

    public function saveSettings(Request $r)
    {
        $d = $r->validate(collect(self::SETTINGS)->map(fn ($l, $k) => $k === 'about_text' ? 'nullable|string|max:3000' : 'nullable|string|max:500')->all());
        foreach ($d as $k => $v) Setting::put($k, $v, false);
        \Illuminate\Support\Facades\Cache::forget('dummy_total');
        AuditLog::record('Saved settings');
        return back()->with('ok', 'Settings saved.');
    }

    public function saveAccount(Request $r)
    {
        $u = $r->user();
        $d = $r->validate([
            'name' => 'required|max:120', 'email' => ['required', 'email', \Illuminate\Validation\Rule::unique('users', 'email')->ignore($u->id)],
            'phone' => 'nullable|max:30', 'password' => 'nullable|min:8|confirmed',
        ]);
        $u->name = $d['name']; $u->email = $d['email']; $u->phone = $d['phone'] ?? null; $u->is_dummy = false;
        if (! empty($d['password'])) $u->password = $d['password'];
        $u->save();
        \Illuminate\Support\Facades\Cache::forget('dummy_total');
        AuditLog::record('Updated owner login');
        return back()->with('ok', 'Owner login saved. Use the new email and password next time you sign in.');
    }

    public function placeholders()
    {
        $labels = [
            'users' => 'Logins (owner + sample members)', 'members' => 'Members', 'memberships' => 'Memberships', 'payments' => 'Payments', 'plans' => 'Membership plans',
            'expenses' => 'Expenses', 'announcements' => 'Announcements', 'equipment' => 'Equipment', 'exercises' => 'Exercises (clips are placeholders)',
            'workout_templates' => 'Workout templates', 'workout_plans' => 'Member workout plans', 'plan_requests' => 'Plan requests', 'workout_logs' => 'Workout logs',
            'body_logs' => 'Body measurements', 'goals' => 'Goals', 'foods' => 'Foods and prices', 'diet_plans' => 'Meal plans', 'renewal_requests' => 'Renewal requests', 'settings' => 'Settings (address, phone...)',
        ];
        return view('admin.placeholders', [
            'counts' => Dummy::counts(), 'labels' => $labels,
            'dummySettings' => Setting::where('is_dummy', true)->pluck('key')->all(), 'fields' => self::SETTINGS,
        ]);
    }

    public function purge(Request $r)
    {
        $r->validate(['confirm' => 'required|in:DELETE']);
        if (! \App\Models\User::where('role', 'admin')->where('is_dummy', false)->exists()) {
            return back()->withErrors(['confirm' => 'First replace the sample owner login in Settings > Owner login, otherwise you would lock yourself out.']);
        }
        $removed = Dummy::purge();
        \Illuminate\Support\Facades\Cache::forget('dummy_total');
        AuditLog::record('Removed all sample data', null, array_sum($removed).' rows');
        return redirect()->route('admin.placeholders')->with('ok', 'Sample data removed ('.array_sum($removed).' rows). Real data was not touched. If you are not signed in again, add a real owner login first!');
    }
}
