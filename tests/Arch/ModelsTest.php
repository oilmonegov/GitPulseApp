<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Models
 *
 * These tests enforce architectural rules for Eloquent models.
 */
arch('models should be classes')
    ->expect('App\Models')
    ->toBeClasses();

arch('models should extend Eloquent Model')
    ->expect('App\Models')
    ->toExtend(\Illuminate\Database\Eloquent\Model::class);

arch('models should not have suffix')
    ->expect('App\Models')
    ->not->toHaveSuffix('Model');

arch('models should only be used in allowed locations')
    ->expect('App\Models')
    ->toOnlyBeUsedIn([
        'App\Http\Controllers',
        'App\Http\Requests',
        'App\Actions',
        'App\Providers',
        'App\Policies',
        'App\Observers',
        'App\Events',
        'App\Listeners',
        'App\Jobs',
        'App\Mail',
        'App\Notifications',
        'App\Console',
        'App\Rules',
        'App\Services',
        'App\Repositories',
        'App\Concerns',
        'Database\Factories',
        'Database\Seeders',
    ]);
