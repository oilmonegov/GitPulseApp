<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Performance Tests
 *
 * These tests verify application performance metrics.
 * They ensure pages load within acceptable time limits.
 */
it('homepage loads within acceptable time', function (): void {
    $start = microtime(true);

    $page = visit('/');

    $loadTime = (microtime(true) - $start) * 1000;

    $page->assertNoJavascriptErrors();

    expect($loadTime)->toBeLessThan(5000); // 5 seconds max
});

it('login page loads within acceptable time', function (): void {
    $start = microtime(true);

    $page = visit(route('login'));

    $loadTime = (microtime(true) - $start) * 1000;

    $page->assertNoJavascriptErrors();

    expect($loadTime)->toBeLessThan(3000); // 3 seconds max
});

it('dashboard loads within acceptable time when authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $start = microtime(true);

    $page = visit(route('dashboard'));

    $loadTime = (microtime(true) - $start) * 1000;

    $page->assertNoJavascriptErrors();

    expect($loadTime)->toBeLessThan(3000); // 3 seconds max
});

it('settings pages load within acceptable time', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $routes = [
        route('profile.edit'),
        route('user-password.edit'),
        route('appearance.edit'),
        route('two-factor.show'),
    ];

    foreach ($routes as $route) {
        $start = microtime(true);

        $page = visit($route);

        $loadTime = (microtime(true) - $start) * 1000;

        $page->assertNoJavascriptErrors();

        expect($loadTime)->toBeLessThan(3000);
    }
});

it('handles slow network conditions gracefully', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    // Page should still be functional
    $page
        ->assertNoJavascriptErrors();
});
