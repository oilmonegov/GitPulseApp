<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Stress Tests
 *
 * These tests verify application stability under stress conditions.
 * Run with --parallel for concurrent browser instances.
 *
 * Usage:
 *   ./vendor/bin/pest tests/Browser/StressTest.php --parallel
 */
it('handles rapid page navigation', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    // Rapidly navigate between pages
    for ($i = 0; $i < 5; $i++) {
        $page->navigate(route('profile.edit'))
            ->assertNoJavascriptErrors();

        $page->navigate(route('dashboard'))
            ->assertNoJavascriptErrors();
    }
});

it('handles multiple form submissions', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    // Submit form multiple times rapidly
    for ($i = 0; $i < 3; $i++) {
        $page->fill('name', "Test User {$i}")
            ->click('Save')
            ->assertNoJavascriptErrors();
    }
});

it('handles concurrent page loads', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Visit multiple pages simultaneously
    $pages = visit([
        route('dashboard'),
        route('profile.edit'),
        route('user-password.edit'),
        route('appearance.edit'),
    ]);

    $pages->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('handles large dataset rendering', function (): void {
    // Create multiple users to simulate data-heavy pages
    User::factory()->count(50)->create();

    $user = User::first();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page
        ->assertNoJavascriptErrors();
});

it('handles repeated authentication cycles', function (): void {
    for ($i = 0; $i < 3; $i++) {
        $user = User::factory()->create([
            'email' => "stress-test-{$i}@example.com",
            'password' => bcrypt('password'),
        ]);

        $page = visit(route('login'));

        $page->fill('email', $user->email)
            ->fill('password', 'password')
            ->click('Log in')
            ->assertPathIs('/dashboard')
            ->assertNoJavascriptErrors();

        $this->post(route('logout'));
    }
});

it('handles long-running page sessions', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    // Simulate staying on page and performing actions
    $page->assertNoJavascriptErrors();

    // Wait and check for memory leaks or JS errors
    $page->wait(2000)
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});
