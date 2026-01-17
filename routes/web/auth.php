<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\GitHubController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Routes for authentication including GitHub OAuth integration.
|
*/

// GitHub OAuth Routes
Route::prefix('auth/github')->name('auth.github.')->group(function (): void {
    Route::get('/', [GitHubController::class, 'redirect'])->name('redirect');
    Route::get('/callback', [GitHubController::class, 'callback'])->name('callback');
    Route::delete('/', [GitHubController::class, 'disconnect'])
        ->middleware('auth')
        ->name('disconnect');
});
