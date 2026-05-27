<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Core\Response;

/**
 * Centralized JSON response formatter.
 * Enforces the standard HealingYuk API response envelope.
 */
final class ResponseHelper
{
    /**
     * Standard success response.
     *
     * @param mixed       $data
     * @param array|null  $meta  Pagination/extra metadata
     */
    public static function success(
        mixed  $data    = null,
        string $message = 'Success',
        int    $status  = 200,
        ?array $meta    = null
    ): Response {
        $body = [
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ];

        if ($meta !== null) {
            $body['meta'] = $meta;
        }

        return Response::json($body, $status);
    }

    /**
     * Standard error response.
     *
     * @param array<string, string[]>|null $errors  Field-level validation errors
     */
    public static function error(
        string $message,
        int    $status = 400,
        ?array $errors = null
    ): Response {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return Response::json($body, $status);
    }

    /**
     * Created response (201).
     */
    public static function created(mixed $data, string $message = 'Created successfully'): Response
    {
        return self::success($data, $message, 201);
    }

    /**
     * No-content response (204).
     */
    public static function noContent(): Response
    {
        return Response::json(null, 204);
    }

    /**
     * Paginated collection response.
     */
    public static function paginated(array $result, string $message = 'Data fetched successfully'): Response
    {
        return self::success(
            $result['data'],
            $message,
            200,
            [
                'total'       => $result['total'],
                'page'        => $result['page'],
                'limit'       => $result['limit'],
                'total_pages' => $result['total_pages'] ?? (int) ceil($result['total'] / max(1, $result['limit'])),
            ]
        );
    }
}
