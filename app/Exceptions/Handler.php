<?php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'github_token',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e): void {
            // Don't report if we're in local environment and it's a common HTTP exception
            if (app()->environment('local') && $e instanceof HttpException) {
                return;
            }

            // Log additional context for debugging
            Log::error('Exception occurred', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        });

        $this->renderable(function (Throwable $e, Request $request): ?\Illuminate\Http\JsonResponse {
            if ($request->expectsJson() || $request->is('api/*')) {
                return $this->handleApiException($e);
            }

            return null;
        });
    }

    /**
     * Handle API exceptions with consistent JSON responses.
     */
    protected function handleApiException(Throwable $e): JsonResponse
    {
        $statusCode = $this->getStatusCode($e);
        $message = $this->getSafeMessage($e, $statusCode);

        $response = [
            'success' => false,
            'message' => $message,
        ];

        // Add validation errors if available
        if ($e instanceof ValidationException) {
            $response['errors'] = $e->errors();
        }

        // Add debug information only in local environment
        if (app()->environment('local') && config('app.debug')) {
            $response['debug'] = [
                'exception' => $e::class,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => collect($e->getTrace())
                    ->take(5)
                    ->map(fn ($trace): array => [
                        'file' => $trace['file'] ?? null,
                        'line' => $trace['line'] ?? null,
                        'function' => $trace['function'],
                    ])
                    ->toArray(),
            ];
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get the appropriate HTTP status code for the exception.
     */
    protected function getStatusCode(Throwable $e): int
    {
        if ($e instanceof HttpException) {
            return $e->getStatusCode();
        }

        if ($e instanceof AuthenticationException) {
            return Response::HTTP_UNAUTHORIZED;
        }

        if ($e instanceof ValidationException) {
            return Response::HTTP_UNPROCESSABLE_ENTITY;
        }

        if ($e instanceof ModelNotFoundException) {
            return Response::HTTP_NOT_FOUND;
        }

        return Response::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * Get a safe, user-friendly error message without exposing sensitive information.
     */
    protected function getSafeMessage(Throwable $e, int $statusCode): string
    {
        // Use custom messages from validation exceptions
        if ($e instanceof ValidationException) {
            return __('errors.validation.failed');
        }

        // Use custom messages from authentication exceptions
        if ($e instanceof AuthenticationException) {
            return __('errors.auth.unauthenticated');
        }

        // Check for HTTP status-specific translations
        $httpKey = "errors.http.{$statusCode}";

        if (__($httpKey) !== $httpKey) {
            return __($httpKey);
        }

        // In production, never expose raw exception messages
        if (app()->environment('production')) {
            return __('errors.generic.unexpected');
        }

        // In development, allow the message if it doesn't contain sensitive patterns
        $message = $e->getMessage();

        if ($this->containsSensitiveData($message)) {
            return __('errors.generic.unexpected');
        }

        return $message ?: __('errors.generic.unexpected');
    }

    /**
     * Check if a message contains potentially sensitive data.
     */
    protected function containsSensitiveData(string $message): bool
    {
        $sensitivePatterns = [
            '/password/i',
            '/secret/i',
            '/token/i',
            '/api[_-]?key/i',
            '/authorization/i',
            '/bearer/i',
            '/credential/i',
            '/private/i',
            '/database/i',
            '/sql/i',
            '/connection/i',
            '/\/Users\/\w+\//i', // File paths
            '/\/var\/www\//i',
            '/\/home\/\w+\//i',
        ];

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }
}
