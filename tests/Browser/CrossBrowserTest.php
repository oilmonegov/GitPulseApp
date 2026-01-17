<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Cross-Browser Compatibility Tests
 *
 * These tests verify that the application works correctly across
 * different browsers. Run with different --browser flags:
 *
 * Usage:
 *   ./vendor/bin/pest tests/Browser/CrossBrowserTest.php --browser chrome
 *   ./vendor/bin/pest tests/Browser/CrossBrowserTest.php --browser firefox
 *   ./vendor/bin/pest tests/Browser/CrossBrowserTest.php --browser safari
 */
it('homepage renders correctly', function (): void {
    $page = visit('/');

    $page->assertNoSmoke();
});

it('login form works correctly', function (): void {
    $user = User::factory()->create([
        'email' => 'crossbrowser@example.com',
        'password' => bcrypt('password'),
    ]);

    $page = visit(route('login'));

    $page->fill('email', 'crossbrowser@example.com')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertPathIs('/dashboard')
        ->assertNoJavascriptErrors();

    $this->assertAuthenticated();
});

it('dashboard renders correctly when authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Dashboard')
        ->assertNoJavascriptErrors();
});

it('forms submit correctly', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    $page->fill('name', 'Cross Browser Test')
        ->click('Save')
        ->assertNoJavascriptErrors();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Cross Browser Test',
    ]);
});

it('navigation works correctly', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->navigate(route('profile.edit'))
        ->assertPathIs('/settings/profile')
        ->assertNoJavascriptErrors();
});

it('responsive design works correctly on mobile viewport', function (): void {
    $page = visit('/')->on()->mobile();

    $page->assertNoJavascriptErrors();
});

it('responsive design works correctly on tablet viewport', function (): void {
    $page = visit('/')->on()->tablet();

    $page->assertNoJavascriptErrors();
});

it('dark mode renders correctly', function (): void {
    $page = visit('/')->inDarkMode();

    $page->assertNoJavascriptErrors();
});

it('light mode renders correctly', function (): void {
    $page = visit('/')->inLightMode();

    $page->assertNoJavascriptErrors();
});
