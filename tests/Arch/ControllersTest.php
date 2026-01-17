<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Controllers
 *
 * These tests enforce architectural rules for HTTP controllers.
 */
arch('controllers should be classes')
    ->expect('App\Http\Controllers')
    ->toBeClasses();

arch('controllers should have suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('controllers should extend base controller')
    ->expect('App\Http\Controllers')
    ->toExtend(\App\Http\Controllers\Controller::class)
    ->ignoring(\App\Http\Controllers\Controller::class);

arch('controllers should not be used outside Http layer')
    ->expect('App\Http\Controllers')
    ->toOnlyBeUsedIn([
        'App\Http\Controllers',
        'App\Providers',
    ]);

arch('controllers should not use Eloquent Builder directly')
    ->expect('App\Http\Controllers')
    ->not->toUse(\Illuminate\Database\Eloquent\Builder::class);

arch('controllers should only have resource or invocable methods')
    ->expect('App\Http\Controllers')
    ->not->toHavePublicMethodsBesides([
        // Invokable controller method
        '__invoke',
        // Resource controller methods
        'index',
        'show',
        'create',
        'store',
        'edit',
        'update',
        'destroy',
        // Constructor
        '__construct',
        // Auth controller specific methods (redirect, callback, disconnect patterns)
        'redirect',
        'callback',
        'disconnect',
        // Laravel middleware interface method
        'middleware',
    ])
    ->ignoring(\App\Http\Controllers\Controller::class);
