<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Contracts\Repositories\ContentRepositoryInterface;
use App\Models\ContentItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EloquentContentRepository implements ContentRepositoryInterface
{
    public function findById(int $id): ?ContentItem
    {
        return ContentItem::query()->with(['hashtags', 'folder'])->find($id);
    }

    public function forBrand(int $brandId, array $filters = []): LengthAwarePaginator
    {
        $query = ContentItem::query()
            ->where('brand_id', $brandId)
            ->with(['hashtags']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (! empty($filters['folder_id'])) {
            $query->where('folder_id', $filters['folder_id']);
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('body', 'like', "%{$term}%");
            });
        }

        return $query
            ->orderByDesc('created_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function countByStatus(int $brandId): array
    {
        return ContentItem::query()
            ->where('brand_id', $brandId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function create(array $data): ContentItem
    {
        return ContentItem::query()->create($data);
    }

    public function update(ContentItem $item, array $data): ContentItem
    {
        $item->update($data);

        return $item->fresh(['hashtags']);
    }

    public function delete(ContentItem $item): bool
    {
        return (bool) $item->delete();
    }

    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return ContentItem::query()
            ->whereIn('id', $ids)
            ->update(['status' => $status]);
    }
}
