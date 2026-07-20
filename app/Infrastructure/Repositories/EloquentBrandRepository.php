<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\BrandRepositoryInterface;
use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class EloquentBrandRepository implements BrandRepositoryInterface
{
    public function findById(int $id): ?Brand
    {
        return Brand::query()->find($id);
    }

    public function findBySlug(User $user, string $slug): ?Brand
    {
        return Brand::query()
            ->where('user_id', $user->id)
            ->where('slug', $slug)
            ->first();
    }

    public function forUser(User $user): Collection
    {
        return Brand::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(User $user, array $data): Brand
    {
        $data['user_id'] = $user->id;
        $data['slug'] = $data['slug'] ?? $this->uniqueSlug($user, $data['name']);

        return Brand::query()->create($data);
    }

    protected function uniqueSlug(User $user, string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'brand';
        $slug = $baseSlug;
        $counter = 2;

        while ($this->findBySlug($user, $slug)) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    public function update(Brand $brand, array $data): Brand
    {
        $brand->update($data);

        return $brand->fresh();
    }

    public function delete(Brand $brand): bool
    {
        return (bool) $brand->delete();
    }

    public function countForUser(User $user): int
    {
        return Brand::query()->where('user_id', $user->id)->count();
    }
}
