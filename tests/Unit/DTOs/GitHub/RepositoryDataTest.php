<?php

declare(strict_types=1);

use App\DTOs\GitHub\RepositoryData;

describe('RepositoryData DTO', function (): void {
    it('can be instantiated from webhook payload', function (): void {
        $payload = [
            'id' => 123456789,
            'name' => 'my-repo',
            'full_name' => 'johndoe/my-repo',
            'description' => 'A sample repository',
            'default_branch' => 'main',
            'language' => 'PHP',
            'private' => false,
            'html_url' => 'https://github.com/johndoe/my-repo',
        ];

        $dto = RepositoryData::fromWebhook($payload);

        expect($dto->id)->toBe('123456789')
            ->and($dto->name)->toBe('my-repo')
            ->and($dto->fullName)->toBe('johndoe/my-repo')
            ->and($dto->description)->toBe('A sample repository')
            ->and($dto->defaultBranch)->toBe('main')
            ->and($dto->language)->toBe('PHP')
            ->and($dto->isPrivate)->toBeFalse()
            ->and($dto->htmlUrl)->toBe('https://github.com/johndoe/my-repo');
    });

    it('handles private repositories', function (): void {
        $payload = [
            'id' => 123456789,
            'name' => 'private-repo',
            'full_name' => 'johndoe/private-repo',
            'private' => true,
            'html_url' => 'https://github.com/johndoe/private-repo',
        ];

        $dto = RepositoryData::fromWebhook($payload);

        expect($dto->isPrivate)->toBeTrue();
    });

    it('handles null description and language', function (): void {
        $payload = [
            'id' => 123456789,
            'name' => 'empty-repo',
            'full_name' => 'johndoe/empty-repo',
            'description' => null,
            'language' => null,
            'private' => false,
            'html_url' => 'https://github.com/johndoe/empty-repo',
        ];

        $dto = RepositoryData::fromWebhook($payload);

        expect($dto->description)->toBeNull()
            ->and($dto->language)->toBeNull();
    });

    it('uses default branch when not specified', function (): void {
        $payload = [
            'id' => 123456789,
            'name' => 'repo',
            'full_name' => 'johndoe/repo',
            'private' => false,
            'html_url' => 'https://github.com/johndoe/repo',
        ];

        $dto = RepositoryData::fromWebhook($payload);

        expect($dto->defaultBranch)->toBe('main');
    });

    it('converts to array for database storage', function (): void {
        $payload = [
            'id' => 123456789,
            'name' => 'my-repo',
            'full_name' => 'johndoe/my-repo',
            'description' => 'A sample repository',
            'default_branch' => 'main',
            'language' => 'PHP',
            'private' => true,
            'html_url' => 'https://github.com/johndoe/my-repo',
        ];

        $dto = RepositoryData::fromWebhook($payload);
        $array = $dto->toArray();

        expect($array)->toBeArray()
            ->and($array['github_id'])->toBe('123456789')
            ->and($array['name'])->toBe('my-repo')
            ->and($array['full_name'])->toBe('johndoe/my-repo')
            ->and($array['description'])->toBe('A sample repository')
            ->and($array['default_branch'])->toBe('main')
            ->and($array['language'])->toBe('PHP')
            ->and($array['is_private'])->toBeTrue();
    });

    it('is immutable (readonly)', function (): void {
        $reflection = new ReflectionClass(RepositoryData::class);

        expect($reflection->isReadOnly())->toBeTrue();
    });

    it('is final', function (): void {
        $reflection = new ReflectionClass(RepositoryData::class);

        expect($reflection->isFinal())->toBeTrue();
    });
});
