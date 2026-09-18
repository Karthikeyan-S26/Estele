<?php

namespace App\Http\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consistent JSON response shapes for the mobile API:
 *
 *  - success payloads      -> { "data": ... }
 *  - errors                -> { "success": false, "message": "..." }
 *  - validation failures   -> { "success": false, "message": "...", "errors": {...} } (422)
 *
 * Every endpoint in routes/api.php renders through these helpers so the
 * Flutter client only ever needs to understand three shapes.
 */
trait ApiResponses
{
    protected function ok(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            ...$meta,
        ], $status);
    }

    protected function paginated(LengthAwarePaginator $paginator, ?callable $transform = null): JsonResponse
    {
        $items = $transform ? $paginator->getCollection()->map($transform)->values() : $paginator->items();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }

    protected function created(mixed $data = null): JsonResponse
    {
        return $this->ok($data, [], 201);
    }

    /**
     * Reads and clamps the ?per_page query param to a sane bound (1–100) so a
     * client sending per_page=0 or per_page=-1 can't blow up the paginator
     * (Laravel throws / returns nonsense for non-positive per_page values).
     */
    protected function perPage(Request $request, int $default = 24): int
    {
        $perPage = (int) $request->query('per_page', $default);

        return max(1, min(100, $perPage));
    }

    protected function message(string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
        ], $status);
    }

    protected function error(string $message, int $status = 422, array $errors = []): JsonResponse
    {
        return response()->json(
            array_filter([
                'success' => false,
                'message' => $message,
                'errors' => $errors ?: null,
            ]),
            $status,
        );
    }
}