<?php

declare(strict_types=1);

use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Repository Model', function (): void {
    it('can be created with factory', function (): void {
        $repository = Repository::factory()->create();

        expect($repository)->toBeInstanceOf(Repository::class)
            ->and($repository->id)->toBeInt()
            ->and($repository->name)->toBeString()
            ->and($repository->full_name)->toBeString()
            ->and($repository->github_id)->toBeString();
    });

    it('belongs to a user', function (): void {
        $user = User::factory()->create();
        $repository = Repository::factory()->for($user)->create();

        expect($repository->user)->toBeInstanceOf(User::class)
            ->and($repository->user->id)->toBe($user->id);
    });

    it('has many commits', function (): void {
        $repository = Repository::factory()->create();
        Commit::factory()->count(3)->for($repository)->create();

        expect($repository->commits)->toHaveCount(3)
            ->and($repository->commits->first())->toBeInstanceOf(Commit::class);
    });

    it('casts booleans correctly', function (): void {
        $repository = Repository::factory()->create([
            'is_active' => true,
            'is_private' => false,
        ]);

        expect($repository->is_active)->toBeBool()->toBeTrue()
            ->and($repository->is_private)->toBeBool()->toBeFalse();
    });

    it('casts dates to immutable datetime', function (): void {
        $repository = Repository::factory()->synced()->create();

        expect($repository->last_sync_at)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($repository->created_at)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($repository->updated_at)->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('hides webhook_secret in serialization', function (): void {
        $repository = Repository::factory()->withWebhook()->create();
        $array = $repository->toArray();

        expect($array)->not->toHaveKey('webhook_secret');
    });

    describe('scopes', function (): void {
        it('filters active repositories', function (): void {
            Repository::factory()->count(2)->create(['is_active' => true]);
            Repository::factory()->create(['is_active' => false]);

            $active = Repository::active()->get();

            expect($active)->toHaveCount(2);
        });

        it('filters public repositories', function (): void {
            Repository::factory()->count(2)->create(['is_private' => false]);
            Repository::factory()->create(['is_private' => true]);

            $public = Repository::public()->get();

            expect($public)->toHaveCount(2);
        });

        it('filters private repositories', function (): void {
            Repository::factory()->count(2)->create(['is_private' => false]);
            Repository::factory()->create(['is_private' => true]);

            $private = Repository::private()->get();

            expect($private)->toHaveCount(1);
        });

        it('filters repositories with webhooks', function (): void {
            Repository::factory()->count(2)->withWebhook()->create();
            Repository::factory()->create(['webhook_id' => null]);

            $withWebhook = Repository::withWebhook()->get();

            expect($withWebhook)->toHaveCount(2);
        });
    });

    describe('methods', function (): void {
        it('checks if repository has webhook', function (): void {
            $withWebhook = Repository::factory()->withWebhook()->create();
            $withoutWebhook = Repository::factory()->create(['webhook_id' => null]);

            expect($withWebhook->hasWebhook())->toBeTrue()
                ->and($withoutWebhook->hasWebhook())->toBeFalse();
        });

        it('generates GitHub URL', function (): void {
            $repository = Repository::factory()->create([
                'full_name' => 'owner/repo',
            ]);

            expect($repository->github_url)->toBe('https://github.com/owner/repo');
        });

        it('counts total commits', function (): void {
            $repository = Repository::factory()->create();
            Commit::factory()->count(5)->for($repository)->create();

            expect($repository->total_commits)->toBe(5);
        });
    });

    describe('factory states', function (): void {
        it('creates private repository', function (): void {
            $repository = Repository::factory()->private()->create();

            expect($repository->is_private)->toBeTrue();
        });

        it('creates inactive repository', function (): void {
            $repository = Repository::factory()->inactive()->create();

            expect($repository->is_active)->toBeFalse();
        });

        it('creates repository with webhook', function (): void {
            $repository = Repository::factory()->withWebhook()->create();

            expect($repository->webhook_id)->not->toBeNull()
                ->and($repository->webhook_secret)->not->toBeNull();
        });

        it('creates synced repository', function (): void {
            $repository = Repository::factory()->synced()->create();

            expect($repository->last_sync_at)->not->toBeNull();
        });

        it('creates Laravel repository', function (): void {
            $repository = Repository::factory()->laravel()->create();

            expect($repository->language)->toBe('PHP');
        });
    });
});
