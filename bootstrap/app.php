<?php

declare(strict_types=1);

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            // Register webhook routes (excluded from web middleware)
            Route::middleware('api')
                ->group(base_path('routes/webhooks.php'));

            // Register health check routes (no auth required for monitoring)
            Route::middleware('web')
                ->group(base_path('routes/health.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Exclude webhook routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Report exceptions to Sentry
        Integration::handles($exceptions);

        // Handle Inertia requests with proper error pages
        $exceptions->respond(function ($response, $exception, Request $request) {
            // For Inertia requests, render error pages via Inertia
            if (
                ! app()->environment(['local', 'testing'])
                && in_array($response->getStatusCode(), [500, 503, 404, 403], true)
                && $request->header('X-Inertia')
            ) {
                return Inertia::render('Error', [
                    'status' => $response->getStatusCode(),
                    'message' => $exception instanceof HttpExceptionInterface
                        ? $exception->getMessage()
                        : __("errors.http.{$response->getStatusCode()}"),
                ])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }

            return $response;
        });

        // Don't report common HTTP errors in production
        $exceptions->dontReport([
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Session\TokenMismatchException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
        ]);

        // Sanitize sensitive data from being flashed to session
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
            'github_token',
            'api_key',
            'secret',
        ]);
    })->create();
