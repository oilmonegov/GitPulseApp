<?php

declare(strict_types=1);

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GithubProvider;
use Laravel\Socialite\Two\User as SocialiteUser;

beforeEach(function (): void {
    // Create a mock GitHub user
    $this->githubUser = new SocialiteUser;
    $this->githubUser->map([
        'id' => '123456789',
        'nickname' => 'testuser',
        'name' => 'Test User',
        'email' => 'testuser@github.com',
        'avatar' => 'https://avatars.githubusercontent.com/u/123456789',
    ]);
    $this->githubUser->token = 'fake-github-token';
});

test('github redirect route redirects to github', function (): void {
    $response = $this->get(route('auth.github.redirect'));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('github.com');
});

test('github callback creates new user when user does not exist', function (): void {
    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturn($this->mock(GithubProvider::class, function ($mock): void {
            $mock->shouldReceive('user')->andReturn($this->githubUser);
        }));

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticated();

    $this->assertDatabaseHas('users', [
        'github_id' => '123456789',
        'github_username' => 'testuser',
        'email' => 'testuser@github.com',
    ]);
});

test('github callback authenticates existing user with matching github id', function (): void {
    $user = User::factory()->withGitHub()->create([
        'github_id' => '123456789',
    ]);

    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturn($this->mock(GithubProvider::class, function ($mock): void {
            $mock->shouldReceive('user')->andReturn($this->githubUser);
        }));

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('github callback links account when authenticated user connects github', function (): void {
    $user = User::factory()->create([
        'github_id' => null,
    ]);

    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturn($this->mock(GithubProvider::class, function ($mock): void {
            $mock->shouldReceive('user')->andReturn($this->githubUser);
        }));

    $response = $this->actingAs($user)->get(route('auth.github.callback'));

    $response->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->github_id)->toBe('123456789');
    expect($user->github_username)->toBe('testuser');
});

test('github callback finds user by email if no github id match', function (): void {
    $user = User::factory()->create([
        'email' => 'testuser@github.com',
        'github_id' => null,
    ]);

    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturn($this->mock(GithubProvider::class, function ($mock): void {
            $mock->shouldReceive('user')->andReturn($this->githubUser);
        }));

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);

    $user->refresh();
    expect($user->github_id)->toBe('123456789');
});

test('github disconnect removes github credentials', function (): void {
    $user = User::factory()->withGitHub()->create();

    expect($user->github_id)->not->toBeNull();

    $response = $this->actingAs($user)->delete(route('auth.github.disconnect'));

    $response->assertRedirect();

    $user->refresh();
    expect($user->github_id)->toBeNull();
    expect($user->github_username)->toBeNull();
    expect($user->github_token)->toBeNull();
});

test('github disconnect requires authentication', function (): void {
    $response = $this->delete(route('auth.github.disconnect'));

    $response->assertRedirect(route('login'));
});

test('github callback handles oauth errors gracefully', function (): void {
    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturn($this->mock(GithubProvider::class, function ($mock): void {
            $mock->shouldReceive('user')->andThrow(new Exception('OAuth error'));
        }));

    $response = $this->get(route('auth.github.callback'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
    $this->assertGuest();
});

test('github callback prevents linking to already connected github account', function (): void {
    // Create a user who has already connected this GitHub account
    User::factory()->create([
        'github_id' => '123456789',
    ]);

    // Another user tries to connect the same GitHub account
    $anotherUser = User::factory()->create([
        'github_id' => null,
    ]);

    Socialite::shouldReceive('driver')
        ->with('github')
        ->andReturn($this->mock(GithubProvider::class, function ($mock): void {
            $mock->shouldReceive('user')->andReturn($this->githubUser);
        }));

    $response = $this->actingAs($anotherUser)->get(route('auth.github.callback'));

    // Should redirect back with an error since GitHub account is already linked
    $response->assertRedirect(route('dashboard'));

    // The current user should not have the GitHub account linked
    $anotherUser->refresh();
    expect($anotherUser->github_id)->toBeNull();
});

test('user has github connected helper returns correct value', function (): void {
    $userWithGitHub = User::factory()->withGitHub()->create();
    $userWithoutGitHub = User::factory()->create(['github_id' => null]);

    expect($userWithGitHub->hasGitHubConnected())->toBeTrue();
    expect($userWithoutGitHub->hasGitHubConnected())->toBeFalse();
});
