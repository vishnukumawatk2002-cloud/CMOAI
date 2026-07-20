<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticate
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('admin')->check()) {
            return redirect()->route('admin.login');
        }

        $admin = Auth::guard('admin')->user();
        $admin->loadMissing(['roles.permissions']);

        if (! $admin->is_active) {
            Auth::guard('admin')->logout();

            return redirect()->route('admin.login')->with('error', 'Account deactivated.');
        }

        return $next($request);
    }
}
