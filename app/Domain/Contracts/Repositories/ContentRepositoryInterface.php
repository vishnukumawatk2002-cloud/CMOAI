<?php

namespace App\Domain\Contracts\Repositories;

use App\Models\ContentItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ContentRepositoryInterface
{
    public function findById(int $id): ?ContentItem;

    public function forBrand(int $brandId, array $filters = []): LengthAwarePaginator;

    public function countByStatus(int $brandId): array;

    public function create(array $data): ContentItem;

    public function update(ContentItem $item, array $data): ContentItem;

    public function delete(ContentItem $item): bool;

    public function bulkUpdateStatus(array $ids, string $status): int;
}
