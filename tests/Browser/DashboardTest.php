<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Dashboard Tests
 *
 * These tests verify that the dashboard page works correctly.
 */
it('redirects unauthenticated users to login', function (): void {
    $page = visit(route('dashboard'));

    $page->assertPathIs('/login')
        ->assertNoJavascriptErrors();
});

it('displays dashboard for authenticated users', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('Dashboard')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('displays user information on dashboard', function (): void {
    $user = User::factory()->create([
        'name' => 'John Doe',
    ]);
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertSee('John Doe')
        ->assertNoJavascriptErrors();
});
