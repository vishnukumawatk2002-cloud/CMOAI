<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface BrandRepositoryInterface
{
    public function findById(int $id): ?Brand;

    public function findBySlug(User $user, string $slug): ?Brand;

    public function forUser(User $user): Collection;

    public function create(User $user, array $data): Brand;

    public function update(Brand $brand, array $data): Brand;

    public function delete(Brand $brand): bool;

    public function countForUser(User $user): int;
}
