<?php

declare(strict_types=1);

use App\Actions\GitHub\StoreCommitAction;
use App\Constants\CommitType;
use App\DTOs\GitHub\CommitData;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('StoreCommitAction', function (): void {
    it('stores a new commit from webhook data', function (): void {
        $user = User::factory()->withGitHub()->create();
        $repository = Repository::factory()->for($user)->create();

        $commitData = CommitData::fromWebhook([
            'id' => 'abc123def456',
            'message' => 'feat: add new feature',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123def456',
            'author' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'distinct' => true,
        ]);

        $commit = (new StoreCommitAction($repository, $user, $commitData))->execute();

        expect($commit)->toBeInstanceOf(Commit::class)
            ->and($commit->sha)->toBe('abc123def456')
            ->and($commit->message)->toBe('feat: add new feature')
            ->and($commit->author_name)->toBe('John Doe')
            ->and($commit->author_email)->toBe('john@example.com')
            ->and($commit->commit_type)->toBe(CommitType::Feat)
            ->and($commit->repository_id)->toBe($repository->id)
            ->and($commit->user_id)->toBe($user->id);

        $this->assertDatabaseHas('commits', [
            'sha' => 'abc123def456',
            'repository_id' => $repository->id,
            'user_id' => $user->id,
        ]);
    });

    it('returns null for duplicate commits (idempotency)', function (): void {
        $user = User::factory()->withGitHub()->create();
        $repository = Repository::factory()->for($user)->create();

        // Create existing commit
        Commit::factory()->for($repository)->for($user)->create([
            'sha' => 'existing123',
        ]);

        $commitData = CommitData::fromWebhook([
            'id' => 'existing123',
            'message' => 'feat: same commit',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/existing123',
            'author' => ['name' => 'John', 'email' => 'john@example.com'],
            'distinct' => true,
        ]);

        $result = (new StoreCommitAction($repository, $user, $commitData))->execute();

        expect($result)->toBeNull();

        // Should still only have one commit with this sha
        expect(Commit::query()->where('sha', 'existing123')->count())->toBe(1);
    });

    it('stores commit with correct type parsing', function (): void {
        $user = User::factory()->withGitHub()->create();
        $repository = Repository::factory()->for($user)->create();

        $types = [
            ['message' => 'fix: resolve bug', 'expected' => CommitType::Fix],
            ['message' => 'docs: update readme', 'expected' => CommitType::Docs],
            ['message' => 'test: add unit tests', 'expected' => CommitType::Test],
            ['message' => 'refactor: clean up code', 'expected' => CommitType::Refactor],
        ];

        foreach ($types as $index => $type) {
            $commitData = CommitData::fromWebhook([
                'id' => 'sha' . $index,
                'message' => $type['message'],
                'timestamp' => '2026-01-17T10:30:00Z',
                'url' => 'https://github.com/user/repo/commit/sha' . $index,
                'author' => ['name' => 'John', 'email' => 'john@example.com'],
                'distinct' => true,
            ]);

            $commit = (new StoreCommitAction($repository, $user, $commitData))->execute();

            expect($commit->commit_type)->toBe($type['expected']);
        }
    });

    it('stores commit with scope', function (): void {
        $user = User::factory()->withGitHub()->create();
        $repository = Repository::factory()->for($user)->create();

        $commitData = CommitData::fromWebhook([
            'id' => 'scoped123',
            'message' => 'feat(auth): add OAuth support',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/scoped123',
            'author' => ['name' => 'John', 'email' => 'john@example.com'],
            'distinct' => true,
        ]);

        $commit = (new StoreCommitAction($repository, $user, $commitData))->execute();

        expect($commit->scope)->toBe('auth');
    });

    it('marks merge commits correctly', function (): void {
        $user = User::factory()->withGitHub()->create();
        $repository = Repository::factory()->for($user)->create();

        $commitData = CommitData::fromWebhook([
            'id' => 'merge123',
            'message' => 'Merge pull request #42 from feature-branch',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/merge123',
            'author' => ['name' => 'John', 'email' => 'john@example.com'],
            'distinct' => true,
        ]);

        $commit = (new StoreCommitAction($repository, $user, $commitData))->execute();

        expect($commit->is_merge)->toBeTrue();
    });
});
