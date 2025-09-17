<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Exception;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Infrastructure\Laravel\Http\ApiResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class ApiExceptionHandler
{
    /**
     * Render API exceptions with consistent JSON responses.
     */
    public static function render(Request $request, Throwable $exception): ?JsonResponse
    {
        // Only handle API requests
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return null;
        }

        return match (true) {
            $exception instanceof ValidationException => self::handleValidationException($exception),
            $exception instanceof AuthenticationException => self::handleAuthenticationException($exception),
            $exception instanceof AccessDeniedHttpException => self::handleAccessDeniedException($exception),
            $exception instanceof ModelNotFoundException => self::handleModelNotFoundException($exception),
            $exception instanceof NotFoundHttpException => self::handleNotFoundException($exception),
            $exception instanceof ThrottleRequestsException => self::handleThrottleException($exception),
            $exception instanceof HttpException => self::handleHttpException($exception),
            default => self::handleGenericException($exception),
        };
    }

    /**
     * Handle validation exceptions.
     */
    private static function handleValidationException(ValidationException $exception): JsonResponse
    {
        return ApiResponse::validationError(
            errors: $exception->errors(),
            message: $exception->getMessage() !== '' ? $exception->getMessage() : 'The given data was invalid.',
        );
    }

    /**
     * Handle authentication exceptions.
     */
    private static function handleAuthenticationException(AuthenticationException $exception): JsonResponse
    {
        return ApiResponse::unauthorized(
            message: $exception->getMessage() !== '' ? $exception->getMessage() : 'Authentication required.',
        );
    }

    /**
     * Handle access denied exceptions.
     */
    private static function handleAccessDeniedException(AccessDeniedHttpException $exception): JsonResponse
    {
        return ApiResponse::forbidden(
            message: $exception->getMessage() !== '' ? $exception->getMessage() : 'Access denied.',
        );
    }

    /**
     * Handle model not found exceptions.
     *
     * @param  ModelNotFoundException<Model>  $exception
     */
    private static function handleModelNotFoundException(ModelNotFoundException $exception): JsonResponse
    {
        $model = class_basename($exception->getModel());

        return ApiResponse::notFound(
            message: "{$model} not found.",
        );
    }

    /**
     * Handle not found HTTP exceptions.
     */
    private static function handleNotFoundException(NotFoundHttpException $exception): JsonResponse
    {
        return ApiResponse::notFound(
            message: $exception->getMessage() !== '' ? $exception->getMessage() : 'The requested resource was not found.',
        );
    }

    /**
     * Handle rate limiting exceptions.
     */
    private static function handleThrottleException(ThrottleRequestsException $exception): JsonResponse
    {
        return ApiResponse::error(
            message: 'Too many requests. Please slow down.',
            statusCode: 429,
            headers: [
                'Retry-After' => $exception->getHeaders()['Retry-After'] ?? 60,
                'X-RateLimit-Limit' => $exception->getHeaders()['X-RateLimit-Limit'] ?? null,
                'X-RateLimit-Remaining' => $exception->getHeaders()['X-RateLimit-Remaining'] ?? null,
            ],
        );
    }

    /**
     * Handle HTTP exceptions.
     */
    private static function handleHttpException(HttpException $exception): JsonResponse
    {
        return ApiResponse::error(
            message: $exception->getMessage() !== '' ? $exception->getMessage() : 'An HTTP error occurred.',
            statusCode: $exception->getStatusCode(),
        );
    }

    /**
     * Handle generic exceptions.
     */
    private static function handleGenericException(Throwable $exception): JsonResponse
    {
        // Log the exception for debugging
        Log::error('API Exception: ' . $exception->getMessage(), [
            'exception' => $exception,
            'trace' => $exception->getTraceAsString(),
        ]);

        // Don't expose internal errors in production
        $message = app()->environment('production')
            ? 'An unexpected error occurred.'
            : $exception->getMessage();

        return ApiResponse::serverError(message: $message);
    }
}
