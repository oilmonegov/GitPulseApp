# Laravel Best Practices for GitPulse

This document outlines Laravel best practices tailored to the GitPulse codebase. Follow these guidelines to maintain code quality, consistency, and scalability.

## Table of Contents

1. [Project Structure](#project-structure)
2. [Models & Eloquent](#models--eloquent)
3. [Controllers](#controllers)
4. [Services & Actions](#services--actions)
5. [Form Requests & Validation](#form-requests--validation)
6. [Database & Migrations](#database--migrations)
7. [Queue Jobs](#queue-jobs)
8. [API Design](#api-design)
9. [Testing](#testing)
10. [Security](#security)
11. [Performance](#performance)
12. [Error Handling](#error-handling)
13. [Git Workflow](#git-workflow)

---

## Project Structure

### Directory Organization

```
app/
├── Actions/           # Single-purpose domain actions
├── Concerns/          # Shared traits for models/controllers
├── Contracts/         # Interfaces for dependency injection
├── DTOs/              # Data Transfer Objects
├── Constants/         # PHP 8.1+ backed enums
├── Events/            # Domain events
├── Exceptions/        # Custom exception classes
├── Http/
│   ├── Controllers/   # Keep thin, delegate to services
│   ├── Middleware/
│   ├── Requests/      # Form request validation
│   └── Resources/     # API resources (if needed)
├── Jobs/              # Queue jobs for async processing
├── Listeners/         # Event listeners
├── Models/            # Eloquent models
├── Policies/          # Authorization policies
├── Providers/
├── Services/          # Business logic services
└── Webhooks/          # Spatie webhook handlers
```

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Controllers | Singular, PascalCase + `Controller` | `CommitController` |
| Models | Singular, PascalCase | `Commit`, `DailyMetric` |
| Jobs | Verb + Noun | `ProcessGitHubPush`, `GenerateWeeklyReport` |
| Actions | Verb + Noun | `CalculateImpactScore`, `ParseCommitMessage` |
| Services | Noun + `Service` | `MetricsService`, `GitHubService` |
| Events | Past tense | `CommitProcessed`, `ReportGenerated` |
| Listeners | Handle + Event | `HandleCommitProcessed` |
| Policies | Model + `Policy` | `RepositoryPolicy` |
| Requests | Action + Model + `Request` | `StoreRepositoryRequest` |

---

## Models & Eloquent

### Model Structure

Organize model properties and methods in this order:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Commit extends Model
{
    use HasFactory;

    // 1. Primary key configuration (if non-standard)
    protected $keyType = 'string';
    public $incrementing = false;

    // 2. Mass assignment protection
    protected $fillable = [
        'sha',
        'message',
        'author_name',
        'author_email',
        'committed_at',
        'repository_id',
    ];

    // 3. Attribute casting (use method, not property — Laravel 12 convention)
    protected function casts(): array
    {
        return [
            'committed_at' => 'datetime',
            'files_changed' => 'array',
            'impact_score' => 'decimal:2',
        ];
    }

    // 4. Hidden attributes (for serialization)
    protected $hidden = [
        'raw_payload',
    ];

    // 5. Boot method (if needed)
    protected static function booted(): void
    {
        static::creating(function (Commit $commit) {
            $commit->id ??= (string) Str::uuid();
        });
    }

    // 6. Relationships (alphabetical)
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 7. Scopes
    public function scopeForRepository(Builder $query, int $repositoryId): Builder
    {
        return $query->where('repository_id', $repositoryId);
    }

    public function scopeInDateRange(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('committed_at', [$start, $end]);
    }

    // 8. Accessors & Mutators
    protected function shortSha(): Attribute
    {
        return Attribute::get(fn () => substr($this->sha, 0, 7));
    }

    // 9. Custom methods
    public function isHighImpact(): bool
    {
        return $this->impact_score >= 7.0;
    }
}
```

### Key Requirements

- **All models must be `final`** — this is enforced by architecture tests. No model should be extended.
- **Use the `casts()` method** instead of the `$casts` property (Laravel 12 convention).
- **All PHP files must include `declare(strict_types=1)`** — enforced by architecture tests project-wide.
- **Omit the `$table` property** when following Laravel's naming convention (model `Commit` → table `commits`).

### Best Practices

**DO:**
```php
// Use query scopes for reusable queries
Commit::forRepository($repoId)->inDateRange($start, $end)->get();

// Use explicit select for large tables
Commit::select(['id', 'sha', 'message', 'committed_at'])->get();

// Use chunk/lazy for large datasets
Commit::where('committed_at', '>', now()->subMonth())
    ->lazy()
    ->each(fn ($commit) => $this->process($commit));

// Use eager loading to prevent N+1
$commits = Commit::with(['repository', 'user'])->get();

// Use whereHas for relationship filtering
Repository::whereHas('commits', fn ($q) => $q->where('impact_score', '>', 5))->get();
```

**DON'T:**
```php
// Don't use DB facade — use Model::query() or Eloquent methods
DB::table('commits')->where('id', $id)->first(); // Bad
Commit::find($id); // Good
Commit::query()->where('sha', $sha)->first(); // Good — prefer Model::query() for complex queries

// Don't query in loops (N+1 problem)
foreach ($commits as $commit) {
    echo $commit->repository->name; // N+1 queries!
}

// Don't use * in production for large tables
Commit::all(); // Avoid on partitioned tables

// Don't chain where clauses when scope works
$query->where('repository_id', $id)->where('committed_at', '>', $date); // OK
$query->forRepository($id)->recent(); // Better
```

### Using Enums for Status/Type Fields

```php
<?php

declare(strict_types=1);

namespace App\Constants;

enum CommitType: string
{
    case Feature = 'feat';
    case Fix = 'fix';
    case Refactor = 'refactor';
    case Performance = 'perf';
    case Test = 'test';
    case Documentation = 'docs';
    case Style = 'style';
    case Chore = 'chore';

    public function impactWeight(): float
    {
        return match ($this) {
            self::Feature => 1.0,
            self::Fix => 0.85,
            self::Refactor, self::Performance => 0.7,
            self::Test => 0.5,
            self::Documentation => 0.3,
            self::Style, self::Chore => 0.2,
        };
    }
}
```

```php
// In model
protected function casts(): array
{
    return [
        'type' => CommitType::class,
    ];
}
```

---

## Controllers

### Keep Controllers Thin

Controllers should only handle HTTP concerns. Delegate business logic to services or actions.

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRepositoryRequest;
use App\Models\Repository;
use App\Services\RepositoryService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class RepositoryController extends Controller
{
    public function __construct(
        private readonly RepositoryService $repositoryService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Repositories/Index', [
            'repositories' => auth()->user()
                ->repositories()
                ->withCount('commits')
                ->latest()
                ->paginate(15),
        ]);
    }

    public function store(StoreRepositoryRequest $request): RedirectResponse
    {
        $repository = $this->repositoryService->createFromGitHub(
            user: $request->user(),
            githubRepoId: $request->validated('github_repo_id'),
        );

        return redirect()
            ->route('repositories.show', $repository)
            ->with('success', 'Repository connected successfully.');
    }

    public function destroy(Repository $repository): RedirectResponse
    {
        $this->authorize('delete', $repository);

        $this->repositoryService->disconnect($repository);

        return redirect()
            ->route('repositories.index')
            ->with('success', 'Repository disconnected.');
    }
}
```

### RESTful Resource Controllers

Use resource controller methods consistently:

| Method | Route | Action |
|--------|-------|--------|
| `index()` | GET `/repositories` | List resources |
| `create()` | GET `/repositories/create` | Show create form |
| `store()` | POST `/repositories` | Create resource |
| `show()` | GET `/repositories/{id}` | Show single resource |
| `edit()` | GET `/repositories/{id}/edit` | Show edit form |
| `update()` | PUT `/repositories/{id}` | Update resource |
| `destroy()` | DELETE `/repositories/{id}` | Delete resource |

### Route Model Binding

```php
// routes/web.php
Route::get('/repositories/{repository}', [RepositoryController::class, 'show']);

// Controller receives the model instance
public function show(Repository $repository): Response
{
    $this->authorize('view', $repository);
    // $repository is already loaded
}
```

---

## Services & Actions

### When to Use Each

This project **prefers Actions and Queries over Services** following the CQRS pattern. Most business logic should live in Action/Query classes. Services are a secondary pattern reserved for external integrations or orchestration that doesn't fit the Action/Query model.

**Services**: For external integrations or complex orchestration involving multiple operations.

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Http;

final class GitHubService
{
    public function __construct(
        private readonly string $baseUrl = 'https://api.github.com',
    ) {}

    public function getRepositories(User $user): array
    {
        $response = Http::withToken($user->github_token)
            ->get("{$this->baseUrl}/user/repos");

        return $response->json();
    }

    public function createWebhook(Repository $repository): array
    {
        // Complex webhook creation logic
    }

    public function deleteWebhook(Repository $repository): void
    {
        // Webhook deletion logic
    }
}
```

### CQRS Pattern (Actions & Queries)

This project follows a simplified CQRS pattern enforced by architecture tests:

- **Actions** (`app/Actions/`): Handle write operations. Must implement `App\Contracts\Action` and be `final`. Execute via `->execute()`.
- **Queries** (`app/Queries/`): Handle read operations. Must implement `App\Contracts\Query` and be `final`. Execute via `->get()`. Never modify state.

```php
// Action example
final class ConnectGitHubAction implements Action
{
    public function __construct(
        private readonly User $user,
        private readonly SocialiteUser $githubUser,
    ) {}

    public function execute(): bool
    {
        $this->user->update([...]);
        return true;
    }
}

// Query example
final class FindUserByGitHubIdQuery implements Query
{
    public function __construct(
        private readonly string $githubId,
    ) {}

    public function get(): ?User
    {
        return User::where('github_id', $this->githubId)->first();
    }
}
```

**Actions**: For single-purpose, reusable operations. Following the "Single Action Classes" pattern.

```php
<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CommitData;
use App\Constants\CommitType;
use App\Models\Commit;

final class CalculateImpactScore implements Action
{
    private const WEIGHTS = [
        'lines_changed' => 0.20,
        'files_touched' => 0.15,
        'commit_type' => 0.25,
        'merge_status' => 0.20,
        'references' => 0.10,
        'time_of_day' => 0.10,
    ];

    public function execute(CommitData $data): float
    {
        $scores = [
            'lines_changed' => $this->scoreLinesChanged($data->additions + $data->deletions),
            'files_touched' => $this->scoreFilesTouched($data->filesChanged),
            'commit_type' => $this->scoreCommitType($data->type),
            'merge_status' => $data->isMerge ? 2.0 : 5.0,
            'references' => $this->scoreReferences($data->message),
            'time_of_day' => $this->scoreTimeOfDay($data->committedAt),
        ];

        return collect($scores)
            ->map(fn ($score, $key) => $score * self::WEIGHTS[$key])
            ->sum();
    }

    private function scoreLinesChanged(int $lines): float
    {
        return match (true) {
            $lines < 10 => 2.0,
            $lines < 50 => 5.0,
            $lines < 200 => 8.0,
            default => 10.0,
        };
    }

    // Additional private methods...
}
```

### Dependency Injection

Always inject dependencies through the constructor:

```php
// Good: Constructor injection
public function __construct(
    private readonly GitHubService $github,
    private readonly CalculateImpactScore $calculateImpact,
) {}

// Bad: Using app() helper or facades in methods
public function handle()
{
    $github = app(GitHubService::class); // Avoid
}
```

---

## Form Requests & Validation

### Create Dedicated Request Classes

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRepositoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Use policies for complex authorization
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'github_repo_id' => [
                'required',
                'integer',
                Rule::unique('repositories')->where('user_id', $this->user()->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'default_branch' => ['sometimes', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'github_repo_id.unique' => 'This repository is already connected.',
        ];
    }
}
```

### Validation Best Practices

```php
// Use typed methods for accessing validated data
public function validated($key = null, $default = null): mixed;

// In controller
$validated = $request->validated();
$repoId = $request->validated('github_repo_id');

// Use safe() for partial validated data
$request->safe()->only(['name', 'email']);
$request->safe()->except(['password']);
```

---

## Database & Migrations

### Migration Best Practices

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commits', function (Blueprint $table) {
            // Primary key
            $table->uuid('id')->primary();

            // Foreign keys (define early)
            $table->foreignId('repository_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Core fields
            $table->string('sha', 40)->unique();
            $table->text('message');
            $table->string('author_name');
            $table->string('author_email');
            $table->string('type', 20)->nullable();

            // Metrics
            $table->unsignedInteger('additions')->default(0);
            $table->unsignedInteger('deletions')->default(0);
            $table->unsignedSmallInteger('files_changed')->default(0);
            $table->decimal('impact_score', 4, 2)->default(0);

            // Timestamps
            $table->timestamp('committed_at');
            $table->timestamps();

            // Indexes for common queries
            $table->index(['repository_id', 'committed_at']);
            $table->index(['user_id', 'committed_at']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commits');
    }
};
```

### SQLite & MySQL Compatibility

This project uses SQLite for development/testing and MySQL for production. All database code must work on both:

- **Never use `enum()` column type** in migrations — use `string()` with a sensible length instead (e.g., `string('status', 20)`).
- **Use the `DatabaseCompatible` trait** (`App\Concerns\DatabaseCompatible`) for queries requiring database-specific functions like `YEAR()`, `MONTH()`, `NOW()`, `DATE_FORMAT()`, or `GROUP_CONCAT()`. The trait provides cross-DB methods: `yearFromDate()`, `monthFromDate()`, `dateFormat()`, `currentTimestamp()`, `concat()`, `groupConcat()`, etc.
- **Avoid raw SQL** with database-specific functions. Use Laravel's query builder which handles most cross-DB compatibility automatically.

### Database Best Practices

```php
// Use transactions for multi-table operations
DB::transaction(function () use ($data) {
    $commit = Commit::create($data['commit']);
    $commit->files()->createMany($data['files']);
});

// Use database-level defaults
$table->boolean('is_active')->default(true);
$table->timestamp('created_at')->useCurrent();

// Add indexes for filtered/sorted columns
$table->index('committed_at'); // Frequent date filtering
$table->index(['repository_id', 'committed_at']); // Composite for common queries

// Use appropriate column types
$table->string('sha', 40); // Fixed length, not varchar(255)
$table->unsignedTinyInteger('status'); // 0-255 is enough
$table->decimal('impact_score', 4, 2); // Precise decimals, not float
```

---

## Queue Jobs

### Job Structure

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\CalculateImpactScore;
use App\Actions\ParseCommitMessage;
use App\DTOs\GitHubPushPayload;
use App\Models\Commit;
use App\Models\Repository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessGitHubPush implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        public readonly GitHubPushPayload $payload,
        public readonly Repository $repository,
    ) {}

    public function uniqueId(): string
    {
        return $this->payload->deliveryId;
    }

    public function handle(
        ParseCommitMessage $parseMessage,
        CalculateImpactScore $calculateScore,
    ): void {
        foreach ($this->payload->commits as $commitData) {
            $parsed = $parseMessage->execute($commitData['message']);

            Commit::create([
                'repository_id' => $this->repository->id,
                'sha' => $commitData['id'],
                'message' => $commitData['message'],
                'type' => $parsed->type,
                'impact_score' => $calculateScore->execute($commitData),
                'committed_at' => $commitData['timestamp'],
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('GitHub push processing failed', [
            'repository_id' => $this->repository->id,
            'delivery_id' => $this->payload->deliveryId,
            'error' => $exception->getMessage(),
        ]);
    }
}
```

### Queue Best Practices

```php
// Use specific queues for different job types
ProcessGitHubPush::dispatch($payload)->onQueue('webhooks');
GenerateWeeklyReport::dispatch($user)->onQueue('reports');
SendNotification::dispatch($user)->onQueue('notifications');

// Use job batching for related operations
Bus::batch([
    new ProcessCommit($commit1),
    new ProcessCommit($commit2),
    new ProcessCommit($commit3),
])->then(function (Batch $batch) {
    // All jobs completed
})->dispatch();

// Use rate limiting for external API calls
Redis::throttle('github-api')
    ->allow(100)
    ->every(60)
    ->then(function () {
        // Make API call
    });
```

---

## API Design

### Resource Responses

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class CommitResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sha' => $this->sha,
            'short_sha' => $this->short_sha,
            'message' => $this->message,
            'type' => $this->type,
            'impact_score' => $this->impact_score,
            'committed_at' => $this->committed_at->toIso8601String(),
            'repository' => new RepositoryResource($this->whenLoaded('repository')),
            'links' => [
                'self' => route('api.commits.show', $this),
                'repository' => route('api.repositories.show', $this->repository_id),
            ],
        ];
    }
}
```

### Consistent API Responses

```php
// Success responses
return response()->json([
    'data' => new CommitResource($commit),
], 201);

// Collection with pagination
return CommitResource::collection($commits);

// Error responses (use consistent structure)
return response()->json([
    'message' => 'Repository not found.',
    'errors' => ['repository_id' => ['The repository does not exist.']],
], 404);
```

---

## Testing

### Test Organization

```
tests/
├── Feature/
│   ├── Http/
│   │   └── Controllers/
│   │       └── RepositoryControllerTest.php
│   ├── Jobs/
│   │   └── ProcessGitHubPushTest.php
│   └── Webhooks/
│       └── GitHubWebhookTest.php
├── Unit/
│   ├── Actions/
│   │   └── CalculateImpactScoreTest.php
│   └── Models/
│       └── CommitTest.php
└── Pest.php
```

### PestPHP Test Examples

```php
<?php

use App\Actions\CalculateImpactScore;
use App\DTOs\CommitData;
use App\Constants\CommitType;

describe('CalculateImpactScore', function () {
    it('scores feature commits higher than chores', function () {
        $action = new CalculateImpactScore();

        $featureScore = $action->execute(makeCommitData(type: CommitType::Feature));
        $choreScore = $action->execute(makeCommitData(type: CommitType::Chore));

        expect($featureScore)->toBeGreaterThan($choreScore);
    });

    it('increases score for commits with more lines changed', function () {
        $action = new CalculateImpactScore();

        $smallCommit = $action->execute(makeCommitData(additions: 5, deletions: 2));
        $largeCommit = $action->execute(makeCommitData(additions: 150, deletions: 50));

        expect($largeCommit)->toBeGreaterThan($smallCommit);
    });
});

describe('CommitController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->repository = Repository::factory()->for($this->user)->create();
    });

    it('lists commits for authenticated user', function () {
        Commit::factory()->count(3)->for($this->repository)->create();

        $response = $this->actingAs($this->user)
            ->get(route('commits.index', $this->repository));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Commits/Index')
                ->has('commits.data', 3)
            );
    });

    it('prevents access to other users repositories', function () {
        $otherUser = User::factory()->create();
        $otherRepo = Repository::factory()->for($otherUser)->create();

        $this->actingAs($this->user)
            ->get(route('commits.index', $otherRepo))
            ->assertForbidden();
    });
});
```

### Wayfinder in Tests

Use Wayfinder-generated route functions for type-safe route references in frontend tests instead of hardcoded route names:

```typescript
// Good: Type-safe Wayfinder imports
import { index, show } from '@/actions/App/Http/Controllers/CommitController';

// Use in test assertions or navigation
expect(index.url()).toBe('/commits');
expect(show.url(1)).toBe('/commits/1');
```

### Inertia v2 Testing Patterns

When testing Inertia v2 features, cover deferred props, polling, prefetching, and `WhenVisible` components:

```php
<?php

declare(strict_types=1);

// Test deferred props load correctly
it('loads deferred stats after initial render', function () {
    $this->actingAs($this->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('stats') // deferred prop key exists
        );
});

// Test that WhenVisible components have the expected data available
it('provides data for lazy-loaded sections', function () {
    $this->actingAs($this->user)
        ->get(route('repositories.show', $this->repository))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('recentCommits')
        );
});
```

### Testing Best Practices

```php
// Use factories with states
Commit::factory()
    ->highImpact()
    ->for($repository)
    ->create();

// Test edge cases
it('handles empty commit message gracefully', function () {
    $action = new ParseCommitMessage();

    $result = $action->execute('');

    expect($result->type)->toBe(CommitType::Chore);
});

// Use database transactions (automatic in Pest)
uses(RefreshDatabase::class);

// Mock external services
Http::fake([
    'api.github.com/*' => Http::response(['repos' => []], 200),
]);

// Test jobs
Queue::fake();
ProcessGitHubPush::dispatch($payload, $repository);
Queue::assertPushed(ProcessGitHubPush::class);
```

---

## Security

### Authentication & Authorization

```php
// Use policies for authorization
class RepositoryPolicy
{
    public function view(User $user, Repository $repository): bool
    {
        return $user->id === $repository->user_id;
    }

    public function delete(User $user, Repository $repository): bool
    {
        return $user->id === $repository->user_id;
    }
}

// Register in AuthServiceProvider
protected $policies = [
    Repository::class => RepositoryPolicy::class,
];

// Use in controllers
$this->authorize('view', $repository);

// Or in routes
Route::get('/repositories/{repository}', [RepositoryController::class, 'show'])
    ->can('view', 'repository');
```

### Sensitive Data

```php
// Use encrypted casts for sensitive data
protected function casts(): array
{
    return [
        'github_token' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];
}

// Never log sensitive data
Log::info('User authenticated', [
    'user_id' => $user->id,
    // 'token' => $user->github_token, // NEVER
]);

// Use hidden attributes
protected $hidden = [
    'password',
    'github_token',
    'remember_token',
];
```

### Input Validation

```php
// Always validate webhook signatures (handled by Spatie package)
// Always use form requests
// Never trust user input

// Sanitize output
{!! $content !!}  // Dangerous - raw HTML
{{ $content }}    // Safe - escaped
```

---

## Performance

### Query Optimization

```php
// Use indexes for frequently filtered columns
// Use eager loading
$repositories = Repository::with(['commits' => function ($query) {
    $query->latest()->limit(10);
}])->get();

// Use caching for expensive queries
$stats = Cache::remember("user:{$user->id}:stats", 3600, function () use ($user) {
    return $this->metricsService->calculateStats($user);
});

// Use database pagination
Commit::latest()->paginate(25); // Good
Commit::all();                  // Bad for large tables
```

### Caching Strategy

```php
// Cache computed values
Cache::tags(['user', "user:{$userId}"])->put('weekly-stats', $stats, 3600);

// Invalidate on update
Cache::tags(["user:{$userId}"])->flush();

// Use cache locks for expensive operations
Cache::lock("generate-report:{$userId}")->block(5, function () {
    // Generate report
});
```

### N+1 Prevention

```php
// Enable strict mode in development
Model::preventLazyLoading(! app()->isProduction());

// This will throw exception in dev if N+1 detected
foreach ($commits as $commit) {
    $commit->repository->name; // Throws if not eager loaded
}
```

---

## Error Handling

### Custom Exceptions

```php
<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

final class WebhookProcessingException extends Exception
{
    public static function invalidSignature(): self
    {
        return new self('Invalid webhook signature', 401);
    }

    public static function repositoryNotFound(string $repoId): self
    {
        return new self("Repository {$repoId} not found", 404);
    }
}
```

### Exception Handler

```php
// In bootstrap/app.php or exception handler
$exceptions->render(function (WebhookProcessingException $e, Request $request) {
    if ($request->expectsJson()) {
        return response()->json([
            'message' => $e->getMessage(),
        ], $e->getCode());
    }

    return back()->with('error', $e->getMessage());
});
```

### Logging

```php
// Use structured logging
Log::info('Commit processed', [
    'commit_id' => $commit->id,
    'repository_id' => $commit->repository_id,
    'impact_score' => $commit->impact_score,
]);

// Use appropriate log levels
Log::debug('...');   // Detailed debug info
Log::info('...');    // Interesting events
Log::warning('...');  // Exceptional but not errors
Log::error('...');   // Runtime errors
Log::critical('...'); // Critical conditions

// Use context consistently
Log::channel('webhooks')->info('Webhook received', [
    'delivery_id' => $deliveryId,
    'event' => $event,
]);
```

---

## Quick Reference

### Code Style Commands

**Required**: Run `vendor/bin/pint --dirty` before finalizing any changes to ensure code matches the project's expected style.

```bash
# Format only changed files (preferred — run before every commit)
vendor/bin/pint --dirty

# Format all files
vendor/bin/pint

# Check without fixing
vendor/bin/pint --test

# Run static analysis
vendor/bin/phpstan analyse

# Preview automated refactoring
vendor/bin/rector process --dry-run
```

### Common Artisan Commands

```bash
# Create components
php artisan make:model Commit -mf         # Model + migration + factory
php artisan make:controller CommitController --resource
php artisan make:request StoreCommitRequest
php artisan make:job ProcessGitHubPush
php artisan make:action CalculateImpactScore  # Custom command
php artisan make:policy CommitPolicy --model=Commit

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed --class=CommitSeeder

# Cache
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue
php artisan queue:work --queue=webhooks,default
php artisan horizon
```

---

## Git Workflow

### Conventional Commits

All commits must follow the format: `type(scope): description`

Valid types: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`

This is enforced by the `commit-msg` hook. Examples:

```
feat(auth): add GitHub OAuth login flow
fix(metrics): correct impact score calculation for merge commits
refactor(queries): extract repository stats into dedicated Query class
```

### Lessons Learned

The project maintains themed lesson files in `docs/lessons/` to capture architectural decisions and learnings. This is enforced by the pre-push hook for significant commits (`feat`, `fix`, `refactor`, `perf`).

**Check lessons before making architectural decisions.** Update the relevant file after completing significant work:

| Work Type | File |
|-----------|------|
| CQRS, Actions, Queries, DTOs | `docs/lessons/architecture.md` |
| Database, migrations, Eloquent | `docs/lessons/database.md` |
| Vue, Inertia, CSS, UI | `docs/lessons/frontend.md` |
| Tests, Pest, mutations | `docs/lessons/testing.md` |
| CI/CD, Docker, monitoring | `docs/lessons/infrastructure.md` |
| OAuth, APIs, webhooks | `docs/lessons/integrations.md` |
| Git hooks, commits, workflow | `docs/lessons/git-workflow.md` |

---

## Further Reading

- [Laravel Documentation](https://laravel.com/docs)
- [Laracasts](https://laracasts.com)
- [Laravel News](https://laravel-news.com)
- [Spatie Laravel Guidelines](https://spatie.be/guidelines/laravel-php)
