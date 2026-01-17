<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Smoke Tests
 *
 * These tests visit critical pages and ensure they render without
 * JavaScript errors, console logs, or accessibility issues.
 */
it('can load the homepage without smoke', function (): void {
    $page = visit('/');

    $page->assertNoSmoke();
});

it('can load the login page without smoke', function (): void {
    $page = visit(route('login'));

    $page->assertNoSmoke();
});

it('can load the registration page without smoke', function (): void {
    $page = visit(route('register'));

    $page->assertNoSmoke();
});

it('can load the password reset page without smoke', function (): void {
    $page = visit(route('password.request'));

    $page->assertNoSmoke();
});

it('can load the dashboard without smoke when authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertNoSmoke();
});

it('can load settings pages without smoke when authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $pages = visit([
        route('profile.edit'),
        route('user-password.edit'),
        route('appearance.edit'),
        route('two-factor.show'),
    ]);

    $pages->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});
