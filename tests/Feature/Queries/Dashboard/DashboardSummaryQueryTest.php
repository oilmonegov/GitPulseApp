<?php

declare(strict_types=1);

use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use App\Queries\Dashboard\DashboardSummaryQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('DashboardSummaryQuery', function () {
    describe('empty state', function () {
        it('returns zeros when user has no commits', function () {
            $user = User::factory()->create();

            $query = new DashboardSummaryQuery($user);
            $result = $query->get();

            expect($result)->toBe([
                'total_commits' => 0,
                'average_impact' => 0.0,
                'lines_changed' => 0,
            ]);
        });
    });

    describe('correct calculations', function () {
        it('calculates total commit count', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()
                ->count(5)
                ->for($user)
                ->for($repo)
                ->create();

            $query = new DashboardSummaryQuery($user);
            $result = $query->get();

            expect($result['total_commits'])->toBe(5);
        });

        it('calculates average impact score', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            // Create commits with known impact scores
            Commit::factory()->for($user)->for($repo)->create(['impact_score' => 2.0]);
            Commit::factory()->for($user)->for($repo)->create(['impact_score' => 4.0]);
            Commit::factory()->for($user)->for($repo)->create(['impact_score' => 6.0]);

            $query = new DashboardSummaryQuery($user);
            $result = $query->get();

            expect($result['average_impact'])->toBe(4.0);
        });

        it('calculates total lines changed', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()->for($user)->for($repo)->create([
                'additions' => 100,
                'deletions' => 50,
            ]);
            Commit::factory()->for($user)->for($repo)->create([
                'additions' => 200,
                'deletions' => 30,
            ]);

            $query = new DashboardSummaryQuery($user);
            $result = $query->get();

            // 100 + 50 + 200 + 30 = 380
            expect($result['lines_changed'])->toBe(380);
        });
    });

    describe('date filtering', function () {
        it('filters commits by date range', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            // Create commits in range
            Commit::factory()
                ->count(3)
                ->for($user)
                ->for($repo)
                ->committedOn(Carbon::now()->subDays(5))
                ->create();

            // Create commits outside range
            Commit::factory()
                ->count(2)
                ->for($user)
                ->for($repo)
                ->committedOn(Carbon::now()->subDays(60))
                ->create();

            $query = new DashboardSummaryQuery(
                $user,
                startDate: Carbon::now()->subDays(30),
                endDate: Carbon::now(),
            );
            $result = $query->get();

            expect($result['total_commits'])->toBe(3);
        });

        it('includes commits on boundary dates', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            $startDate = Carbon::now()->subDays(7)->startOfDay();
            $endDate = Carbon::now()->endOfDay();

            // Create commit exactly on start date
            Commit::factory()
                ->for($user)
                ->for($repo)
                ->create(['committed_at' => $startDate]);

            // Create commit exactly on end date
            Commit::factory()
                ->for($user)
                ->for($repo)
                ->create(['committed_at' => $endDate]);

            $query = new DashboardSummaryQuery($user, $startDate, $endDate);
            $result = $query->get();

            expect($result['total_commits'])->toBe(2);
        });
    });

    describe('user isolation', function () {
        it('only counts commits belonging to the user', function () {
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            Commit::factory()
                ->count(3)
                ->for($user)
                ->for($repo)
                ->create();

            Commit::factory()
                ->count(5)
                ->for($otherUser)
                ->for($repo)
                ->create();

            $query = new DashboardSummaryQuery($user);
            $result = $query->get();

            expect($result['total_commits'])->toBe(3);
        });
    });
});
