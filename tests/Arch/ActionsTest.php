<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Actions
 *
 * These tests enforce architectural rules for action classes
 * following Laravel's action pattern (Fortify, Jetstream, etc.).
 */
arch('actions should be classes')
    ->expect('App\Actions')
    ->toBeClasses();

arch('actions should not depend on controllers')
    ->expect('App\Actions')
    ->not->toUse('App\Http\Controllers');

arch('actions should not depend on requests')
    ->expect('App\Actions')
    ->not->toUse('App\Http\Requests');
