<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBrandAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $brand = $request->route('brand');

        if ($brand instanceof Brand && $brand->user_id !== $request->user()?->id) {
            abort(403);
        }

        return $next($request);
    }
}
