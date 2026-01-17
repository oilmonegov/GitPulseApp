<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Authentication Tests
 *
 * These tests verify that the authentication flow works correctly
 * in a real browser environment.
 */
it('displays the login form', function (): void {
    $page = visit(route('login'));

    $page->assertSee('Log in')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertNoJavascriptErrors();
});

it('displays validation errors for invalid login', function (): void {
    $page = visit(route('login'));

    $page->fill('email', 'invalid-email')
        ->fill('password', 'short')
        ->click('Log in')
        ->assertSee('email')
        ->assertNoJavascriptErrors();
});

it('can login with valid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $page = visit(route('login'));

    $page->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('Log in')
        ->assertPathIs('/dashboard')
        ->assertNoJavascriptErrors();

    $this->assertAuthenticated();
});

it('cannot login with invalid credentials', function (): void {
    $user = User::factory()->create([
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    $page = visit(route('login'));

    $page->fill('email', 'test@example.com')
        ->fill('password', 'wrong-password')
        ->click('Log in')
        ->assertPathIs('/login')
        ->assertNoJavascriptErrors();

    $this->assertGuest();
});

it('displays the registration form', function (): void {
    $page = visit(route('register'));

    $page->assertSee('Register')
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertNoJavascriptErrors();
});

it('can register a new user', function (): void {
    $page = visit(route('register'));

    $page->fill('name', 'Test User')
        ->fill('email', 'newuser@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->click('Register')
        ->assertNoJavascriptErrors();

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
    ]);
});

it('can logout', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertNoJavascriptErrors();

    $this->post(route('logout'));

    $this->assertGuest();
});
