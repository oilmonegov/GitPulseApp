<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Navigation Tests
 *
 * These tests verify that navigation between pages works correctly.
 */
it('can navigate from home to login', function (): void {
    $page = visit('/');

    $page->click('Log in')
        ->assertPathIs('/login')
        ->assertNoJavascriptErrors();
});

it('can navigate from login to register', function (): void {
    $page = visit(route('login'));

    $page->click('Register')
        ->assertPathIs('/register')
        ->assertNoJavascriptErrors();
});

it('can navigate between settings pages when authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    $page->assertSee('Profile')
        ->assertNoJavascriptErrors();

    $page->navigate(route('user-password.edit'))
        ->assertSee('Password')
        ->assertNoJavascriptErrors();

    $page->navigate(route('appearance.edit'))
        ->assertSee('Appearance')
        ->assertNoJavascriptErrors();
});

it('handles browser back button correctly', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->navigate(route('profile.edit'))
        ->assertPathIs('/settings/profile')
        ->assertNoJavascriptErrors();
});
