<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Seeder;

class RepositorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $user = User::factory()->withGitHub()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        // Create some sample repositories for the user
        Repository::factory()
            ->for($user)
            ->withWebhook()
            ->synced()
            ->create([
                'name' => 'gitpulse',
                'full_name' => "{$user->github_username}/gitpulse",
                'description' => 'Developer productivity analytics platform',
                'language' => 'PHP',
            ]);

        Repository::factory()
            ->for($user)
            ->withWebhook()
            ->synced()
            ->create([
                'name' => 'awesome-project',
                'full_name' => "{$user->github_username}/awesome-project",
                'description' => 'An awesome open source project',
                'language' => 'TypeScript',
            ]);

        Repository::factory()
            ->for($user)
            ->private()
            ->withWebhook()
            ->synced()
            ->create([
                'name' => 'secret-startup',
                'full_name' => "{$user->github_username}/secret-startup",
                'description' => 'Top secret startup project',
                'language' => 'Python',
            ]);

        // Create a few inactive repositories
        Repository::factory()
            ->count(2)
            ->for($user)
            ->inactive()
            ->create();
    }
}
