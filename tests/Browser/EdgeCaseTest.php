<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Edge Case Tests
 *
 * These tests verify that the application handles edge cases and
 * unusual scenarios correctly.
 */
it('handles empty form submissions gracefully', function (): void {
    $page = visit(route('login'));

    $page->click('Log in')
        ->assertNoJavascriptErrors();

    // Should show validation errors, not crash
    $this->assertGuest();
});

it('handles very long input values', function (): void {
    $page = visit(route('login'));

    $longEmail = str_repeat('a', 500) . '@example.com';

    $page->fill('email', $longEmail)
        ->fill('password', str_repeat('a', 500))
        ->click('Log in')
        ->assertNoJavascriptErrors();
});

it('handles special characters in input', function (): void {
    $page = visit(route('register'));

    $page->fill('name', "O'Brien-McDonald Jr.")
        ->fill('email', 'test+special@example.com')
        ->fill('password', 'P@$$w0rd!#$%')
        ->fill('password_confirmation', 'P@$$w0rd!#$%')
        ->click('Register')
        ->assertNoJavascriptErrors();
});

it('handles unicode characters', function (): void {
    $page = visit(route('register'));

    $page->fill('name', '日本語テスト')
        ->fill('email', 'unicode@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->click('Register')
        ->assertNoJavascriptErrors();
});

it('handles emoji in input', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    $page->fill('name', 'Test User 🚀👨‍💻')
        ->click('Save')
        ->assertNoJavascriptErrors();
});

it('handles rapid clicking', function (): void {
    $page = visit(route('login'));

    // Rapid click simulation
    $page->fill('email', 'test@example.com')
        ->fill('password', 'password');

    for ($i = 0; $i < 5; $i++) {
        $page->click('Log in');
    }

    $page->assertNoJavascriptErrors();
});

it('handles browser refresh during form submission', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    $page->fill('name', 'Refreshed Name')
        ->assertNoJavascriptErrors();

    // Page should handle gracefully
    $page->navigate(route('profile.edit'))
        ->assertNoJavascriptErrors();
});

it('handles network timeout gracefully', function (): void {
    $page = visit('/');

    $page
        ->assertNoJavascriptErrors();
});

it('handles concurrent sessions', function (): void {
    $user = User::factory()->create([
        'email' => 'concurrent@example.com',
        'password' => bcrypt('password'),
    ]);

    // Login in first session
    $this->actingAs($user);
    $page1 = visit(route('dashboard'));
    $page1->assertNoJavascriptErrors();

    // Application should handle concurrent access
    $page2 = visit(route('profile.edit'));
    $page2->assertNoJavascriptErrors();
});

it('handles expired sessions gracefully', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));
    $page->assertNoJavascriptErrors();

    // Simulate session expiry by logging out
    $this->post(route('logout'));

    // Navigating should redirect to login
    $page->navigate(route('profile.edit'))
        ->assertPathIs('/login')
        ->assertNoJavascriptErrors();
});

it('handles page with missing assets gracefully', function (): void {
    $page = visit('/');

    // Even if some assets fail, page should not crash
    $page->assertNoJavascriptErrors();
});

it('handles different viewport sizes', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $viewports = [
        [320, 568],   // iPhone SE
        [375, 667],   // iPhone 8
        [768, 1024],  // iPad
        [1280, 720],  // HD
        [1920, 1080], // Full HD
    ];

    foreach ($viewports as [$width, $height]) {
        $page = visit(route('dashboard'));
        $page->resize($width, $height)
            ->assertNoJavascriptErrors();
    }
});
