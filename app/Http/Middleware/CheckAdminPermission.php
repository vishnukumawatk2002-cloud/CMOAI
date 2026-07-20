<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = $this->resolveAdmin($request);

        if (! $admin || ! $admin->hasPermission($permission)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access this resource.',
                ], 403);
            }

            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }

    private function resolveAdmin(Request $request): ?Admin
    {
        $user = $request->user();

        return $user instanceof Admin ? $user : $request->user('admin');
    }
}
