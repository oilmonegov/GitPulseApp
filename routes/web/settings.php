<?php

declare(strict_types=1);

use App\Http\Controllers\Settings\DataManagementController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SettingsController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Settings Routes
|--------------------------------------------------------------------------
|
| Routes for user settings including profile, password, appearance,
| and two-factor authentication management.
|
*/

Route::middleware(['auth'])->group(function (): void {
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/appearance', fn () => Inertia::render('settings/Appearance'))->name('appearance.edit');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    Route::get('settings/notifications', [NotificationSettingsController::class, 'edit'])
        ->name('notifications.edit');
    Route::patch('settings/notifications', [NotificationSettingsController::class, 'update'])
        ->name('notifications.update');

    Route::get('settings/data', [DataManagementController::class, 'index'])
        ->name('data.index');
    Route::post('settings/data/export', [DataManagementController::class, 'export'])
        ->name('data.export');
});
