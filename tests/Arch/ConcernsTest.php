<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Concerns (Traits)
 *
 * These tests enforce architectural rules for trait files.
 */
arch('concerns should be traits')
    ->expect('App\Concerns')
    ->toBeTraits();

arch('concerns should have valid naming')
    ->expect('App\Concerns')
    ->not->toHaveSuffix('Trait');
