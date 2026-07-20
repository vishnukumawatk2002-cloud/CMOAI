<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\SocialAccountResource;
use App\Models\SocialAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAccountController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $brand = $request->attributes->get('current_brand');

        $query = SocialAccount::query()->where('brand_id', $brand->id);

        if ($request->platform) {
            $query->where('platform', $request->platform);
        }

        $this->applySearch($query, $request->search, ['account_name', 'account_handle']);
        $this->applySorting($query, $request, ['platform', 'connected_at'], 'connected_at');

        $accounts = $query->paginate($this->perPage($request));

        return $this->paginated($accounts, SocialAccountResource::class);
    }

    public function show(SocialAccount $socialAccount): JsonResponse
    {
        $this->authorizeAccount($socialAccount);

        return $this->success(new SocialAccountResource($socialAccount));
    }

    public function destroy(SocialAccount $socialAccount): JsonResponse
    {
        $this->authorizeAccount($socialAccount);

        $socialAccount->delete();

        return $this->success(message: 'Social account disconnected successfully.');
    }

    private function authorizeAccount(SocialAccount $account): void
    {
        if ($account->brand->user_id !== auth()->id()) {
            abort(403);
        }
    }
}
