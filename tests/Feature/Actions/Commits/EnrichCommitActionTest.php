<?php

declare(strict_types=1);

use App\Actions\Commits\EnrichCommitAction;
use App\Constants\CommitType;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('EnrichCommitAction', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->repository = Repository::factory()->for($this->user)->create();
    });

    describe('conventional commit enrichment', function () {
        it('enriches a conventional commit', function () {
            $commit = Commit::factory()->for($this->repository)->for($this->user)->create([
                'message' => 'feat(auth): add OAuth login #123',
                'additions' => 100,
                'deletions' => 20,
                'files_changed' => 5,
                'commit_type' => CommitType::Other,
                'impact_score' => 0,
            ]);

            $action = new EnrichCommitAction($commit);
            $enrichedCommit = $action->execute();

            expect($enrichedCommit->commit_type)->toBe(CommitType::Feat)
                ->and($enrichedCommit->scope)->toBe('auth')
                ->and($enrichedCommit->external_refs)->toContain(['type' => 'github', 'id' => '#123'])
                ->and((float) $enrichedCommit->impact_score)->toBeGreaterThan(0)
                ->and($enrichedCommit->is_merge)->toBeFalse();
        });

        it('enriches a merge commit', function () {
            $commit = Commit::factory()->for($this->repository)->for($this->user)->create([
                'message' => 'Merge branch \'feature\' into main',
                'additions' => 200,
                'deletions' => 50,
                'files_changed' => 10,
            ]);

            $action = new EnrichCommitAction($commit);
            $enrichedCommit = $action->execute();

            expect($enrichedCommit->is_merge)->toBeTrue()
                ->and($enrichedCommit->commit_type)->toBe(CommitType::Other)
                ->and((float) $enrichedCommit->impact_score)->toBeGreaterThan(0);
        });

        it('enriches a non-conventional commit with inferred type', function () {
            $commit = Commit::factory()->for($this->repository)->for($this->user)->create([
                'message' => 'Fix bug in user registration',
                'additions' => 20,
                'deletions' => 5,
                'files_changed' => 2,
            ]);

            $action = new EnrichCommitAction($commit);
            $enrichedCommit = $action->execute();

            expect($enrichedCommit->commit_type)->toBe(CommitType::Fix);
        });
    });

    describe('impact score with repository average', function () {
        it('calculates score with repository average', function () {
            // Create some existing commits to establish average
            Commit::factory()->for($this->repository)->for($this->user)->count(5)->create([
                'additions' => 50,
                'deletions' => 10,
            ]);

            $newCommit = Commit::factory()->for($this->repository)->for($this->user)->create([
                'message' => 'feat: large feature',
                'additions' => 500,
                'deletions' => 100,
                'files_changed' => 20,
            ]);

            $avg = EnrichCommitAction::getRepositoryAverage($this->repository->id);
            $action = new EnrichCommitAction($newCommit, $avg);
            $enrichedCommit = $action->execute();

            // Large commit relative to average should have high impact
            expect((float) $enrichedCommit->impact_score)->toBeGreaterThan(5);
        });
    });

    describe('getRepositoryAverage', function () {
        it('returns null for empty repository', function () {
            $avg = EnrichCommitAction::getRepositoryAverage($this->repository->id);

            expect($avg)->toBeNull();
        });

        it('calculates correct average', function () {
            Commit::factory()->for($this->repository)->for($this->user)->create([
                'additions' => 100,
                'deletions' => 0,
            ]);

            Commit::factory()->for($this->repository)->for($this->user)->create([
                'additions' => 0,
                'deletions' => 100,
            ]);

            $avg = EnrichCommitAction::getRepositoryAverage($this->repository->id);

            // Average of (100+0) and (0+100) = 100
            expect($avg)->toBe(100.0);
        });
    });

    describe('external references', function () {
        it('extracts JIRA references', function () {
            $commit = Commit::factory()->for($this->repository)->for($this->user)->create([
                'message' => 'fix: resolve PROJ-123 database issue',
            ]);

            $action = new EnrichCommitAction($commit);
            $enrichedCommit = $action->execute();

            expect($enrichedCommit->external_refs)->toContain(['type' => 'jira', 'id' => 'PROJ-123']);
        });

        it('extracts multiple reference types', function () {
            $commit = Commit::factory()->for($this->repository)->for($this->user)->create([
                'message' => 'fix: resolve #123 for JIRA-456',
            ]);

            $action = new EnrichCommitAction($commit);
            $enrichedCommit = $action->execute();

            expect($enrichedCommit->external_refs)->toHaveCount(2);
        });

        it('stores null for commits without references', function () {
            $commit = Commit::factory()->for($this->repository)->for($this->user)->create([
                'message' => 'feat: add new feature',
            ]);

            $action = new EnrichCommitAction($commit);
            $enrichedCommit = $action->execute();

            expect($enrichedCommit->external_refs)->toBeNull();
        });
    });
});
