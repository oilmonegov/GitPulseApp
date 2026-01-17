<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Spatie\WebhookClient\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| These routes handle incoming webhooks from external services.
| They are excluded from CSRF protection in bootstrap/app.php.
|
*/

Route::post('webhooks/github', WebhookController::class)
    ->name('webhooks.github');
