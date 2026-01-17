<?php

declare(strict_types=1);

use App\DTOs\GitHub\CommitData;
use App\Integrations\GitHub\Requests\CreateWebhookRequest;
use App\Integrations\GitHub\Requests\DeleteWebhookRequest;
use App\Integrations\GitHub\Requests\GetAuthenticatedUserRequest;
use App\Integrations\GitHub\Requests\GetCommitRequest;
use App\Integrations\GitHub\Requests\GetRepositoryRequest;
use App\Integrations\GitHub\Requests\ListUserRepositoriesRequest;
use App\Models\User;
use App\Services\GitHubService;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('GitHubService', function (): void {
    beforeEach(function (): void {
        MockClient::destroyGlobal();
    });

    it('can be created for a user', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_token' => 'gho_test_token_123',
        ]);

        $service = GitHubService::forUser($user);

        expect($service)->toBeInstanceOf(GitHubService::class);
    });

    it('fetches commit data from API', function (): void {
        MockClient::global([
            GetCommitRequest::class => MockResponse::make([
                'sha' => 'abc123def456',
                'commit' => [
                    'message' => 'feat: add new feature',
                    'author' => [
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'date' => '2026-01-17T10:30:00Z',
                    ],
                ],
                'html_url' => 'https://github.com/owner/repo/commit/abc123def456',
                'stats' => [
                    'additions' => 50,
                    'deletions' => 10,
                ],
                'files' => [
                    ['filename' => 'file1.php', 'status' => 'added', 'additions' => 30, 'deletions' => 0, 'changes' => 30],
                    ['filename' => 'file2.php', 'status' => 'modified', 'additions' => 20, 'deletions' => 10, 'changes' => 30],
                ],
            ], 200),
        ]);

        $service = new GitHubService('test_token');
        $commit = $service->getCommit('owner', 'repo', 'abc123');

        expect($commit)->toBeInstanceOf(CommitData::class)
            ->and($commit->sha)->toBe('abc123def456')
            ->and($commit->message)->toBe('feat: add new feature')
            ->and($commit->authorName)->toBe('John Doe')
            ->and($commit->additions)->toBe(50)
            ->and($commit->deletions)->toBe(10)
            ->and($commit->files)->toHaveCount(2);
    });

    it('returns null when commit fetch fails', function (): void {
        MockClient::global([
            GetCommitRequest::class => MockResponse::make(['message' => 'Not Found'], 404),
        ]);

        $service = new GitHubService('test_token');
        $result = $service->getCommit('owner', 'repo', 'nonexistent');

        expect($result)->toBeNull();
    });

    it('fetches repository information', function (): void {
        MockClient::global([
            GetRepositoryRequest::class => MockResponse::make([
                'id' => 123456789,
                'name' => 'repo',
                'full_name' => 'owner/repo',
                'description' => 'A test repository',
                'private' => false,
            ], 200),
        ]);

        $service = new GitHubService('test_token');
        $repo = $service->getRepository('owner', 'repo');

        expect($repo)->toBeArray()
            ->and($repo['id'])->toBe(123456789)
            ->and($repo['name'])->toBe('repo');
    });

    it('returns null when repository fetch fails', function (): void {
        MockClient::global([
            GetRepositoryRequest::class => MockResponse::make(['message' => 'Not Found'], 404),
        ]);

        $service = new GitHubService('test_token');
        $result = $service->getRepository('owner', 'nonexistent');

        expect($result)->toBeNull();
    });

    it('lists user repositories', function (): void {
        MockClient::global([
            ListUserRepositoriesRequest::class => MockResponse::make([
                ['id' => 1, 'name' => 'repo1', 'full_name' => 'user/repo1'],
                ['id' => 2, 'name' => 'repo2', 'full_name' => 'user/repo2'],
            ], 200),
        ]);

        $service = new GitHubService('test_token');
        $repos = $service->listUserRepositories();

        expect($repos)->toBeArray()
            ->and($repos)->toHaveCount(2);
    });

    it('returns empty array when listing repositories fails', function (): void {
        MockClient::global([
            ListUserRepositoriesRequest::class => MockResponse::make(['message' => 'Unauthorized'], 401),
        ]);

        $service = new GitHubService('test_token');
        $result = $service->listUserRepositories();

        expect($result)->toBeArray()
            ->and($result)->toBeEmpty();
    });

    it('creates a webhook', function (): void {
        MockClient::global([
            CreateWebhookRequest::class => MockResponse::make([
                'id' => 12345678,
                'name' => 'web',
                'active' => true,
                'events' => ['push', 'pull_request'],
                'config' => [
                    'url' => 'https://example.com/webhook',
                    'content_type' => 'json',
                ],
            ], 201),
        ]);

        $service = new GitHubService('test_token');
        $result = $service->createWebhook('owner', 'repo', 'https://example.com/webhook');

        expect($result)->toBeArray()
            ->and($result['id'])->toBe('12345678')
            ->and($result['secret'])->toBeString()
            ->and(strlen($result['secret']))->toBe(64); // 32 bytes = 64 hex chars
    });

    it('returns null when webhook creation fails', function (): void {
        MockClient::global([
            CreateWebhookRequest::class => MockResponse::make(['message' => 'Forbidden'], 403),
        ]);

        $service = new GitHubService('test_token');
        $result = $service->createWebhook('owner', 'repo', 'https://example.com/webhook');

        expect($result)->toBeNull();
    });

    it('deletes a webhook', function (): void {
        MockClient::global([
            DeleteWebhookRequest::class => MockResponse::make('', 204),
        ]);

        $service = new GitHubService('test_token');
        $result = $service->deleteWebhook('owner', 'repo', '12345');

        expect($result)->toBeTrue();
    });

    it('returns true when deleting non-existent webhook', function (): void {
        MockClient::global([
            DeleteWebhookRequest::class => MockResponse::make(['message' => 'Not Found'], 404),
        ]);

        $service = new GitHubService('test_token');
        $result = $service->deleteWebhook('owner', 'repo', 'nonexistent');

        expect($result)->toBeTrue();
    });

    it('gets authenticated user information', function (): void {
        MockClient::global([
            GetAuthenticatedUserRequest::class => MockResponse::make([
                'id' => 12345,
                'login' => 'johndoe',
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ], 200),
        ]);

        $service = new GitHubService('test_token');
        $user = $service->getAuthenticatedUser();

        expect($user)->toBeArray()
            ->and($user['login'])->toBe('johndoe');
    });

    it('returns true when user has repository access', function (): void {
        MockClient::global([
            GetRepositoryRequest::class => MockResponse::make(['id' => 1], 200),
        ]);

        $service = new GitHubService('test_token');

        expect($service->hasRepositoryAccess('owner', 'accessible'))->toBeTrue();
    });

    it('returns false when user has no repository access', function (): void {
        MockClient::global([
            GetRepositoryRequest::class => MockResponse::make(['message' => 'Not Found'], 404),
        ]);

        $service = new GitHubService('test_token');

        expect($service->hasRepositoryAccess('owner', 'inaccessible'))->toBeFalse();
    });

    it('works without token for public endpoints', function (): void {
        MockClient::global([
            GetRepositoryRequest::class => MockResponse::make(['id' => 1], 200),
        ]);

        $service = new GitHubService;
        $result = $service->getRepository('owner', 'public-repo');

        expect($result)->toBeArray()
            ->and($result['id'])->toBe(1);
    });
});
