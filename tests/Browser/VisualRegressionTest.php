<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Visual Regression Tests
 *
 * These tests capture screenshots to detect visual regressions.
 * Screenshots are compared against baseline images.
 */
it('homepage matches visual snapshot', function (): void {
    $page = visit('/');

    $page->assertScreenshotMatches();
})->skip(! file_exists(__DIR__ . '/Screenshots/.homepage.png'), 'Run tests once to generate baseline screenshots');

it('login page matches visual snapshot', function (): void {
    $page = visit(route('login'));

    $page->assertScreenshotMatches();
})->skip(! file_exists(__DIR__ . '/Screenshots/.login-page.png'), 'Run tests once to generate baseline screenshots');

it('dashboard matches visual snapshot when authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertScreenshotMatches();
})->skip(! file_exists(__DIR__ . '/Screenshots/.dashboard.png'), 'Run tests once to generate baseline screenshots');
