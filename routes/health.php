<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;

/*
|--------------------------------------------------------------------------
| Health Check Routes
|--------------------------------------------------------------------------
|
| These routes provide health check endpoints for monitoring systems,
| load balancers, and orchestration tools (e.g., Kubernetes).
|
| Endpoints:
| - /health         → HTML dashboard (requires HEALTH_SECRET_TOKEN in production)
| - /health/json    → JSON API response for automated monitoring
|
*/

// Health check dashboard (HTML)
Route::get('/health', HealthCheckResultsController::class)
    ->name('health');

// Health check API (JSON) - for automated monitoring
Route::get('/health/json', HealthCheckJsonResultsController::class)
    ->name('health.json');
