<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create the test user with GitHub connected
        User::factory()->withGitHub()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'github_username' => 'testuser',
        ]);

        // Seed repositories and commits
        $this->call([
            RepositorySeeder::class,
            CommitSeeder::class,
        ]);
    }
}
