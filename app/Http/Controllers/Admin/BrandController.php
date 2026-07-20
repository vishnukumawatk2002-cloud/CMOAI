<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(Request $request): View
    {
        $brands = Brand::query()
            ->with(['user:id,first_name,last_name,email', 'plan:id,name,slug'])
            ->withCount(['socialAccounts', 'contentItems'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('industry', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('email', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($request->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.brands.index', compact('brands'));
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if (! auth('admin')->user()->hasPermission('brands.delete')) {
            abort(403);
        }

        $userId = $brand->user_id;
        $brand->delete();

        if ($userId) {
            return redirect()
                ->route('admin.users.show', $userId)
                ->with('status', 'Brand deleted successfully.');
        }

        return redirect()
            ->route('admin.brands.index')
            ->with('status', 'Brand deleted successfully.');
    }
}
