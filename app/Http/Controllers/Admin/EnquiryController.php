<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{AuditLog, Enquiry};
use Illuminate\Http\Request;

class EnquiryController extends Controller
{
    public function index(Request $r)
    {
        $filter = array_key_exists($r->query('status'), Enquiry::STATUSES) ? $r->query('status') : 'open';
        $q = Enquiry::with('member.user')->latest();
        if ($filter === 'open') $q->whereIn('status', ['new', 'contacted']);
        else $q->where('status', $filter);
        return view('admin.enquiries.index', [
            'enquiries' => $q->get(), 'filter' => $filter,
            'counts' => Enquiry::selectRaw('status, count(*) c')->groupBy('status')->pluck('c', 'status'),
        ]);
    }

    public function create()
    {
        return view('admin.enquiries.form');
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'name' => 'required|string|max:120', 'phone' => 'required|string|max:30', 'email' => 'nullable|email|max:120',
            'interest' => 'nullable|string|max:120', 'message' => 'nullable|string|max:1000',
            'source' => 'required|in:walk-in,phone,other',
        ]);
        $e = Enquiry::create($d);
        \Illuminate\Support\Facades\Cache::forget('admin_nav_counts');
        AuditLog::record('Added enquiry', null, $e->name);
        return redirect()->route('admin.enquiries.index')->with('ok', 'Enquiry saved.');
    }

    public function update(Request $r, Enquiry $enquiry)
    {
        $d = $r->validate(['status' => 'required|in:new,contacted,joined,lost', 'notes' => 'nullable|string|max:1000']);
        $enquiry->update($d + ['is_dummy' => false]);
        \Illuminate\Support\Facades\Cache::forget('admin_nav_counts');
        return back()->with('ok', 'Enquiry updated.');
    }

    public function destroy(Enquiry $enquiry)
    {
        $enquiry->delete();
        \Illuminate\Support\Facades\Cache::forget('admin_nav_counts');
        return back()->with('ok', 'Enquiry deleted.');
    }
}
