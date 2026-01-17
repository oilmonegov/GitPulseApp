<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
| Routes are organized into modules for better maintainability.
| Each module has its own route file in routes/web/ directory.
|
*/

// Home Page
Route::get('/', fn () => Inertia::render('Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
]))->name('home');

/*
|--------------------------------------------------------------------------
| Module Routes
|--------------------------------------------------------------------------
|
| Load modular route files. Each module is responsible for its own routes.
| This approach reduces context penalty and improves code organization.
|
*/

$routeFiles = [
    'auth',       // Authentication routes (GitHub OAuth, etc.)
    'dashboard',  // Dashboard routes
    'settings',   // User settings routes
];

foreach ($routeFiles as $routeFile) {
    $path = __DIR__ . "/web/{$routeFile}.php";

    if (file_exists($path)) {
        require $path;
    }
}
