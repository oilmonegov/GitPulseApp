<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Commit;
use App\Models\Repository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class CommitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $repositories = Repository::with('user')->active()->get();

        if ($repositories->isEmpty()) {
            $this->command->warn('No active repositories found. Run RepositorySeeder first.');

            return;
        }

        foreach ($repositories as $repository) {
            $user = $repository->user;

            // Skip repositories without a user
            if ($user === null) {
                continue;
            }

            // Create commits for the past 30 days
            for ($daysAgo = 0; $daysAgo < 30; $daysAgo++) {
                $date = Carbon::now()->subDays($daysAgo);
                $commitsPerDay = fake()->numberBetween(0, 8);

                // Skip some days to make it realistic
                if (fake()->boolean(20)) {
                    continue;
                }

                Commit::factory()
                    ->count($commitsPerDay)
                    ->for($repository)
                    ->for($user)
                    ->committedOn($date->copy()->addHours(fake()->numberBetween(9, 18)))
                    ->create();
            }

            // Add some high impact feature commits
            Commit::factory()
                ->count(3)
                ->for($repository)
                ->for($user)
                ->feature()
                ->highImpact()
                ->create();

            // Add some bug fixes
            Commit::factory()
                ->count(5)
                ->for($repository)
                ->for($user)
                ->fix()
                ->create();

            // Add some documentation commits
            Commit::factory()
                ->count(2)
                ->for($repository)
                ->for($user)
                ->docs()
                ->lowImpact()
                ->create();

            // Add a merge commit
            Commit::factory()
                ->for($repository)
                ->for($user)
                ->merge()
                ->create();

            // Add commits with external references
            Commit::factory()
                ->count(3)
                ->for($repository)
                ->for($user)
                ->withExternalRefs()
                ->create();

            // Add commits with file details
            Commit::factory()
                ->count(2)
                ->for($repository)
                ->for($user)
                ->withFiles()
                ->create();
        }
    }
}
