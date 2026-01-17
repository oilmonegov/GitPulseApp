<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Settings Tests
 *
 * These tests verify that the settings pages work correctly.
 */
it('can access profile settings', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    $page->assertSee('Profile')
        ->assertSee($user->name)
        ->assertSee($user->email)
        ->assertNoJavascriptErrors();
});

it('can update profile information', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'));

    $page->fill('name', 'Updated Name')
        ->click('Save')
        ->assertNoJavascriptErrors();

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
    ]);
});

it('can access password settings', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('user-password.edit'));

    $page->assertSee('Password')
        ->assertSee('Current Password')
        ->assertNoJavascriptErrors();
});

it('can access appearance settings', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('appearance.edit'));

    $page->assertSee('Appearance')
        ->assertNoJavascriptErrors();
});

it('can access two-factor authentication settings', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('two-factor.show'));

    $page->assertSee('Two-Factor')
        ->assertNoJavascriptErrors();
});

it('settings pages are responsive on mobile', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'))->on()->mobile();

    $page->assertSee('Profile')
        ->assertNoJavascriptErrors()
        ->assertNoConsoleLogs();
});

it('settings pages work in dark mode', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('profile.edit'))->inDarkMode();

    $page->assertSee('Profile')
        ->assertNoJavascriptErrors();
});
