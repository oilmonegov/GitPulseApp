<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Form Requests
 *
 * These tests enforce architectural rules for form request validation.
 */
arch('form requests should be classes')
    ->expect('App\Http\Requests')
    ->toBeClasses();

arch('form requests should have suffix')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('form requests should extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend(\Illuminate\Foundation\Http\FormRequest::class);

arch('form requests should implement rules method')
    ->expect('App\Http\Requests')
    ->toHaveMethod('rules');

arch('form requests should only be used in controllers')
    ->expect('App\Http\Requests')
    ->toOnlyBeUsedIn([
        'App\Http\Controllers',
    ]);
