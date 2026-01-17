<?php

declare(strict_types=1);

use App\DTOs\GitHub\CommitData;
use App\DTOs\GitHub\PushEventData;
use App\DTOs\GitHub\RepositoryData;

describe('PushEventData DTO', function (): void {
    it('can be instantiated from webhook payload', function (): void {
        $payload = createPushEventPayload();

        $dto = PushEventData::fromWebhook($payload);

        expect($dto->ref)->toBe('refs/heads/main')
            ->and($dto->before)->toBe('0000000000000000000000000000000000000000')
            ->and($dto->after)->toBe('abc123def456')
            ->and($dto->pusherName)->toBe('johndoe')
            ->and($dto->pusherEmail)->toBe('john@example.com')
            ->and($dto->senderLogin)->toBe('johndoe')
            ->and($dto->senderId)->toBe('12345')
            ->and($dto->created)->toBeFalse()
            ->and($dto->deleted)->toBeFalse()
            ->and($dto->forced)->toBeFalse();
    });

    it('extracts branch name from ref', function (): void {
        $payload = createPushEventPayload(['ref' => 'refs/heads/feature/new-feature']);

        $dto = PushEventData::fromWebhook($payload);

        expect($dto->getBranch())->toBe('feature/new-feature');
    });

    it('detects default branch pushes', function (): void {
        $mainPayload = createPushEventPayload(['ref' => 'refs/heads/main']);
        $featurePayload = createPushEventPayload(['ref' => 'refs/heads/feature']);

        $mainDto = PushEventData::fromWebhook($mainPayload);
        $featureDto = PushEventData::fromWebhook($featurePayload);

        expect($mainDto->isDefaultBranch())->toBeTrue()
            ->and($featureDto->isDefaultBranch())->toBeFalse();
    });

    it('detects if push has commits', function (): void {
        $withCommits = createPushEventPayload();
        $withoutCommits = createPushEventPayload(['commits' => []]);
        $deletedBranch = createPushEventPayload(['deleted' => true]);

        $withCommitsDto = PushEventData::fromWebhook($withCommits);
        $withoutCommitsDto = PushEventData::fromWebhook($withoutCommits);
        $deletedBranchDto = PushEventData::fromWebhook($deletedBranch);

        expect($withCommitsDto->hasCommits())->toBeTrue()
            ->and($withoutCommitsDto->hasCommits())->toBeFalse()
            ->and($deletedBranchDto->hasCommits())->toBeFalse();
    });

    it('parses commits from payload', function (): void {
        $payload = createPushEventPayload();

        $dto = PushEventData::fromWebhook($payload);

        expect($dto->commits)->toHaveCount(2)
            ->and($dto->commits[0])->toBeInstanceOf(CommitData::class)
            ->and($dto->commits[0]->sha)->toBe('abc123')
            ->and($dto->commits[1]->sha)->toBe('def456');
    });

    it('filters distinct commits only', function (): void {
        $payload = createPushEventPayload([
            'commits' => [
                ['id' => 'abc123', 'message' => 'First', 'distinct' => true, 'timestamp' => '2026-01-17T10:00:00Z', 'url' => 'https://github.com', 'author' => ['name' => 'John', 'email' => 'j@e.com'], 'added' => [], 'modified' => [], 'removed' => []],
                ['id' => 'def456', 'message' => 'Second', 'distinct' => false, 'timestamp' => '2026-01-17T10:00:00Z', 'url' => 'https://github.com', 'author' => ['name' => 'John', 'email' => 'j@e.com'], 'added' => [], 'modified' => [], 'removed' => []],
                ['id' => 'ghi789', 'message' => 'Third', 'distinct' => true, 'timestamp' => '2026-01-17T10:00:00Z', 'url' => 'https://github.com', 'author' => ['name' => 'John', 'email' => 'j@e.com'], 'added' => [], 'modified' => [], 'removed' => []],
            ],
        ]);

        $dto = PushEventData::fromWebhook($payload);
        $distinctCommits = $dto->getDistinctCommits();

        expect($distinctCommits)->toHaveCount(2)
            ->and($distinctCommits[0]->sha)->toBe('abc123')
            ->and($distinctCommits[2]->sha)->toBe('ghi789');
    });

    it('parses repository data', function (): void {
        $payload = createPushEventPayload();

        $dto = PushEventData::fromWebhook($payload);

        expect($dto->repository)->toBeInstanceOf(RepositoryData::class)
            ->and($dto->repository->fullName)->toBe('johndoe/my-repo')
            ->and($dto->repository->defaultBranch)->toBe('main');
    });

    it('handles branch creation events', function (): void {
        $payload = createPushEventPayload([
            'created' => true,
            'before' => '0000000000000000000000000000000000000000',
        ]);

        $dto = PushEventData::fromWebhook($payload);

        expect($dto->created)->toBeTrue();
    });

    it('handles branch deletion events', function (): void {
        $payload = createPushEventPayload([
            'deleted' => true,
            'after' => '0000000000000000000000000000000000000000',
            'commits' => [],
        ]);

        $dto = PushEventData::fromWebhook($payload);

        expect($dto->deleted)->toBeTrue()
            ->and($dto->hasCommits())->toBeFalse();
    });

    it('handles forced push events', function (): void {
        $payload = createPushEventPayload(['forced' => true]);

        $dto = PushEventData::fromWebhook($payload);

        expect($dto->forced)->toBeTrue();
    });

    it('is immutable (readonly)', function (): void {
        $reflection = new ReflectionClass(PushEventData::class);

        expect($reflection->isReadOnly())->toBeTrue();
    });

    it('is final', function (): void {
        $reflection = new ReflectionClass(PushEventData::class);

        expect($reflection->isFinal())->toBeTrue();
    });
});

/**
 * Create a sample push event payload for testing.
 *
 * @param  array<string, mixed>  $overrides
 *
 * @return array<string, mixed>
 */
function createPushEventPayload(array $overrides = []): array
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
                'author' => ['name' => 'John Doe', 'email' => 'john@example.com', 'username' => 'johndoe'],
                'added' => ['file1.php'],
                'modified' => [],
                'removed' => [],
                'distinct' => true,
            ],
            [
                'id' => 'def456',
                'message' => 'fix: second commit',
                'timestamp' => '2026-01-17T10:05:00Z',
                'url' => 'https://github.com/johndoe/my-repo/commit/def456',
                'author' => ['name' => 'John Doe', 'email' => 'john@example.com', 'username' => 'johndoe'],
                'added' => [],
                'modified' => ['file1.php'],
                'removed' => [],
                'distinct' => true,
            ],
        ],
        'created' => false,
        'deleted' => false,
        'forced' => false,
    ], $overrides);
}
