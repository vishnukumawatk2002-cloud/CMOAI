<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PlanStoreRequest;
use App\Http\Requests\Admin\PlanUpdateRequest;
use App\Models\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    private const SORTABLE = ['name', 'price_monthly', 'sort_order', 'created_at'];

    public function index(Request $request): View
    {
        $sort = in_array($request->sort, self::SORTABLE, true) ? $request->sort : 'sort_order';
        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $plans = Plan::query()
            ->withCount('subscriptions')
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString();

        return view('admin.plans.index', compact('plans', 'sort', 'direction'));
    }

    public function create(): View
    {
        return view('admin.plans.create');
    }

    public function store(PlanStoreRequest $request): RedirectResponse
    {
        Plan::query()->create($request->planAttributes());

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Plan created successfully.');
    }

    public function edit(Plan $plan): View
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(PlanUpdateRequest $request, Plan $plan): RedirectResponse
    {
        $plan->update($request->planAttributes());

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Plan updated successfully.');
    }

    public function toggleActive(Plan $plan): RedirectResponse
    {
        if (! auth('admin')->user()->hasPermission('plans.edit')) {
            abort(403);
        }

        $plan->update(['is_active' => ! $plan->is_active]);

        $label = $plan->is_active ? 'activated' : 'deactivated';

        return back()->with('status', "Plan \"{$plan->name}\" {$label} successfully.");
    }

    public function destroy(Plan $plan): RedirectResponse
    {
        if (! auth('admin')->user()->hasPermission('plans.delete')) {
            abort(403);
        }

        if ($plan->subscriptions()->exists()) {
            return back()->with('error', 'Cannot delete this plan — subscriptions are linked to it. Deactivate the plan instead.');
        }

        if ($plan->orders()->exists()) {
            return back()->with('error', 'Cannot delete this plan — payment orders are linked to it. Deactivate the plan instead.');
        }

        try {
            $plan->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Cannot delete this plan because related records still exist. Deactivate it instead.');
        }

        return redirect()
            ->route('admin.plans.index')
            ->with('status', 'Plan deleted successfully.');
    }
}
