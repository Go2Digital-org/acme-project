<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\ApiPlatform\Exception;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Domain\Exception\ApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

final class LocalizedApiExceptionHandler
{
    /**
     * Render exception as localized JSON response for API requests.
     */
    public static function render(Request $request, Throwable $e): ?JsonResponse
    {
        // Only handle API requests
        if (! $request->expectsJson() && ! str_starts_with($request->getPathInfo(), '/api/')) {
            return null;
        }

        $locale = $request->attributes->get('api_locale', 'en');

        return match (true) {
            $e instanceof TokenMismatchException => self::handleTokenMismatchException($request, $locale),
            $e instanceof ValidationException => self::handleValidationException($e, $locale),
            $e instanceof AuthenticationException => self::handleAuthenticationException($locale),
            $e instanceof AuthorizationException => self::handleAuthorizationException($locale),
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => self::handleNotFoundException($locale),
            $e instanceof TooManyRequestsHttpException => self::handleRateLimitException($e, $locale),
            $e instanceof ApiException => self::handleApiException($e, $locale),
            $e instanceof HttpException => self::handleHttpException($e, $locale),
            default => self::handleGenericException($e, $locale),
        };
    }

    private static function handleValidationException(ValidationException $e, string $locale): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'validation_error',
                'message' => __('api.validation_failed', [], $locale),
                'code' => 'VALIDATION_FAILED',
                'details' => $e->errors(),
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private static function handleAuthenticationException(string $locale): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'authentication_error',
                'message' => __('api.unauthorized', [], $locale),
                'code' => 'AUTHENTICATION_REQUIRED',
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], Response::HTTP_UNAUTHORIZED);
    }

    private static function handleAuthorizationException(string $locale): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'authorization_error',
                'message' => __('api.forbidden', [], $locale),
                'code' => 'INSUFFICIENT_PERMISSIONS',
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], Response::HTTP_FORBIDDEN);
    }

    private static function handleTokenMismatchException(Request $request, string $locale): JsonResponse
    {
        // Log the 419 error with detailed context
        Log::channel('auth')->error('API CSRF Token Mismatch (419)', [
            'event' => 'api_csrf_token_mismatch',
            'timestamp' => now()->toISOString(),
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
            'user_id' => $request->user()?->id,
            'user_email' => $request->user()?->email,
            'locale' => $locale,
        ]);

        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'csrf_error',
                'message' => __('errors.419', [], $locale),
                'code' => 'CSRF_TOKEN_MISMATCH',
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], 419);
    }

    private static function handleNotFoundException(string $locale): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'not_found_error',
                'message' => __('api.resource_not_found', [], $locale),
                'code' => 'RESOURCE_NOT_FOUND',
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], Response::HTTP_NOT_FOUND);
    }

    private static function handleRateLimitException(TooManyRequestsHttpException $e, string $locale): JsonResponse
    {
        $retryAfter = $e->getHeaders()['Retry-After'] ?? null;

        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'rate_limit_error',
                'message' => __('api.rate_limit_exceeded', [], $locale),
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $retryAfter,
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], Response::HTTP_TOO_MANY_REQUESTS, $retryAfter ? ['Retry-After' => $retryAfter] : []);
    }

    private static function handleApiException(ApiException $e, string $locale): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'api_error',
                'message' => $e->getLocalizedMessage($locale),
                'code' => $e->getCode(),
                'details' => $e->getDetails(),
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], $e->getStatusCode());
    }

    private static function handleHttpException(HttpException $e, string $locale): JsonResponse
    {
        $statusCode = $e->getStatusCode();

        $message = match ($statusCode) {
            400 => __('api.error', [], $locale),
            401 => __('api.unauthorized', [], $locale),
            403 => __('api.forbidden', [], $locale),
            404 => __('api.resource_not_found', [], $locale),
            429 => __('api.rate_limit_exceeded', [], $locale),
            500 => __('api.server_error', [], $locale),
            default => $e->getMessage() !== '' ? $e->getMessage() : __('api.error', [], $locale),
        };

        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'http_error',
                'message' => $message,
                'code' => 'HTTP_' . $statusCode,
                'status_code' => $statusCode,
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], $statusCode);
    }

    private static function handleGenericException(Throwable $e, string $locale): JsonResponse
    {
        $isProduction = app()->environment('production');

        return new JsonResponse([
            'success' => false,
            'error' => [
                'type' => 'server_error',
                'message' => $isProduction
                    ? __('api.server_error', [], $locale)
                    : $e->getMessage(),
                'code' => 'INTERNAL_SERVER_ERROR',
                'debug' => $isProduction ? null : [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
            ],
            'meta' => [
                'locale' => $locale,
                'timestamp' => now()->toISOString(),
                'request_id' => request()->header('X-Request-ID') ?? uniqid(),
            ],
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
