<?php

declare(strict_types=1);

use App\DTOs\GitHubUserData;

describe('GitHubUserData DTO', function (): void {
    it('can be instantiated with all properties', function (): void {
        $dto = new GitHubUserData(
            id: '12345',
            username: 'johndoe',
            name: 'John Doe',
            email: 'john@example.com',
            avatar: 'https://avatars.githubusercontent.com/u/12345',
            token: 'gho_xxxxxxxxxxxx',
        );

        expect($dto->id)->toBe('12345')
            ->and($dto->username)->toBe('johndoe')
            ->and($dto->name)->toBe('John Doe')
            ->and($dto->email)->toBe('john@example.com')
            ->and($dto->avatar)->toBe('https://avatars.githubusercontent.com/u/12345')
            ->and($dto->token)->toBe('gho_xxxxxxxxxxxx');
    });

    it('allows nullable properties', function (): void {
        $dto = new GitHubUserData(
            id: '12345',
            username: 'johndoe',
            name: null,
            email: null,
            avatar: null,
            token: null,
        );

        expect($dto->name)->toBeNull()
            ->and($dto->email)->toBeNull()
            ->and($dto->avatar)->toBeNull()
            ->and($dto->token)->toBeNull();
    });

    it('returns display name as name when available', function (): void {
        $dto = new GitHubUserData(
            id: '12345',
            username: 'johndoe',
            name: 'John Doe',
            email: null,
            avatar: null,
            token: null,
        );

        expect($dto->displayName())->toBe('John Doe');
    });

    it('returns display name as username when name is null', function (): void {
        $dto = new GitHubUserData(
            id: '12345',
            username: 'johndoe',
            name: null,
            email: null,
            avatar: null,
            token: null,
        );

        expect($dto->displayName())->toBe('johndoe');
    });

    it('converts to array for database storage', function (): void {
        $dto = new GitHubUserData(
            id: '12345',
            username: 'johndoe',
            name: 'John Doe',
            email: 'john@example.com',
            avatar: 'https://avatars.githubusercontent.com/u/12345',
            token: 'gho_xxxxxxxxxxxx',
        );

        $array = $dto->toArray();

        expect($array)->toBeArray()
            ->and($array['github_id'])->toBe('12345')
            ->and($array['github_username'])->toBe('johndoe')
            ->and($array['github_token'])->toBe('gho_xxxxxxxxxxxx')
            ->and($array['avatar_url'])->toBe('https://avatars.githubusercontent.com/u/12345');
    });

    it('is immutable (readonly)', function (): void {
        $reflection = new ReflectionClass(GitHubUserData::class);

        expect($reflection->isReadOnly())->toBeTrue();
    });

    it('is final', function (): void {
        $reflection = new ReflectionClass(GitHubUserData::class);

        expect($reflection->isFinal())->toBeTrue();
    });
});
