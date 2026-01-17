<?php

declare(strict_types=1);

/**
 * Pest Architectural Testing - Presets
 *
 * These tests enforce PHP, security, and Laravel best practices
 * across the entire codebase using Pest's built-in presets.
 */
arch()->preset()->php();

arch()->preset()->security()
    ->ignoring('md5'); // md5 is used legitimately for Gravatar hashing

arch()->preset()->laravel()
    ->ignoring(\App\Http\Controllers\Auth\GitHubController::class) // OAuth controller has custom methods
    ->ignoring('App\Constants'); // Project uses Constants folder for enums (not Enums folder)
