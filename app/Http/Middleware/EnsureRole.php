<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        if (! $user) return redirect()->route('login');
        if ($user->role !== $role) {
            return redirect($user->isAdmin() ? route('admin.dashboard') : route('member.home'));
        }
        if ($role === 'member' && ! $user->member) abort(403, 'No member profile is linked to this account.');
        return $next($request);
    }
}
