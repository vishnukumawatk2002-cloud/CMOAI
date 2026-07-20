<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Contracts\Repositories\ContentRepositoryInterface;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function __construct(private readonly ContentRepositoryInterface $content)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $brand = $request->attributes->get('current_brand');
        $subscription = $request->attributes->get('subscription');

        return $this->success([
            'brand' => [
                'id' => $brand->id,
                'name' => $brand->name,
                'setup_complete' => $brand->isSetupComplete(),
            ],
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'plan' => $subscription->plan?->name,
            ] : null,
            'content_stats' => $this->content->countByStatus($brand->id),
            'social_accounts' => $brand->socialAccounts()->count(),
        ]);
    }
}
