<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogTableQueryService
{
    /**
     * @param  list<string>  $searchableColumns
     * @param  callable(mixed): array<string, mixed>  $transform
     */
    public function paginate(
        Request $request,
        Builder $query,
        array $searchableColumns,
        callable $transform,
    ): JsonResponse {
        $perPage = min(100, max(5, (int) $request->integer('per_page', 25)));
        $page = max(1, (int) $request->integer('page', 1));

        $this->applyListFilters($request, $query, $searchableColumns);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'items' => $paginator->getCollection()->map($transform)->values()->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * @param  list<string>  $searchableColumns
     * @param  callable(mixed): array<string, mixed>  $transform
     * @return list<array<string, mixed>>
     */
    public function collect(
        Request $request,
        Builder $query,
        array $searchableColumns,
        callable $transform,
    ): array {
        $this->applyListFilters($request, $query, $searchableColumns);

        return $query->get()->map($transform)->values()->all();
    }

    /**
     * @param  list<string>  $searchableColumns
     */
    private function applyListFilters(Request $request, Builder $query, array $searchableColumns): void
    {
        $filters = $request->input('filters', []);
        if (is_array($filters)) {
            foreach ($filters as $key => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $query->where($key, 'like', '%'.$value.'%');
            }
        }

        $search = trim((string) $request->get('search', ''));
        if ($search !== '' && $searchableColumns !== []) {
            $query->where(function (Builder $inner) use ($searchableColumns, $search) {
                foreach ($searchableColumns as $column) {
                    $inner->orWhere($column, 'like', '%'.$search.'%');
                }
            });
        }

        $sortKey = trim((string) $request->get('sort_key', ''));
        $sortDir = strtolower((string) $request->get('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        if ($sortKey !== '') {
            $query->orderBy($sortKey, $sortDir);
        }
    }
}
