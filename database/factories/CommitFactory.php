<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Constants\CommitType;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<Commit>
 */
class CommitFactory extends Factory
{
    /**
     * Conventional commit prefixes for generating realistic messages.
     *
     * @var array<string, array<string>>
     */
    protected static array $commitMessages = [
        'feat' => [
            'add user authentication',
            'implement dashboard analytics',
            'add API endpoint for reports',
            'implement webhook handling',
            'add dark mode support',
            'implement export functionality',
        ],
        'fix' => [
            'resolve null pointer exception',
            'correct date formatting issue',
            'fix authentication redirect loop',
            'resolve race condition in queue',
            'fix memory leak in background jobs',
            'correct timezone handling',
        ],
        'docs' => [
            'update README with setup instructions',
            'add API documentation',
            'improve contributing guidelines',
            'document authentication flow',
            'add code examples',
        ],
        'refactor' => [
            'extract service class',
            'simplify controller logic',
            'improve database queries',
            'restructure folder hierarchy',
            'optimize imports',
        ],
        'test' => [
            'add unit tests for user model',
            'improve integration test coverage',
            'add feature tests for API',
            'mock external services',
        ],
        'chore' => [
            'update dependencies',
            'configure CI pipeline',
            'add pre-commit hooks',
            'update gitignore',
        ],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(CommitType::cases());
        $messages = self::$commitMessages[$type->value] ?? self::$commitMessages['chore'];
        $message = fake()->randomElement($messages);
        $scope = fake()->optional(0.4)->randomElement(['auth', 'api', 'ui', 'db', 'core']);

        $committedAt = Carbon::now()->subDays(fake()->numberBetween(0, 90))->subHours(fake()->numberBetween(0, 23));

        return [
            'repository_id' => Repository::factory(),
            'user_id' => User::factory(),
            'sha' => Str::random(40),
            'message' => $scope ? "{$type->value}({$scope}): {$message}" : "{$type->value}: {$message}",
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'committed_at' => $committedAt,
            'additions' => fake()->numberBetween(1, 500),
            'deletions' => fake()->numberBetween(0, 200),
            'files_changed' => fake()->numberBetween(1, 20),
            'files' => null,
            'commit_type' => $type,
            'scope' => $scope,
            'impact_score' => fake()->randomFloat(2, 0.5, 10.0),
            'external_refs' => null,
            'is_merge' => false,
            'url' => null,
        ];
    }

    /**
     * Indicate that the commit is a merge commit.
     */
    public function merge(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_merge' => true,
            'message' => "Merge branch 'feature' into main",
            'commit_type' => CommitType::Other,
        ]);
    }

    /**
     * Indicate the commit is a feature commit.
     */
    public function feature(): static
    {
        return $this->state(fn (array $attributes): array => [
            'commit_type' => CommitType::Feat,
            'message' => 'feat: ' . fake()->randomElement(self::$commitMessages['feat']),
        ]);
    }

    /**
     * Indicate the commit is a bug fix.
     */
    public function fix(): static
    {
        return $this->state(fn (array $attributes): array => [
            'commit_type' => CommitType::Fix,
            'message' => 'fix: ' . fake()->randomElement(self::$commitMessages['fix']),
        ]);
    }

    /**
     * Indicate the commit is documentation.
     */
    public function docs(): static
    {
        return $this->state(fn (array $attributes): array => [
            'commit_type' => CommitType::Docs,
            'message' => 'docs: ' . fake()->randomElement(self::$commitMessages['docs']),
            'additions' => fake()->numberBetween(5, 100),
            'deletions' => fake()->numberBetween(0, 50),
        ]);
    }

    /**
     * Indicate the commit is a refactor.
     */
    public function refactor(): static
    {
        return $this->state(fn (array $attributes): array => [
            'commit_type' => CommitType::Refactor,
            'message' => 'refactor: ' . fake()->randomElement(self::$commitMessages['refactor']),
        ]);
    }

    /**
     * Indicate the commit is a test.
     */
    public function test(): static
    {
        return $this->state(fn (array $attributes): array => [
            'commit_type' => CommitType::Test,
            'message' => 'test: ' . fake()->randomElement(self::$commitMessages['test']),
        ]);
    }

    /**
     * Indicate the commit is a chore.
     */
    public function chore(): static
    {
        return $this->state(fn (array $attributes): array => [
            'commit_type' => CommitType::Chore,
            'message' => 'chore: ' . fake()->randomElement(self::$commitMessages['chore']),
            'impact_score' => fake()->randomFloat(2, 0.5, 3.0),
        ]);
    }

    /**
     * Indicate the commit has high impact.
     */
    public function highImpact(): static
    {
        return $this->state(fn (array $attributes): array => [
            'additions' => fake()->numberBetween(200, 1000),
            'deletions' => fake()->numberBetween(50, 500),
            'files_changed' => fake()->numberBetween(10, 50),
            'impact_score' => fake()->randomFloat(2, 7.0, 10.0),
        ]);
    }

    /**
     * Indicate the commit has low impact.
     */
    public function lowImpact(): static
    {
        return $this->state(fn (array $attributes): array => [
            'additions' => fake()->numberBetween(1, 20),
            'deletions' => fake()->numberBetween(0, 10),
            'files_changed' => fake()->numberBetween(1, 3),
            'impact_score' => fake()->randomFloat(2, 0.5, 2.0),
        ]);
    }

    /**
     * Set the commit to a specific date.
     */
    public function committedOn(Carbon $date): static
    {
        return $this->state(fn (array $attributes): array => [
            'committed_at' => $date,
        ]);
    }

    /**
     * Set the commit to today.
     */
    public function today(): static
    {
        return $this->committedOn(Carbon::today()->addHours(fake()->numberBetween(8, 18)));
    }

    /**
     * Set the commit to yesterday.
     */
    public function yesterday(): static
    {
        return $this->committedOn(Carbon::yesterday()->addHours(fake()->numberBetween(8, 18)));
    }

    /**
     * Add external references (issues, PRs).
     */
    public function withExternalRefs(): static
    {
        return $this->state(fn (array $attributes): array => [
            'external_refs' => [
                ['type' => 'issue', 'id' => '#' . fake()->numberBetween(1, 500)],
            ],
        ]);
    }

    /**
     * Add file details.
     *
     * @param  array<array{filename: string, status: string, additions: int, deletions: int}>|null  $files
     */
    public function withFiles(?array $files = null): static
    {
        return $this->state(function (array $attributes) use ($files): array {
            $files ??= [
                [
                    'filename' => 'app/Models/' . fake()->word() . '.php',
                    'status' => 'modified',
                    'additions' => fake()->numberBetween(5, 50),
                    'deletions' => fake()->numberBetween(0, 20),
                ],
                [
                    'filename' => 'tests/Feature/' . fake()->word() . 'Test.php',
                    'status' => 'added',
                    'additions' => fake()->numberBetween(20, 100),
                    'deletions' => 0,
                ],
            ];

            return [
                'files' => $files,
                'files_changed' => count($files),
            ];
        });
    }

    /**
     * Set a specific commit type.
     */
    public function ofType(CommitType $type): static
    {
        $messages = self::$commitMessages[$type->value] ?? self::$commitMessages['chore'];

        return $this->state(fn (array $attributes): array => [
            'commit_type' => $type,
            'message' => "{$type->value}: " . fake()->randomElement($messages),
        ]);
    }
}
