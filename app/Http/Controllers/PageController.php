<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Setting;

/** Public (logged-out) pages: home, about, pricing, contact, privacy, terms. */
class PageController extends Controller
{
    private function plans()
    {
        return Plan::where('active', true)->orderBy('duration_days')->get();
    }

    public function home()
    {
        return view('pages.home', ['plans' => $this->plans()]);
    }

    public function about()
    {
        return view('pages.about');
    }

    public function pricing()
    {
        return view('pages.pricing', ['plans' => $this->plans()]);
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    /** Enquiry form on the Contact page. Saved for the owner to follow up; nothing is emailed. */
    public function enquire(\Illuminate\Http\Request $r)
    {
        if ($r->filled('website')) return redirect()->route('contact')->with('ok', 'Thank you! We will message you soon.'); // honeypot: bots fill this
        $d = $r->validate([
            'name' => 'required|string|max:120', 'phone' => 'required|string|min:7|max:30',
            'email' => 'nullable|email|max:120', 'interest' => 'nullable|string|max:120', 'message' => 'nullable|string|max:1000',
        ]);
        \App\Models\Enquiry::create($d + ['source' => 'website']);
        \Illuminate\Support\Facades\Cache::forget('admin_nav_counts');
        return redirect()->route('contact')->with('ok', 'Thank you! We will message you soon.');
    }
}
