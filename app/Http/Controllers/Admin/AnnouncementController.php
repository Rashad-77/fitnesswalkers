<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Announcement, AuditLog};
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements.index', ['items' => Announcement::orderByDesc('published_at')->get()]);
    }

    public function create()
    {
        return view('admin.announcements.form', ['a' => new Announcement()]);
    }

    public function store(Request $r)
    {
        $a = Announcement::create($this->data($r) + ['published_at' => now()]);
        AuditLog::record('Posted announcement', $a->title);
        return redirect()->route('admin.announcements.index')->with('ok', 'Posted. Members see it in their app.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.form', ['a' => $announcement]);
    }

    public function update(Request $r, Announcement $announcement)
    {
        $announcement->update($this->data($r) + ['is_dummy' => false]);
        AuditLog::record('Edited announcement', $announcement->title);
        return redirect()->route('admin.announcements.index')->with('ok', 'Updated.');
    }

    public function destroy(Announcement $announcement)
    {
        AuditLog::record('Deleted announcement', $announcement->title);
        $announcement->delete();
        return back()->with('ok', 'Deleted.');
    }

    private function data(Request $r): array
    {
        return $r->validate(['title' => 'required|max:150', 'body' => 'required|max:3000']);
    }
}
