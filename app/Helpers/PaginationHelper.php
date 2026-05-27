<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * Pagination metadata helper.
 */
final class PaginationHelper
{
    /**
     * Format a repository result set into a standard paginated array.
     */
    public static function format(array $result, int $page, int $limit): array
    {
        $total      = (int) ($result['total'] ?? 0);
        $totalPages = $limit > 0 ? (int) ceil($total / $limit) : 1;

        return [
            'data'        => $result['data'] ?? [],
            'total'       => $total,
            'page'        => $page,
            'limit'       => $limit,
            'total_pages' => $totalPages,
        ];
    }
}
