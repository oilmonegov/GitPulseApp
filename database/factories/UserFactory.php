<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'github_id' => null,
            'github_username' => null,
            'github_token' => null,
            'avatar_url' => null,
            'preferences' => null,
            'timezone' => 'UTC',
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes): array => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the user has connected their GitHub account.
     */
    public function withGitHub(): static
    {
        return $this->state(fn (array $attributes): array => [
            'github_id' => (string) fake()->unique()->numberBetween(1000000, 99999999),
            'github_username' => fake()->userName(),
            'github_token' => Str::random(40),
            'avatar_url' => fake()->imageUrl(200, 200, 'people'),
        ]);
    }

    /**
     * Set specific preferences for the user.
     *
     * @param  array<string, mixed>  $preferences
     */
    public function withPreferences(array $preferences): static
    {
        return $this->state(fn (array $attributes): array => [
            'preferences' => $preferences,
        ]);
    }

    /**
     * Set a specific timezone for the user.
     */
    public function withTimezone(string $timezone): static
    {
        return $this->state(fn (array $attributes): array => [
            'timezone' => $timezone,
        ]);
    }
}
