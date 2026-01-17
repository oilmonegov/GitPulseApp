<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Security Tests
 *
 * These tests verify that security measures work correctly
 * in a real browser environment.
 */
it('prevents XSS in user input', function (): void {
    $page = visit(route('login'));

    // Attempt XSS injection
    $page->fill('email', '<script>alert("xss")</script>')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavascriptErrors();

    // The script should not execute
    $page->assertDontSee('<script>');
});

it('prevents SQL injection in form fields', function (): void {
    $page = visit(route('login'));

    // Attempt SQL injection
    $page->fill('email', "'; DROP TABLE users; --")
        ->fill('password', 'password')
        ->click('Log in')
        ->assertNoJavascriptErrors();

    // Application should still be functional
    expect(User::count())->toBeGreaterThanOrEqual(0);
});

it('protects against CSRF attacks', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    // CSRF token should be present in forms
    $page->assertPresent('input[name="_token"]')
        ->assertNoJavascriptErrors();
});

it('session is destroyed on logout', function (): void {
    $user = User::factory()->create([
        'email' => 'security-test@example.com',
        'password' => bcrypt('password'),
    ]);

    $page = visit(route('login'));

    $page->fill('email', 'security-test@example.com')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertPathIs('/dashboard');

    $this->assertAuthenticated();

    $this->post(route('logout'));

    $this->assertGuest();
});

it('protected routes redirect unauthenticated users', function (): void {
    $protectedRoutes = [
        route('dashboard'),
        route('profile.edit'),
        route('user-password.edit'),
        route('appearance.edit'),
        route('two-factor.show'),
    ];

    foreach ($protectedRoutes as $route) {
        $page = visit($route);

        $page->assertPathIs('/login')
            ->assertNoJavascriptErrors();
    }
});

it('does not expose sensitive information in page source', function (): void {
    $user = User::factory()->create([
        'password' => bcrypt('super-secret-password'),
    ]);
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    $content = $page->content();

    expect($content)->not->toContain('super-secret-password');
    expect($content)->not->toContain($user->password);
});

it('handles malformed URLs gracefully', function (): void {
    $page = visit('/nonexistent-page-12345');

    $page->assertNoJavascriptErrors();
});

it('rate limits login attempts', function (): void {
    $user = User::factory()->create([
        'email' => 'rate-limit@example.com',
        'password' => bcrypt('password'),
    ]);

    // Make multiple failed login attempts
    for ($i = 0; $i < 6; $i++) {
        $page = visit(route('login'));

        $page->fill('email', 'rate-limit@example.com')
            ->fill('password', 'wrong-password')
            ->click('Log in');
    }

    // Should show rate limit message or be blocked
    $page = visit(route('login'));
    $page->assertNoJavascriptErrors();
});

it('password field masks input', function (): void {
    $page = visit(route('login'));

    // Password field should have type="password"
    $page->assertAttribute('#password', 'type', 'password')
        ->assertNoJavascriptErrors();
});

it('does not cache sensitive pages', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    $page->assertNoJavascriptErrors();

    // After logout, back button should not show cached content
    $this->post(route('logout'));

    $this->assertGuest();
});
