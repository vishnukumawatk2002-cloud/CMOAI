<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DTOs\Brand\CreateBrandDTO;
use App\Application\Services\Brand\BrandService;
use App\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Brand\StoreBrandRequest;
use App\Http\Requests\Api\Brand\UpdateBrandRequest;
use App\Http\Resources\BrandResource;
use App\Models\Brand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrandController extends ApiController
{
    public function __construct(
        private readonly BrandService $brandService,
        private readonly BrandRepositoryInterface $brands,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Brand::query()->where('user_id', $request->user()->id);

        $this->applySearch($query, $request->search, ['name', 'slug', 'industry']);
        $this->applySorting($query, $request, ['name', 'created_at'], 'created_at');

        $brands = $query->withCount(['socialAccounts', 'contentItems'])
            ->paginate($this->perPage($request));

        return $this->paginated($brands, BrandResource::class);
    }

    public function store(StoreBrandRequest $request): JsonResponse
    {
        $brand = $this->brandService->create($request->user(), new CreateBrandDTO(
            name: $request->name,
            website: $request->website,
            industry: $request->industry ?? 'Other',
            country: $request->country ?? 'IN',
            language: $request->language ?? 'English',
            tone: $request->tone,
        ));

        return $this->created(new BrandResource($brand), 'Brand created successfully.');
    }

    public function show(Brand $brand): JsonResponse
    {
        $this->authorizeBrand($brand);

        $brand->load(['voiceSettings', 'knowledgeBase'])
            ->loadCount(['socialAccounts', 'contentItems']);

        return $this->success(new BrandResource($brand));
    }

    public function update(UpdateBrandRequest $request, Brand $brand): JsonResponse
    {
        $this->authorizeBrand($brand);

        $brand->update($request->validated());

        return $this->success(new BrandResource($brand->fresh(['voiceSettings', 'knowledgeBase'])), 'Brand updated successfully.');
    }

    public function destroy(Brand $brand): JsonResponse
    {
        $this->authorizeBrand($brand);

        $brand->delete();

        return $this->success(message: 'Brand deleted successfully.');
    }

    public function switch(Request $request, Brand $brand): JsonResponse
    {
        $this->authorizeBrand($brand);

        return $this->success(new BrandResource($brand), 'Brand context switched.');
    }

    private function authorizeBrand(Brand $brand): void
    {
        if ($brand->user_id !== auth()->id()) {
            abort(403, 'You do not have access to this brand.');
        }
    }
}
