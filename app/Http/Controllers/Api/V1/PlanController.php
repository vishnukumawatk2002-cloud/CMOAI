<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $query = Plan::query()->where('is_active', true);

        $this->applySearch($query, $request->search, ['name', 'slug']);
        $this->applySorting($query, $request, ['name', 'price_monthly', 'sort_order'], 'sort_order', 'asc');

        $plans = $query->paginate($this->perPage($request));

        return $this->paginated($plans, PlanResource::class);
    }

    public function show(Plan $plan): JsonResponse
    {
        if (! $plan->is_active) {
            return $this->error('Plan not found.', 404);
        }

        return $this->success(new PlanResource($plan));
    }
}
