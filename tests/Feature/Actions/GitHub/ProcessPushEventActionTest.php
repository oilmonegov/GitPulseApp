<?php

declare(strict_types=1);

use App\Actions\GitHub\ProcessPushEventAction;
use App\DTOs\GitHub\PushEventData;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('ProcessPushEventAction', function (): void {
    it('processes push event and stores commits', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);

        $payload = createPushPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
        ]);

        $pushEvent = PushEventData::fromWebhook($payload);
        $storedCommits = (new ProcessPushEventAction($pushEvent))->execute();

        expect($storedCommits)->toHaveCount(2);
        expect(Commit::query()->count())->toBe(2);

        $this->assertDatabaseHas('commits', ['sha' => 'abc123']);
        $this->assertDatabaseHas('commits', ['sha' => 'def456']);
    });

    it('creates repository if it does not exist', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);

        $payload = createPushPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
            'repository' => [
                'id' => 999888777,
                'name' => 'new-repo',
                'full_name' => 'johndoe/new-repo',
                'description' => 'A new repository',
                'default_branch' => 'main',
                'language' => 'PHP',
                'private' => false,
                'html_url' => 'https://github.com/johndoe/new-repo',
            ],
        ]);

        $pushEvent = PushEventData::fromWebhook($payload);
        (new ProcessPushEventAction($pushEvent))->execute();

        $this->assertDatabaseHas('repositories', [
            'github_id' => '999888777',
            'name' => 'new-repo',
            'user_id' => $user->id,
        ]);
    });

    it('returns empty collection for unknown user', function (): void {
        $payload = createPushPayload([
            'sender' => ['login' => 'unknown-user', 'id' => 99999],
        ]);

        $pushEvent = PushEventData::fromWebhook($payload);
        $result = (new ProcessPushEventAction($pushEvent))->execute();

        expect($result)->toBeEmpty();
        expect(Commit::query()->count())->toBe(0);
    });

    it('skips inactive repositories', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);
        Repository::factory()->for($user)->inactive()->create([
            'github_id' => '123456789',
        ]);

        $payload = createPushPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
        ]);

        $pushEvent = PushEventData::fromWebhook($payload);
        $result = (new ProcessPushEventAction($pushEvent))->execute();

        expect($result)->toBeEmpty();
        expect(Commit::query()->count())->toBe(0);
    });

    it('skips branch deletion events', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);

        $payload = createPushPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
            'deleted' => true,
            'commits' => [],
        ]);

        $pushEvent = PushEventData::fromWebhook($payload);
        $result = (new ProcessPushEventAction($pushEvent))->execute();

        expect($result)->toBeEmpty();
    });

    it('only stores distinct commits', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);

        $payload = createPushPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
            'commits' => [
                createCommitPayload(['id' => 'distinct1', 'distinct' => true]),
                createCommitPayload(['id' => 'notdistinct', 'distinct' => false]),
                createCommitPayload(['id' => 'distinct2', 'distinct' => true]),
            ],
        ]);

        $pushEvent = PushEventData::fromWebhook($payload);
        $result = (new ProcessPushEventAction($pushEvent))->execute();

        expect($result)->toHaveCount(2);
        $this->assertDatabaseHas('commits', ['sha' => 'distinct1']);
        $this->assertDatabaseHas('commits', ['sha' => 'distinct2']);
        $this->assertDatabaseMissing('commits', ['sha' => 'notdistinct']);
    });

    it('updates repository last_sync_at timestamp', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);
        $repository = Repository::factory()->for($user)->create([
            'github_id' => '123456789',
            'last_sync_at' => null,
        ]);

        $payload = createPushPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
        ]);

        $pushEvent = PushEventData::fromWebhook($payload);
        (new ProcessPushEventAction($pushEvent))->execute();

        $repository->refresh();
        expect($repository->last_sync_at)->not->toBeNull();
    });

    it('handles idempotent commit storage', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);
        $repository = Repository::factory()->for($user)->create([
            'github_id' => '123456789',
        ]);
        Commit::factory()->for($repository)->for($user)->create([
            'sha' => 'abc123',
        ]);

        $payload = createPushPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
        ]);

        $pushEvent = PushEventData::fromWebhook($payload);
        $result = (new ProcessPushEventAction($pushEvent))->execute();

        // Only one new commit should be stored (def456), abc123 already exists
        expect($result)->toHaveCount(1);
        expect(Commit::query()->count())->toBe(2);
    });
});

/**
 * Create a sample push event payload for testing.
 *
 * @param  array<string, mixed>  $overrides
 *
 * @return array<string, mixed>
 */
function createPushPayload(array $overrides = []): array
{
    return array_merge([
        'ref' => 'refs/heads/main',
        'before' => '0000000000000000000000000000000000000000',
        'after' => 'abc123def456',
        'repository' => [
            'id' => 123456789,
            'name' => 'my-repo',
            'full_name' => 'johndoe/my-repo',
            'description' => 'A sample repository',
            'default_branch' => 'main',
            'language' => 'PHP',
            'private' => false,
            'html_url' => 'https://github.com/johndoe/my-repo',
        ],
        'pusher' => [
            'name' => 'johndoe',
            'email' => 'john@example.com',
        ],
        'sender' => [
            'login' => 'johndoe',
            'id' => 12345,
        ],
        'commits' => [
            createCommitPayload(['id' => 'abc123', 'message' => 'feat: first commit']),
            createCommitPayload(['id' => 'def456', 'message' => 'fix: second commit']),
        ],
        'created' => false,
        'deleted' => false,
        'forced' => false,
    ], $overrides);
}

/**
 * Create a sample commit payload for testing.
 *
 * @param  array<string, mixed>  $overrides
 *
 * @return array<string, mixed>
 */
function createCommitPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 'commit123',
        'message' => 'feat: add feature',
        'timestamp' => '2026-01-17T10:00:00Z',
        'url' => 'https://github.com/johndoe/my-repo/commit/commit123',
        'author' => [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
        'distinct' => true,
    ], $overrides);
}
