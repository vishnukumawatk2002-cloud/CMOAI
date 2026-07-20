<?php

namespace App\Http\Controllers\Api\Admin\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Admin\PlanStoreRequest;
use App\Http\Requests\Admin\PlanUpdateRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlanController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->hasPermission('plans.view')) {
            return $this->error('Forbidden.', 403);
        }

        $query = Plan::query()->withCount('subscriptions');

        $this->applySearch($query, $request->search, ['name', 'slug']);
        $this->applySorting($query, $request, ['name', 'price_monthly', 'sort_order'], 'sort_order', 'asc');

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        $plans = $query->paginate($this->perPage($request));

        return $this->paginated($plans, PlanResource::class);
    }

    public function store(PlanStoreRequest $request): JsonResponse
    {
        $plan = Plan::query()->create($request->planAttributes());

        return $this->created(new PlanResource($plan), 'Plan created successfully.');
    }

    public function show(Plan $plan): JsonResponse
    {
        if (! auth()->user()->hasPermission('plans.view')) {
            return $this->error('Forbidden.', 403);
        }

        $plan->loadCount('subscriptions');

        return $this->success(new PlanResource($plan));
    }

    public function update(PlanUpdateRequest $request, Plan $plan): JsonResponse
    {
        $plan->update($request->planAttributes());

        return $this->success(new PlanResource($plan->fresh()), 'Plan updated successfully.');
    }

    public function destroy(Plan $plan): JsonResponse
    {
        if (! auth()->user()->hasPermission('plans.delete')) {
            return $this->error('Forbidden.', 403);
        }

        if ($plan->subscriptions()->exists()) {
            return $this->error('Cannot delete a plan with active subscriptions.', 422);
        }

        $plan->delete();

        return $this->success(message: 'Plan deleted successfully.');
    }
}
