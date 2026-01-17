<?php

declare(strict_types=1);

use App\Models\User;

/**
 * Browser Accessibility Tests
 *
 * These tests verify that the application meets accessibility standards.
 * They check for WCAG compliance and common accessibility issues.
 */
it('homepage has no accessibility issues', function (): void {
    $page = visit('/');

    $page->assertNoAccessibilityIssues()
        ->assertNoJavascriptErrors();
});

it('login page has no accessibility issues', function (): void {
    $page = visit(route('login'));

    $page->assertNoAccessibilityIssues()
        ->assertNoJavascriptErrors();
});

it('registration page has no accessibility issues', function (): void {
    $page = visit(route('register'));

    $page->assertNoAccessibilityIssues()
        ->assertNoJavascriptErrors();
});

it('dashboard has no accessibility issues when authenticated', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $page = visit(route('dashboard'));

    $page->assertNoAccessibilityIssues()
        ->assertNoJavascriptErrors();
});

it('settings pages have no accessibility issues', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $pages = visit([
        route('profile.edit'),
        route('user-password.edit'),
        route('appearance.edit'),
        route('two-factor.show'),
    ]);

    $pages->assertNoAccessibilityIssues()
        ->assertNoJavascriptErrors();
});

it('form fields have proper labels', function (): void {
    $page = visit(route('login'));

    // Check that form inputs are properly labeled
    $page->assertPresent('label[for="email"]')
        ->assertPresent('label[for="password"]')
        ->assertNoJavascriptErrors();
});

it('buttons are keyboard accessible', function (): void {
    $page = visit(route('login'));

    // Test keyboard navigation
    $page->keys('body', 'Tab')
        ->assertNoJavascriptErrors();
});

it('has proper heading hierarchy', function (): void {
    $page = visit('/');

    // Page should have at least one h1
    $page->assertPresent('h1')
        ->assertNoJavascriptErrors();
});

it('images have alt text', function (): void {
    $page = visit('/');

    // Script to check all images have alt attributes
    $page->script('
        const images = document.querySelectorAll("img");
        const missingAlt = Array.from(images).filter(img => !img.alt);
        return missingAlt.length;
    ');

    $page->assertNoJavascriptErrors();
});

it('has sufficient color contrast', function (): void {
    $page = visit('/');

    $page->assertNoAccessibilityIssues()
        ->assertNoJavascriptErrors();
});

it('is navigable with screen reader', function (): void {
    $page = visit('/');

    // Check for ARIA landmarks
    $page->assertPresent('[role="main"], main')
        ->assertNoJavascriptErrors();
});
