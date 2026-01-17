<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Global Rules
 *
 * These tests enforce global architectural rules across the entire codebase.
 */
arch('app should use strict types')
    ->expect('App')
    ->toUseStrictTypes();

arch('should not use debugging functions in production code')
    ->expect('App')
    ->not->toUse(['die', 'dd', 'dump', 'ray', 'var_dump', 'print_r']);

arch('should not use env function outside config')
    ->expect('App')
    ->not->toUse('env');

arch('http layer should only be used by http layer')
    ->expect('App\Http')
    ->toOnlyBeUsedIn([
        'App\Http',
        'App\Providers',
    ]);

arch('exception handler should extend framework exception handler')
    ->expect(\App\Exceptions\Handler::class)
    ->toExtend(\Illuminate\Foundation\Exceptions\Handler::class);
