<?php

declare(strict_types=1);

use App\Constants\CommitType;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use App\Queries\Dashboard\CommitTypeDistributionQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('CommitTypeDistributionQuery', function () {
    describe('grouping', function () {
        it('groups commits by type', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()->count(3)->feature()->for($user)->for($repo)->create();
            Commit::factory()->count(2)->fix()->for($user)->for($repo)->create();
            Commit::factory()->count(1)->docs()->for($user)->for($repo)->create();

            $query = new CommitTypeDistributionQuery($user);
            $result = $query->get();

            expect($result)->toHaveCount(3);

            $featEntry = $result->firstWhere('type', 'feat');
            $fixEntry = $result->firstWhere('type', 'fix');
            $docsEntry = $result->firstWhere('type', 'docs');

            expect($featEntry['count'])->toBe(3)
                ->and($fixEntry['count'])->toBe(2)
                ->and($docsEntry['count'])->toBe(1);
        });

        it('returns empty collection when user has no commits', function () {
            $user = User::factory()->create();

            $query = new CommitTypeDistributionQuery($user);
            $result = $query->get();

            expect($result)->toBeEmpty();
        });
    });

    describe('colors', function () {
        it('includes correct hex colors for each type', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()->feature()->for($user)->for($repo)->create();
            Commit::factory()->fix()->for($user)->for($repo)->create();
            Commit::factory()->docs()->for($user)->for($repo)->create();

            $query = new CommitTypeDistributionQuery($user);
            $result = $query->get();

            $featEntry = $result->firstWhere('type', 'feat');
            $fixEntry = $result->firstWhere('type', 'fix');
            $docsEntry = $result->firstWhere('type', 'docs');

            expect($featEntry['color'])->toBe('#16a34a')  // green-600
                ->and($fixEntry['color'])->toBe('#dc2626')   // red-600
                ->and($docsEntry['color'])->toBe('#2563eb'); // blue-600
        });

        it('includes display labels from CommitType enum', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()->feature()->for($user)->for($repo)->create();
            Commit::factory()->fix()->for($user)->for($repo)->create();

            $query = new CommitTypeDistributionQuery($user);
            $result = $query->get();

            $featEntry = $result->firstWhere('type', 'feat');
            $fixEntry = $result->firstWhere('type', 'fix');

            expect($featEntry['label'])->toBe('Feature')
                ->and($fixEntry['label'])->toBe('Bug Fix');
        });
    });

    describe('ordering', function () {
        it('orders results by count descending', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()->count(1)->docs()->for($user)->for($repo)->create();
            Commit::factory()->count(5)->feature()->for($user)->for($repo)->create();
            Commit::factory()->count(3)->fix()->for($user)->for($repo)->create();

            $query = new CommitTypeDistributionQuery($user);
            $result = $query->get();

            $counts = $result->pluck('count')->toArray();

            expect($counts)->toBe([5, 3, 1]);
        });
    });

    describe('date filtering', function () {
        it('filters by date range when provided', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            // Commits in range
            Commit::factory()
                ->count(3)
                ->feature()
                ->for($user)
                ->for($repo)
                ->committedOn(Carbon::now()->subDays(5))
                ->create();

            // Commits outside range
            Commit::factory()
                ->count(5)
                ->feature()
                ->for($user)
                ->for($repo)
                ->committedOn(Carbon::now()->subDays(60))
                ->create();

            $query = new CommitTypeDistributionQuery(
                $user,
                startDate: Carbon::now()->subDays(30),
                endDate: Carbon::now(),
            );
            $result = $query->get();

            $featEntry = $result->firstWhere('type', 'feat');

            expect($featEntry['count'])->toBe(3);
        });
    });

    describe('user isolation', function () {
        it('only counts commits belonging to the user', function () {
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()->count(2)->feature()->for($user)->for($repo)->create();
            Commit::factory()->count(10)->feature()->for($otherUser)->for($repo)->create();

            $query = new CommitTypeDistributionQuery($user);
            $result = $query->get();

            $featEntry = $result->firstWhere('type', 'feat');

            expect($featEntry['count'])->toBe(2);
        });
    });

    describe('structure', function () {
        it('returns correct data structure for each entry', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()->feature()->for($user)->for($repo)->create();

            $query = new CommitTypeDistributionQuery($user);
            $result = $query->get();

            expect($result->first())->toHaveKeys(['type', 'label', 'count', 'color']);
        });

        it('handles "other" commit type correctly', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            // Create commit with 'other' type
            Commit::factory()->for($user)->for($repo)->create([
                'commit_type' => CommitType::Other,
            ]);

            $query = new CommitTypeDistributionQuery($user);
            $result = $query->get();

            $entry = $result->first();

            expect($entry['type'])->toBe('other')
                ->and($entry['label'])->toBe('Other')
                ->and($entry['color'])->toBe('#64748b');
        });
    });
});
