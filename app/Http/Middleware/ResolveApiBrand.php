<?php

namespace App\Http\Middleware;

use App\Application\Services\Brand\BrandService;
use App\Models\Brand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveApiBrand
{
    public function __construct(private readonly BrandService $brands)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $brand = $request->route('brand');

        if ($brand instanceof Brand) {
            if ($brand->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this brand.',
                ], 403);
            }
        } else {
            $brandId = $request->header('X-Brand-Id') ?? $request->query('brand_id');

            if ($brandId) {
                $brand = Brand::query()
                    ->where('id', $brandId)
                    ->where('user_id', $user->id)
                    ->first();

                if (! $brand) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid brand context.',
                    ], 403);
                }
            } else {
                $brand = $this->brands->currentBrandForApi($user);
            }
        }

        if (! $brand) {
            return response()->json([
                'success' => false,
                'message' => 'No brand selected. Provide X-Brand-Id header or create a brand.',
            ], 422);
        }

        $request->attributes->set('current_brand', $brand);

        return $next($request);
    }
}
