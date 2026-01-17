<?php

declare(strict_types=1);

use App\Jobs\ProcessGitHubPushJob;
use App\Models\Commit;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('ProcessGitHubPushJob', function (): void {
    it('can be dispatched to queue', function (): void {
        Queue::fake();

        $payload = createJobPayload();

        ProcessGitHubPushJob::dispatch($payload);

        Queue::assertPushed(ProcessGitHubPushJob::class, function ($job) {
            return $job->payload['repository']['name'] === 'my-repo';
        });
    });

    it('processes push event and stores commits', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);

        $payload = createJobPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
        ]);

        $job = new ProcessGitHubPushJob($payload);
        $job->handle();

        expect(Commit::query()->count())->toBe(2);
        $this->assertDatabaseHas('commits', ['sha' => 'abc123']);
        $this->assertDatabaseHas('commits', ['sha' => 'def456']);
    });

    it('creates repository if it does not exist', function (): void {
        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);

        $payload = createJobPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
        ]);

        $job = new ProcessGitHubPushJob($payload);
        $job->handle();

        $this->assertDatabaseHas('repositories', [
            'github_id' => '123456789',
            'name' => 'my-repo',
        ]);
    });

    it('logs success message after processing', function (): void {
        Log::spy();

        $user = User::factory()->withGitHub()->create([
            'github_id' => '12345',
        ]);

        $payload = createJobPayload([
            'sender' => ['login' => $user->github_username, 'id' => 12345],
        ]);

        $job = new ProcessGitHubPushJob($payload);
        $job->handle();

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message) => str_contains($message, 'Push event processed successfully'))
            ->once();
    });

    it('handles unknown users gracefully', function (): void {
        Log::spy();

        $payload = createJobPayload([
            'sender' => ['login' => 'unknown-user', 'id' => 99999],
        ]);

        $job = new ProcessGitHubPushJob($payload);
        $job->handle();

        expect(Commit::query()->count())->toBe(0);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn ($message) => str_contains($message, 'Push event received for unknown user'))
            ->once();
    });

    it('logs failure information when job fails', function (): void {
        Log::spy();

        $payload = createJobPayload();
        $exception = new \Exception('Test failure');

        $job = new ProcessGitHubPushJob($payload);
        $job->failed($exception);

        Log::shouldHaveReceived('error')
            ->withArgs(fn ($message, $context) => str_contains($message, 'Failed to process GitHub push event')
                && $context['error'] === 'Test failure')
            ->once();
    });

    it('has correct retry configuration', function (): void {
        $job = new ProcessGitHubPushJob([]);

        expect($job->tries)->toBe(3)
            ->and($job->backoff)->toBe(60);
    });

    it('is a queued job', function (): void {
        $reflection = new ReflectionClass(ProcessGitHubPushJob::class);

        expect($reflection->implementsInterface(\Illuminate\Contracts\Queue\ShouldQueue::class))->toBeTrue();
    });

    it('can accept fetchFullCommitData parameter', function (): void {
        $payload = createJobPayload();

        $job = new ProcessGitHubPushJob($payload, true);

        expect($job->fetchFullCommitData)->toBeTrue();

        $jobDefault = new ProcessGitHubPushJob($payload);
        expect($jobDefault->fetchFullCommitData)->toBeFalse();
    });
});

/**
 * Create a sample push event payload for job testing.
 *
 * @param  array<string, mixed>  $overrides
 *
 * @return array<string, mixed>
 */
function createJobPayload(array $overrides = []): array
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
            [
                'id' => 'abc123',
                'message' => 'feat: first commit',
                'timestamp' => '2026-01-17T10:00:00Z',
                'url' => 'https://github.com/johndoe/my-repo/commit/abc123',
                'author' => ['name' => 'John Doe', 'email' => 'john@example.com'],
                'distinct' => true,
            ],
            [
                'id' => 'def456',
                'message' => 'fix: second commit',
                'timestamp' => '2026-01-17T10:05:00Z',
                'url' => 'https://github.com/johndoe/my-repo/commit/def456',
                'author' => ['name' => 'John Doe', 'email' => 'john@example.com'],
                'distinct' => true,
            ],
        ],
        'created' => false,
        'deleted' => false,
        'forced' => false,
    ], $overrides);
}
