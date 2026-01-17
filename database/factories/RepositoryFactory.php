<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Repository>
 */
class RepositoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->slug(2);
        $owner = fake()->userName();

        return [
            'user_id' => User::factory(),
            'github_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'name' => $name,
            'full_name' => "{$owner}/{$name}",
            'description' => fake()->optional(0.7)->sentence(),
            'default_branch' => fake()->randomElement(['main', 'master', 'develop']),
            'language' => fake()->randomElement(['PHP', 'JavaScript', 'TypeScript', 'Python', 'Go', 'Rust', null]),
            'webhook_id' => null,
            'webhook_secret' => null,
            'is_active' => true,
            'is_private' => false,
            'last_sync_at' => null,
        ];
    }

    /**
     * Indicate that the repository is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_private' => true,
        ]);
    }

    /**
     * Indicate that the repository is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the repository has a webhook configured.
     */
    public function withWebhook(): static
    {
        return $this->state(fn (array $attributes): array => [
            'webhook_id' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'webhook_secret' => Str::random(64),
        ]);
    }

    /**
     * Indicate that the repository was recently synced.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_sync_at' => now(),
        ]);
    }

    /**
     * Set a specific programming language for the repository.
     */
    public function withLanguage(string $language): static
    {
        return $this->state(fn (array $attributes): array => [
            'language' => $language,
        ]);
    }

    /**
     * Configure the repository as a Laravel project.
     */
    public function laravel(): static
    {
        return $this->state(fn (array $attributes): array => [
            'language' => 'PHP',
            'description' => 'A Laravel application',
        ]);
    }
}
