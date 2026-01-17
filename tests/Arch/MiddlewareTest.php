<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Middleware
 *
 * These tests enforce architectural rules for HTTP middleware.
 */
arch('middleware should be classes')
    ->expect('App\Http\Middleware')
    ->toBeClasses();

arch('middleware should not depend on controllers')
    ->expect('App\Http\Middleware')
    ->not->toUse('App\Http\Controllers');

arch('middleware should only be used in Http layer and providers')
    ->expect('App\Http\Middleware')
    ->toOnlyBeUsedIn([
        'App\Http',
        'App\Providers',
    ]);
