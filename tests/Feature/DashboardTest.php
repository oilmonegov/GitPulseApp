<?php

declare(strict_types=1);

use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function (): void {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard renders with all props', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('summary')
            ->has('commitsOverTime')
            ->has('commitTypeDistribution'),
    );
});

test('dashboard summary shows correct data', function (): void {
    $user = User::factory()->create();
    $repo = Repository::factory()->for($user)->create();

    // Ensure commits are within the 30-day range
    Commit::factory()->count(3)->for($user)->for($repo)->today()->create([
        'additions' => 100,
        'deletions' => 50,
        'impact_score' => 5.0,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->has(
                'summary',
                fn (Assert $summary) => $summary
                    ->where('total_commits', 3)
                    ->where('average_impact', fn ($value) => (float) $value === 5.0)
                    ->where('lines_changed', 450), // (100 + 50) * 3
            ),
    );
});

test('dashboard commits over time includes date range', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->has(
                'commitsOverTime',
                30,
                fn (Assert $item) => $item
                    ->has('date')
                    ->has('count'),
            ),
    );
});

test('dashboard commit type distribution includes required fields', function (): void {
    $user = User::factory()->create();
    $repo = Repository::factory()->for($user)->create();

    // Ensure commit is within the 30-day range
    Commit::factory()->feature()->today()->for($user)->for($repo)->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertInertia(
        fn (Assert $page) => $page
            ->has(
                'commitTypeDistribution',
                1,
                fn (Assert $item) => $item
                    ->has('type')
                    ->has('label')
                    ->has('count')
                    ->has('color'),
            ),
    );
});
