<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Service Providers
 *
 * These tests enforce architectural rules for service providers.
 */
arch('providers should be classes')
    ->expect('App\Providers')
    ->toBeClasses();

arch('providers should have suffix')
    ->expect('App\Providers')
    ->toHaveSuffix('Provider');

arch('providers should extend ServiceProvider')
    ->expect('App\Providers')
    ->toExtend(\Illuminate\Support\ServiceProvider::class);
