<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class ApiResponse
{
    /**
     * Return a successful response with data.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function success(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        $response = [
            'success' => true,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Return a success response with paginated data.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @param  array<string, mixed>  $headers
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        ?string $message = null,
        array $headers = [],
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
                'self' => $paginator->url($paginator->currentPage()),
            ],
        ], 200, $headers);
    }

    /**
     * Return a success response with collection data.
     *
     * @param  Collection<int, mixed>|array<mixed>  $data
     * @param  array<string, mixed>  $headers
     */
    public static function collection(
        Collection|array $data,
        ?string $message = null,
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    /**
     * Return a success response with resource data.
     *
     * @param  array<string, mixed>  $headers
     */
    public static function resource(
        JsonResource $resource,
        ?string $message = null,
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        $data = $resource->resolve();

        return self::success(
            data: $data,
            message: $message,
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    /**
     * Return a success response with resource collection data.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function resourceCollection(
        ResourceCollection $collection,
        ?string $message = null,
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        $data = $collection->resolve();

        return self::success(
            data: $data,
            message: $message,
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    /**
     * Return an error response.
     */
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $headers
     */
    public static function error(
        string $message,
        ?array $errors = null,
        int $statusCode = 400,
        array $headers = [],
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toIso8601String(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode, $headers);
    }

    /**
     * Return a validation error response.
     */
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $headers
     */
    public static function validationError(
        array $errors,
        string $message = 'The given data was invalid.',
        int $statusCode = 422,
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            errors: $errors,
            statusCode: $statusCode,
            headers: $headers,
        );
    }

    /**
     * Return an unauthorized error response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function unauthorized(
        string $message = 'Unauthorized.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 401,
            headers: $headers,
        );
    }

    /**
     * Return a forbidden error response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function forbidden(
        string $message = 'Forbidden.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 403,
            headers: $headers,
        );
    }

    /**
     * Return a not found error response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function notFound(
        string $message = 'Resource not found.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 404,
            headers: $headers,
        );
    }

    /**
     * Return a bad request error response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function badRequest(
        string $message = 'Bad request.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 400,
            headers: $headers,
        );
    }

    /**
     * Return a server error response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function serverError(
        string $message = 'Internal server error.',
        array $headers = [],
    ): JsonResponse {
        return self::error(
            message: $message,
            statusCode: 500,
            headers: $headers,
        );
    }

    /**
     * Return a created response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function created(
        mixed $data = null,
        string $message = 'Resource created successfully.',
        array $headers = [],
    ): JsonResponse {
        return self::success(
            data: $data,
            message: $message,
            statusCode: 201,
            headers: $headers,
        );
    }

    /**
     * Return a no content response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    public static function noContent(array $headers = []): JsonResponse
    {
        return response()->json(null, 204, $headers);
    }
}
