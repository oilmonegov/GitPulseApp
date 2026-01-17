<?php

declare(strict_types=1);

use App\Actions\GitHub\FindOrCreateRepositoryAction;
use App\DTOs\GitHub\RepositoryData;
use App\Models\Repository;
use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('FindOrCreateRepositoryAction', function (): void {
    it('creates a new repository from webhook data', function (): void {
        $user = User::factory()->withGitHub()->create();

        $repositoryData = RepositoryData::fromWebhook([
            'id' => 123456789,
            'name' => 'new-repo',
            'full_name' => 'johndoe/new-repo',
            'description' => 'A new repository',
            'default_branch' => 'main',
            'language' => 'PHP',
            'private' => false,
            'html_url' => 'https://github.com/johndoe/new-repo',
        ]);

        $repository = (new FindOrCreateRepositoryAction($user, $repositoryData))->execute();

        expect($repository)->toBeInstanceOf(Repository::class)
            ->and($repository->github_id)->toBe('123456789')
            ->and($repository->name)->toBe('new-repo')
            ->and($repository->full_name)->toBe('johndoe/new-repo')
            ->and($repository->description)->toBe('A new repository')
            ->and($repository->default_branch)->toBe('main')
            ->and($repository->language)->toBe('PHP')
            ->and($repository->is_private)->toBeFalse()
            ->and($repository->user_id)->toBe($user->id);

        $this->assertDatabaseHas('repositories', [
            'github_id' => '123456789',
            'user_id' => $user->id,
        ]);
    });

    it('finds existing repository by github_id', function (): void {
        $user = User::factory()->withGitHub()->create();
        $existingRepo = Repository::factory()->for($user)->create([
            'github_id' => '999888777',
            'name' => 'existing-repo',
            'full_name' => 'johndoe/existing-repo',
        ]);

        $repositoryData = RepositoryData::fromWebhook([
            'id' => 999888777,
            'name' => 'existing-repo',
            'full_name' => 'johndoe/existing-repo',
            'description' => 'Updated description',
            'default_branch' => 'main',
            'language' => 'PHP',
            'private' => false,
            'html_url' => 'https://github.com/johndoe/existing-repo',
        ]);

        $repository = (new FindOrCreateRepositoryAction($user, $repositoryData))->execute();

        expect($repository->id)->toBe($existingRepo->id)
            ->and($repository->description)->toBe('Updated description');

        // Should not create a duplicate
        expect(Repository::query()->where('github_id', '999888777')->count())->toBe(1);
    });

    it('updates existing repository with new data', function (): void {
        $user = User::factory()->withGitHub()->create();
        Repository::factory()->for($user)->create([
            'github_id' => '111222333',
            'name' => 'old-name',
            'full_name' => 'johndoe/old-name',
            'description' => 'Old description',
            'language' => 'JavaScript',
        ]);

        $repositoryData = RepositoryData::fromWebhook([
            'id' => 111222333,
            'name' => 'new-name',
            'full_name' => 'johndoe/new-name',
            'description' => 'New description',
            'default_branch' => 'main',
            'language' => 'TypeScript',
            'private' => true,
            'html_url' => 'https://github.com/johndoe/new-name',
        ]);

        $repository = (new FindOrCreateRepositoryAction($user, $repositoryData))->execute();

        expect($repository->name)->toBe('new-name')
            ->and($repository->full_name)->toBe('johndoe/new-name')
            ->and($repository->description)->toBe('New description')
            ->and($repository->language)->toBe('TypeScript')
            ->and($repository->is_private)->toBeTrue();
    });

    it('creates private repository correctly', function (): void {
        $user = User::factory()->withGitHub()->create();

        $repositoryData = RepositoryData::fromWebhook([
            'id' => 444555666,
            'name' => 'private-repo',
            'full_name' => 'johndoe/private-repo',
            'description' => null,
            'default_branch' => 'main',
            'language' => null,
            'private' => true,
            'html_url' => 'https://github.com/johndoe/private-repo',
        ]);

        $repository = (new FindOrCreateRepositoryAction($user, $repositoryData))->execute();

        expect($repository->is_private)->toBeTrue()
            ->and($repository->description)->toBeNull()
            ->and($repository->language)->toBeNull();
    });
});
