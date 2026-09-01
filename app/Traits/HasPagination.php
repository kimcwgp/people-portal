<?php 

namespace App\Traits;

trait HasPagination
{
    private const MAX_PER_PAGE = 100;
    private const DEFAULT_PER_PAGE = 10;

    protected function getPerPageLimit(string $context = null, int $requestedLimit = null): int
    {
        if ($requestedLimit) {
            return min($requestedLimit, self::MAX_PER_PAGE);
        }

        return self::DEFAULT_PER_PAGE;
    }

    protected function buildPaginationResponse($paginatedData): array
    {
        return [
            'data' => $paginatedData->items(),
            'current_page' => $paginatedData->currentPage(),
            'last_page' => $paginatedData->lastPage(),
            'per_page' => $paginatedData->perPage(),
            'total' => $paginatedData->total(),
            'from' => $paginatedData->firstItem(),
            'to' => $paginatedData->lastItem(),
        ];
    }

    protected function buildPaginationMeta($paginatedData): array
    {
        return [
            'current_page' => $paginatedData->currentPage(),
            'last_page' => $paginatedData->lastPage(),
            'per_page' => $paginatedData->perPage(),
            'total' => $paginatedData->total(),
            'from' => $paginatedData->firstItem(),
            'to' => $paginatedData->lastItem(),
        ];
    }

    protected function buildManualPaginationResponse($items, int $currentPage, int $perPage): array
    {
        $total = $items->count();
        $offset = ($currentPage - 1) * $perPage;
        $paginatedItems = $items->slice($offset, $perPage)->values();

        return [
            'data' => $paginatedItems,
            'current_page' => $currentPage,
            'last_page' => (int) ceil($total / $perPage),
            'per_page' => $perPage,
            'total' => $total,
            'from' => $total > 0 ? $offset + 1 : null,
            'to' => $total > 0 ? min($offset + $perPage, $total) : null,
        ];
    }
}