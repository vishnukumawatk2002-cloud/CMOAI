<?php

namespace App\Http\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait HandlesApiQuery
{
    protected function applySearch(Builder $query, ?string $search, array $columns): Builder
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $columns) {
            foreach ($columns as $column) {
                $q->orWhere($column, 'like', "%{$search}%");
            }
        });
    }

    protected function applySorting(
        Builder $query,
        Request $request,
        array $allowed,
        string $default = 'created_at',
        string $defaultDirection = 'desc',
    ): Builder {
        $sort = in_array($request->sort, $allowed, true) ? $request->sort : $default;
        $direction = $request->direction === 'asc' ? 'asc' : $defaultDirection;

        return $query->orderBy($sort, $direction);
    }

    protected function perPage(Request $request, int $default = 15, int $max = 100): int
    {
        return min(max((int) $request->input('per_page', $default), 1), $max);
    }
}
