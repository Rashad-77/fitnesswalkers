<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function home()
    {
        $u = auth()->user();
        if (! $u) return redirect()->route('login');
        return redirect($u->isAdmin() ? route('admin.dashboard') : route('member.home'));
    }

    public function showLogin()
    {
        return auth()->check() ? $this->home() : view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate(['email' => 'required|email', 'password' => 'required']);
        if (! Auth::attempt($cred, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Wrong email or password.']);
        }
        $request->session()->regenerate();
        return redirect()->intended(auth()->user()->isAdmin() ? route('admin.dashboard') : route('member.home'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function showPassword()
    {
        return view('auth.password');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);
        $request->user()->update(['password' => $data['password']]);
        AuditLog::record('Changed own password');
        return back()->with('ok', 'Password changed.');
    }
}
