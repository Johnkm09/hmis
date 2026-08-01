<?php

namespace App\Support;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

final class ApiResponse
{
    public static function success(
        mixed $data = [],
        string $message = 'Success',
        int $status = 200,
        ?LengthAwarePaginator $paginator = null
    ): JsonResponse {
        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data,
            'meta'    => self::pagination($paginator),
        ], $status);
    }

    public static function error(
        string $message = 'Error',
        int $status = 400,
        array $errors = []
    ): JsonResponse {
        return response()->json([
            'status'  => 'error',
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    private static function pagination(?LengthAwarePaginator $paginator): ?array
    {
        if ($paginator === null) {
            return null;
        }

        return [
            'pagination' => [
                'current_page'   => $paginator->currentPage(),
                'last_page'      => $paginator->lastPage(),
                'per_page'       => $paginator->perPage(),
                'total'          => $paginator->total(),
                'from'           => $paginator->firstItem(),
                'to'             => $paginator->lastItem(),
                'has_more_pages' => $paginator->hasMorePages(),
            ],
        ];
    }
}