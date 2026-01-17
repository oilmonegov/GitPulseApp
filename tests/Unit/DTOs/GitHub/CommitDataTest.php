<?php

declare(strict_types=1);

use App\Constants\CommitType;
use App\DTOs\GitHub\CommitData;

describe('CommitData DTO', function (): void {
    it('can be instantiated from webhook payload', function (): void {
        $payload = [
            'id' => 'abc123def456',
            'message' => 'feat(auth): add login functionality',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123def456',
            'author' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);

        expect($dto->sha)->toBe('abc123def456')
            ->and($dto->message)->toBe('feat(auth): add login functionality')
            ->and($dto->authorName)->toBe('John Doe')
            ->and($dto->authorEmail)->toBe('john@example.com')
            ->and($dto->distinct)->toBeTrue();
    });

    it('parses commit type from conventional commit message', function (): void {
        $payload = [
            'id' => 'abc123',
            'message' => 'feat: add new feature',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);

        expect($dto->parseType())->toBe(CommitType::Feat);
    });

    it('parses commit type for fix commits', function (): void {
        $payload = [
            'id' => 'abc123',
            'message' => 'fix: resolve login bug',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);

        expect($dto->parseType())->toBe(CommitType::Fix);
    });

    it('infers type from non-conventional commits', function (): void {
        $payload = [
            'id' => 'abc123',
            'message' => 'updated something',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);

        // 'updated' is a keyword for Chore type
        expect($dto->parseType())->toBe(CommitType::Chore);
    });

    it('parses scope from conventional commit message', function (): void {
        $payload = [
            'id' => 'abc123',
            'message' => 'feat(authentication): add OAuth support',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);

        expect($dto->parseScope())->toBe('authentication');
    });

    it('returns null scope for commits without scope', function (): void {
        $payload = [
            'id' => 'abc123',
            'message' => 'feat: add feature without scope',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);

        expect($dto->parseScope())->toBeNull();
    });

    it('detects merge commits', function (): void {
        $mergePayload = [
            'id' => 'abc123',
            'message' => "Merge pull request #42 from user/feature-branch\n\nAdd new feature",
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $normalPayload = [
            'id' => 'def456',
            'message' => 'feat: normal commit',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/def456',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $mergeDto = CommitData::fromWebhook($mergePayload);
        $normalDto = CommitData::fromWebhook($normalPayload);

        expect($mergeDto->isMergeCommit())->toBeTrue()
            ->and($normalDto->isMergeCommit())->toBeFalse();
    });

    it('extracts external references from commit message', function (): void {
        $payload = [
            'id' => 'abc123',
            'message' => 'fix: resolve issue #42 and PR #123',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);
        $refs = $dto->extractExternalRefs();

        expect($refs)->toHaveCount(2)
            ->and($refs[0]['type'])->toBe('github')
            ->and($refs[0]['id'])->toBe('#42')
            ->and($refs[1]['type'])->toBe('github')
            ->and($refs[1]['id'])->toBe('#123');
    });

    it('gets commit title from message', function (): void {
        $payload = [
            'id' => 'abc123',
            'message' => "feat: add new feature\n\nThis is the body of the commit message.",
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123',
            'author' => ['name' => 'John', 'email' => 'j@e.com'],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);

        expect($dto->getTitle())->toBe('feat: add new feature');
    });

    it('converts to array for database storage', function (): void {
        $payload = [
            'id' => 'abc123def456',
            'message' => 'feat: add new feature',
            'timestamp' => '2026-01-17T10:30:00Z',
            'url' => 'https://github.com/user/repo/commit/abc123def456',
            'author' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'distinct' => true,
        ];

        $dto = CommitData::fromWebhook($payload);
        $array = $dto->toArray();

        expect($array)->toBeArray()
            ->and($array['sha'])->toBe('abc123def456')
            ->and($array['message'])->toBe('feat: add new feature')
            ->and($array['author_name'])->toBe('John Doe')
            ->and($array['author_email'])->toBe('john@example.com')
            ->and($array['commit_type'])->toBe(CommitType::Feat)
            ->and($array['is_merge'])->toBeFalse();
    });

    it('handles API response format', function (): void {
        $apiResponse = [
            'sha' => 'abc123def456',
            'commit' => [
                'message' => 'docs: update README',
                'author' => [
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'date' => '2026-01-17T10:30:00Z',
                ],
            ],
            'html_url' => 'https://github.com/user/repo/commit/abc123def456',
            'stats' => [
                'additions' => 50,
                'deletions' => 10,
            ],
            'files' => [
                ['filename' => 'README.md', 'status' => 'modified', 'additions' => 40, 'deletions' => 5, 'changes' => 45],
                ['filename' => 'docs/guide.md', 'status' => 'added', 'additions' => 10, 'deletions' => 5, 'changes' => 15],
            ],
        ];

        $dto = CommitData::fromApi($apiResponse);

        expect($dto->sha)->toBe('abc123def456')
            ->and($dto->message)->toBe('docs: update README')
            ->and($dto->authorName)->toBe('Jane Doe')
            ->and($dto->authorEmail)->toBe('jane@example.com')
            ->and($dto->additions)->toBe(50)
            ->and($dto->deletions)->toBe(10)
            ->and($dto->files)->toHaveCount(2);
    });

    it('is immutable (readonly)', function (): void {
        $reflection = new ReflectionClass(CommitData::class);

        expect($reflection->isReadOnly())->toBeTrue();
    });

    it('is final', function (): void {
        $reflection = new ReflectionClass(CommitData::class);

        expect($reflection->isFinal())->toBeTrue();
    });
});
