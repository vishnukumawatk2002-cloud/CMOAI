<?php

namespace App\Application\Services\Brand;

use App\Application\DTOs\Brand\CreateBrandDTO;
use App\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Domain\Contracts\Repositories\SubscriptionRepositoryInterface;
use App\Models\Brand;
use App\Models\BrandKnowledgeBase;
use App\Models\BrandVoiceSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BrandService
{
    public function __construct(
        private readonly BrandRepositoryInterface $brands,
        private readonly SubscriptionRepositoryInterface $subscriptions,
    ) {
    }

    public function create(User $user, CreateBrandDTO $dto): Brand
    {
        return DB::transaction(function () use ($user, $dto) {
            $brand = $this->brands->create($user, [
                'name' => $dto->name,
                'website' => $dto->website,
                'industry' => $dto->industry,
                'country' => $dto->country,
                'language' => $dto->language,
                'tone' => $dto->tone,
                'logo_path' => $dto->logoPath,
            ]);

            BrandVoiceSetting::query()->create(['brand_id' => $brand->id]);
            BrandKnowledgeBase::query()->create(['brand_id' => $brand->id]);

            return $brand->load(['voiceSettings', 'knowledgeBase']);
        });
    }

    public function switchBrand(User $user, int $brandId): Brand
    {
        $brand = $this->brands->findById($brandId);

        if (! $brand || $brand->user_id !== $user->id) {
            abort(403, 'Invalid brand selection.');
        }

        session(['current_brand_id' => $brand->id]);

        return $brand;
    }

    public function currentBrand(User $user): ?Brand
    {
        $brandId = session('current_brand_id');

        if ($brandId) {
            $brand = $this->brands->findById($brandId);

            if ($brand && $brand->user_id === $user->id) {
                return $brand;
            }

            session()->forget('current_brand_id');
        }

        $brand = Brand::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->latest()
            ->first();

        if ($brand) {
            session(['current_brand_id' => $brand->id]);
        }

        return $brand;
    }

    public function hasActiveSubscription(User $user): bool
    {
        return (bool) $this->subscriptions->activeForUser($user)?->isActive();
    }

    public function deleteBrand(User $user, Brand $brand): void
    {
        if ($brand->user_id !== $user->id) {
            abort(403, 'You cannot delete this brand.');
        }

        if (session('current_brand_id') === $brand->id) {
            session()->forget('current_brand_id');
        }

        $this->brands->delete($brand);

        $this->currentBrand($user);
    }

    public function currentBrandForApi(User $user, ?int $preferredBrandId = null): ?Brand
    {
        if ($preferredBrandId) {
            $brand = $this->brands->findById($preferredBrandId);

            return ($brand && $brand->user_id === $user->id) ? $brand : null;
        }

        return $this->brands->forUser($user)->first();
    }
}
