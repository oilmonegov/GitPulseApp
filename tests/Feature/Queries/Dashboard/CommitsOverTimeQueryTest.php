<?php

declare(strict_types=1);

use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use App\Queries\Dashboard\CommitsOverTimeQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('CommitsOverTimeQuery', function () {
    describe('gap filling', function () {
        it('fills days without commits with zero count', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            $startDate = Carbon::today()->subDays(6);
            $endDate = Carbon::today();

            // Only create commits on day 1 and day 5
            Commit::factory()
                ->for($user)
                ->for($repo)
                ->create(['committed_at' => $startDate->copy()->addDays(1)]);

            Commit::factory()
                ->for($user)
                ->for($repo)
                ->create(['committed_at' => $startDate->copy()->addDays(5)]);

            $query = new CommitsOverTimeQuery($user, $startDate, $endDate);
            $result = $query->get();

            // Should have 7 days (day 0 through day 6)
            expect($result)->toHaveCount(7);

            // All entries should have date and count
            $result->each(function ($item) {
                expect($item)->toHaveKeys(['date', 'count']);
            });

            // Days 0, 2, 3, 4, 6 should have 0 commits
            expect($result[0]['count'])->toBe(0)
                ->and($result[2]['count'])->toBe(0)
                ->and($result[3]['count'])->toBe(0)
                ->and($result[4]['count'])->toBe(0)
                ->and($result[6]['count'])->toBe(0);

            // Days 1 and 5 should have 1 commit each
            expect($result[1]['count'])->toBe(1)
                ->and($result[5]['count'])->toBe(1);
        });

        it('returns all zeros when user has no commits', function () {
            $user = User::factory()->create();

            $startDate = Carbon::today()->subDays(6);
            $endDate = Carbon::today();

            $query = new CommitsOverTimeQuery($user, $startDate, $endDate);
            $result = $query->get();

            expect($result)->toHaveCount(7);

            $result->each(function ($item) {
                expect($item['count'])->toBe(0);
            });
        });
    });

    describe('per-day counts', function () {
        it('correctly counts multiple commits on same day', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            $targetDate = Carbon::today()->subDays(3);
            $startDate = Carbon::today()->subDays(6);
            $endDate = Carbon::today();

            // Create 4 commits on the same day
            Commit::factory()
                ->count(4)
                ->for($user)
                ->for($repo)
                ->create(['committed_at' => $targetDate]);

            $query = new CommitsOverTimeQuery($user, $startDate, $endDate);
            $result = $query->get();

            $targetEntry = $result->firstWhere('date', $targetDate->format('Y-m-d'));

            expect($targetEntry['count'])->toBe(4);
        });

        it('counts commits at different hours of same day as one day', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            $targetDate = Carbon::today()->subDays(2);
            $startDate = Carbon::today()->subDays(6);
            $endDate = Carbon::today();

            // Create commits at different hours
            Commit::factory()->for($user)->for($repo)->create([
                'committed_at' => $targetDate->copy()->setHour(8),
            ]);
            Commit::factory()->for($user)->for($repo)->create([
                'committed_at' => $targetDate->copy()->setHour(14),
            ]);
            Commit::factory()->for($user)->for($repo)->create([
                'committed_at' => $targetDate->copy()->setHour(23),
            ]);

            $query = new CommitsOverTimeQuery($user, $startDate, $endDate);
            $result = $query->get();

            $targetEntry = $result->firstWhere('date', $targetDate->format('Y-m-d'));

            expect($targetEntry['count'])->toBe(3);
        });
    });

    describe('date range', function () {
        it('only includes commits within the specified range', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            $startDate = Carbon::today()->subDays(6);
            $endDate = Carbon::today();

            // Create commits inside range
            Commit::factory()->for($user)->for($repo)->create([
                'committed_at' => $startDate->copy()->addDays(2),
            ]);

            // Create commits outside range (before)
            Commit::factory()->for($user)->for($repo)->create([
                'committed_at' => $startDate->copy()->subDays(10),
            ]);

            // Create commits outside range (after)
            Commit::factory()->for($user)->for($repo)->create([
                'committed_at' => $endDate->copy()->addDays(5),
            ]);

            $query = new CommitsOverTimeQuery($user, $startDate, $endDate);
            $result = $query->get();

            $totalCount = $result->sum('count');

            expect($totalCount)->toBe(1);
        });

        it('orders results chronologically', function () {
            $user = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            $startDate = Carbon::today()->subDays(6);
            $endDate = Carbon::today();

            Commit::factory()->for($user)->for($repo)->create([
                'committed_at' => $endDate, // Latest
            ]);
            Commit::factory()->for($user)->for($repo)->create([
                'committed_at' => $startDate, // Earliest
            ]);

            $query = new CommitsOverTimeQuery($user, $startDate, $endDate);
            $result = $query->get();

            $dates = $result->pluck('date')->toArray();
            $sortedDates = collect($dates)->sort()->values()->toArray();

            expect($dates)->toBe($sortedDates);
        });
    });

    describe('user isolation', function () {
        it('only counts commits belonging to the user', function () {
            $user = User::factory()->create();
            $otherUser = User::factory()->create();
            $repo = Repository::factory()->for($user)->create();

            $targetDate = Carbon::today()->subDays(2);
            $startDate = Carbon::today()->subDays(6);
            $endDate = Carbon::today();

            // User's commits
            Commit::factory()
                ->count(2)
                ->for($user)
                ->for($repo)
                ->create(['committed_at' => $targetDate]);

            // Other user's commits (same day)
            Commit::factory()
                ->count(5)
                ->for($otherUser)
                ->for($repo)
                ->create(['committed_at' => $targetDate]);

            $query = new CommitsOverTimeQuery($user, $startDate, $endDate);
            $result = $query->get();

            $targetEntry = $result->firstWhere('date', $targetDate->format('Y-m-d'));

            expect($targetEntry['count'])->toBe(2);
        });
    });
});
