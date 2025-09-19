<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;
use Modules\User\Infrastructure\Laravel\Models\User;

/**
 * Base controller providing common functionality and type safety helpers.
 */
abstract class BaseController extends Controller
{
    use AuthorizesRequests;
    use ValidatesRequests;

    /**
     * Get the authenticated user with proper typing.
     */
    protected function getAuthenticatedUser(Request $request): User
    {
        /** @var User|null $user */
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Authentication required.');
        }

        return $user;
    }

    /**
     * Get the authenticated user ID.
     */
    protected function getAuthenticatedUserId(Request $request): int
    {
        return $this->getAuthenticatedUser($request)->getId();
    }

    /**
     * Return a success response with proper typing.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    protected function successResponse(
        mixed $data = null,
        ?string $message = null,
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::success($data, $message, $statusCode, $headers);
    }

    /**
     * Return an error response with proper typing.
     */
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $headers
     */
    protected function errorResponse(
        string $message,
        ?array $errors = null,
        int $statusCode = 400,
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::error($message, $errors, $statusCode, $headers);
    }

    /**
     * Return a validation error response.
     */
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $headers
     */
    protected function validationErrorResponse(
        array $errors,
        string $message = 'The given data was invalid.',
        int $statusCode = 422,
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::validationError($errors, $message, $statusCode, $headers);
    }

    /**
     * Return a not found response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    protected function notFoundResponse(
        string $message = 'Resource not found.',
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::notFound($message, $headers);
    }

    /**
     * Return a forbidden response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    protected function forbiddenResponse(
        string $message = 'Access denied.',
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::forbidden($message, $headers);
    }

    /**
     * Return an unauthorized response.
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    protected function unauthorizedResponse(
        string $message = 'Unauthorized.',
        array $headers = [],
    ): JsonResponse {
        return ApiResponse::unauthorized($message, $headers);
    }

    /**
     * Respond with success data (alias for successResponse).
     */
    /**
     * @param  array<string, mixed>  $headers
     */
    protected function respondWithSuccess(
        string $message,
        mixed $data = null,
        int $statusCode = 200,
        array $headers = [],
    ): JsonResponse {
        return $this->successResponse($data, $message, $statusCode, $headers);
    }

    /**
     * Respond with error (alias for errorResponse).
     */
    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, mixed>  $headers
     */
    protected function respondWithError(
        string $message,
        ?array $errors = null,
        int $statusCode = 400,
        array $headers = [],
    ): JsonResponse {
        return $this->errorResponse($message, $errors, $statusCode, $headers);
    }
}
