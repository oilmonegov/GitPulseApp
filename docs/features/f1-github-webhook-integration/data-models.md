# F1: Data Models

## Database Schema

### `webhook_calls` (Spatie Package)

This table is automatically created by `spatie/laravel-webhook-client`.

```php
Schema::create('webhook_calls', function (Blueprint $table) {
    $table->bigIncrements('id');
    $table->string('name');
    $table->string('url')->nullable();
    $table->json('headers')->nullable();
    $table->json('payload')->nullable();
    $table->text('exception')->nullable();
    $table->timestamps();
});
```

**Purpose**: Stores raw webhook requests for debugging and retry handling.

**Retention**: 30 days (configurable in `config/webhook-client.php`)

---

### `repositories`

```php
Schema::create('repositories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('github_id');
    $table->string('name');
    $table->string('full_name'); // org/repo format
    $table->string('webhook_id')->nullable();
    $table->string('webhook_secret', 64)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_sync_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'github_id']);
    $table->index(['github_id', 'is_active']); // For webhook routing
    $table->index(['user_id', 'is_active']);
});
```

**Key Fields**:
- `github_id`: Used to match incoming webhooks to repositories
- `webhook_id`: GitHub's webhook ID (for management via API)
- `webhook_secret`: Per-repository secret for signature validation
- `is_active`: Toggle to pause webhook processing

---

### `commits`

```php
Schema::create('commits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('sha', 40)->unique();
    $table->text('message');
    $table->string('author_name');
    $table->string('author_email');
    $table->timestamp('committed_at');
    $table->unsignedInteger('additions')->default(0);
    $table->unsignedInteger('deletions')->default(0);
    $table->unsignedSmallInteger('files_changed')->default(0);
    $table->json('files')->nullable();
    $table->enum('commit_type', ['feat', 'fix', 'chore', 'docs', 'refactor', 'test', 'style', 'perf', 'other'])->default('other');
    $table->decimal('impact_score', 5, 2)->default(0);
    $table->json('external_refs')->nullable();
    $table->boolean('is_merge')->default(false);
    $table->timestamps();

    $table->index(['user_id', 'committed_at']);
    $table->index(['repository_id', 'committed_at']);
    $table->index(['committed_at']);
});
```

**Partitioning** (Raw SQL migration):

```sql
ALTER TABLE commits
PARTITION BY RANGE (YEAR(committed_at) * 100 + MONTH(committed_at)) (
    PARTITION p202601 VALUES LESS THAN (202602),
    PARTITION p202602 VALUES LESS THAN (202603),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);
```

---

## Eloquent Models

### Repository Model

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'github_id',
        'name',
        'full_name',
        'webhook_id',
        'webhook_secret',
        'is_active',
        'last_sync_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_sync_at' => 'datetime',
        'webhook_secret' => 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGitHubId($query, string $githubId)
    {
        return $query->where('github_id', $githubId);
    }
}
```

### Commit Model

```php
namespace App\Models;

use App\Enums\CommitType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commit extends Model
{
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'user_id',
        'sha',
        'message',
        'author_name',
        'author_email',
        'committed_at',
        'additions',
        'deletions',
        'files_changed',
        'files',
        'commit_type',
        'impact_score',
        'external_refs',
        'is_merge',
    ];

    protected $casts = [
        'committed_at' => 'datetime',
        'files' => 'array',
        'external_refs' => 'array',
        'commit_type' => CommitType::class,
        'is_merge' => 'boolean',
        'impact_score' => 'decimal:2',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('committed_at', today());
    }

    public function scopeYesterday($query)
    {
        return $query->whereDate('committed_at', today()->subDay());
    }

    public function scopeForDateRange($query, $start, $end)
    {
        return $query->whereBetween('committed_at', [$start, $end]);
    }

    public function getLinesChangedAttribute(): int
    {
        return $this->additions + $this->deletions;
    }
}
```

---

## Enums

### CommitType Enum

```php
namespace App\Enums;

enum CommitType: string
{
    case FEAT = 'feat';
    case FIX = 'fix';
    case CHORE = 'chore';
    case DOCS = 'docs';
    case REFACTOR = 'refactor';
    case TEST = 'test';
    case STYLE = 'style';
    case PERF = 'perf';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FEAT => 'Feature',
            self::FIX => 'Bug Fix',
            self::CHORE => 'Chore',
            self::DOCS => 'Documentation',
            self::REFACTOR => 'Refactor',
            self::TEST => 'Test',
            self::STYLE => 'Style',
            self::PERF => 'Performance',
            self::OTHER => 'Other',
        };
    }

    public function impactWeight(): float
    {
        return match ($this) {
            self::FEAT => 1.0,
            self::FIX => 0.8,
            self::REFACTOR, self::PERF => 0.7,
            self::TEST => 0.5,
            self::DOCS => 0.3,
            self::STYLE, self::CHORE => 0.2,
            self::OTHER => 0.4,
        };
    }
}
```

---

## Data Transfer Objects (Spatie Data)

### CommitData DTO

```php
namespace App\Data;

use App\Enums\CommitType;
use Carbon\Carbon;
use Spatie\LaravelData\Data;

class CommitData extends Data
{
    public function __construct(
        public string $sha,
        public string $message,
        public string $author_name,
        public string $author_email,
        public Carbon $committed_at,
        public int $additions,
        public int $deletions,
        public int $files_changed,
        public array $files,
        public CommitType $commit_type,
        public bool $is_merge,
        public ?array $external_refs = null,
    ) {}

    public static function fromGitHubPayload(array $commit): self
    {
        return new self(
            sha: $commit['id'],
            message: $commit['message'],
            author_name: $commit['author']['name'],
            author_email: $commit['author']['email'],
            committed_at: Carbon::parse($commit['timestamp']),
            additions: count($commit['added'] ?? []),
            deletions: count($commit['removed'] ?? []),
            files_changed: count($commit['modified'] ?? []) + count($commit['added'] ?? []) + count($commit['removed'] ?? []),
            files: array_merge($commit['added'] ?? [], $commit['modified'] ?? [], $commit['removed'] ?? []),
            commit_type: CommitType::OTHER, // Set by ParseCommitMessage action
            is_merge: str_starts_with($commit['message'], 'Merge'),
            external_refs: null, // Set by ParseCommitMessage action
        );
    }
}
```

---

## Factory Definitions

### RepositoryFactory

```php
namespace Database\Factories;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class RepositoryFactory extends Factory
{
    protected $model = Repository::class;

    public function definition(): array
    {
        $name = fake()->slug(2);

        return [
            'user_id' => User::factory(),
            'github_id' => (string) fake()->unique()->randomNumber(8),
            'name' => $name,
            'full_name' => fake()->userName() . '/' . $name,
            'webhook_id' => (string) fake()->randomNumber(8),
            'webhook_secret' => bin2hex(random_bytes(32)),
            'is_active' => true,
            'last_sync_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
```

### CommitFactory

```php
namespace Database\Factories;

use App\Enums\CommitType;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommitFactory extends Factory
{
    protected $model = Commit::class;

    public function definition(): array
    {
        $types = CommitType::cases();
        $type = fake()->randomElement($types);

        return [
            'repository_id' => Repository::factory(),
            'user_id' => User::factory(),
            'sha' => fake()->sha1(),
            'message' => $type->value . ': ' . fake()->sentence(),
            'author_name' => fake()->name(),
            'author_email' => fake()->email(),
            'committed_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'additions' => fake()->numberBetween(1, 500),
            'deletions' => fake()->numberBetween(0, 200),
            'files_changed' => fake()->numberBetween(1, 20),
            'files' => ['src/' . fake()->word() . '.php'],
            'commit_type' => $type,
            'impact_score' => fake()->randomFloat(2, 0, 10),
            'external_refs' => fake()->boolean(30) ? ['#' . fake()->randomNumber(3)] : null,
            'is_merge' => fake()->boolean(10),
        ];
    }

    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'committed_at' => now()->setTime(fake()->numberBetween(8, 18), fake()->numberBetween(0, 59)),
        ]);
    }

    public function yesterday(): static
    {
        return $this->state(fn (array $attributes) => [
            'committed_at' => now()->subDay()->setTime(fake()->numberBetween(8, 18), fake()->numberBetween(0, 59)),
        ]);
    }

    public function feature(): static
    {
        return $this->state(fn (array $attributes) => [
            'commit_type' => CommitType::FEAT,
            'message' => 'feat: ' . fake()->sentence(),
        ]);
    }

    public function fix(): static
    {
        return $this->state(fn (array $attributes) => [
            'commit_type' => CommitType::FIX,
            'message' => 'fix: ' . fake()->sentence(),
        ]);
    }

    public function merge(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_merge' => true,
            'message' => 'Merge pull request #' . fake()->randomNumber(3) . ' from ' . fake()->userName() . '/feature',
        ]);
    }
}
```

---

## Migrations

### Migration Order

1. `2026_01_01_000001_create_repositories_table.php`
2. `2026_01_01_000002_create_commits_table.php`
3. `2026_01_01_000003_add_commit_partitions.php` (raw SQL)

### Partition Management Job

```php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CreateMonthlyPartition implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $nextMonth = now()->addMonth();
        $partitionName = 'p' . $nextMonth->format('Ym');
        $boundary = (int) $nextMonth->addMonth()->format('Ym');

        DB::statement("
            ALTER TABLE commits REORGANIZE PARTITION p_future INTO (
                PARTITION {$partitionName} VALUES LESS THAN ({$boundary}),
                PARTITION p_future VALUES LESS THAN MAXVALUE
            )
        ");
    }
}
```

Scheduled monthly via `app/Console/Kernel.php`:

```php
$schedule->job(new CreateMonthlyPartition)->monthlyOn(25, '00:00');
```
