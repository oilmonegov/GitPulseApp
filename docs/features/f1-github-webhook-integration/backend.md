# F1: Backend Implementation

## Architecture Overview

```
routes/webhooks.php
        │
        ▼
Spatie Webhook Client Middleware
        │
        ▼
┌─────────────────────────────────┐
│   GitHubSignatureValidator      │ ← Verifies HMAC-SHA256
└─────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────┐
│   GitHubWebhookProfile          │ ← Filters event types
└─────────────────────────────────┘
        │
        ▼
┌─────────────────────────────────┐
│   ProcessGitHubWebhook (Job)    │ ← Main dispatcher
└─────────────────────────────────┘
        │
        ├──────────────────────────┐
        ▼                          ▼
ProcessGitHubPush          ProcessPullRequest
        │                          │
        ▼                          ▼
ParseCommitMessage         Update PR status
CalculateImpactScore       Boost merge scores
Create Commit Record
Broadcast Event
```

---

## Route Definition

### `routes/webhooks.php`

```php
<?php

use Illuminate\Support\Facades\Route;

Route::webhooks('/webhooks/github', 'github');
```

### `app/Providers/AppServiceProvider.php`

```php
public function boot(): void
{
    Route::middleware('api')
        ->prefix('api')
        ->group(base_path('routes/webhooks.php'));
}
```

**Final Endpoint**: `POST /api/webhooks/github`

---

## Webhook Processing Classes

### GitHubSignatureValidator

```php
<?php

declare(strict_types=1);

namespace App\Webhooks\GitHub;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class GitHubSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $expectedSignature = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            $config->signingSecret
        );

        return hash_equals($expectedSignature, $signature);
    }
}
```

### GitHubWebhookProfile

```php
<?php

declare(strict_types=1);

namespace App\Webhooks\GitHub;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class GitHubWebhookProfile implements WebhookProfile
{
    private const SUPPORTED_EVENTS = ['push', 'pull_request'];

    public function shouldProcess(Request $request): bool
    {
        $event = $request->header('X-GitHub-Event');

        return in_array($event, self::SUPPORTED_EVENTS, true);
    }
}
```

### ProcessGitHubWebhook

```php
<?php

declare(strict_types=1);

namespace App\Webhooks\GitHub;

use App\Jobs\ProcessGitHubPush;
use App\Jobs\ProcessPullRequest;
use App\Models\Repository;
use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessGitHubWebhook extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = $this->webhookCall->headers['x-github-event'][0] ?? null;

        $repositoryGitHubId = $payload['repository']['id'] ?? null;

        if (! $repositoryGitHubId) {
            Log::warning('Webhook received without repository ID', [
                'webhook_id' => $this->webhookCall->id,
            ]);

            return;
        }

        $repository = Repository::query()
            ->where('github_id', (string) $repositoryGitHubId)
            ->where('is_active', true)
            ->first();

        if (! $repository) {
            Log::info('Webhook received for unknown or inactive repository', [
                'github_id' => $repositoryGitHubId,
            ]);

            return;
        }

        match ($event) {
            'push' => $this->handlePush($repository, $payload),
            'pull_request' => $this->handlePullRequest($repository, $payload),
            default => Log::info("Unhandled event type: {$event}"),
        };
    }

    private function handlePush(Repository $repository, array $payload): void
    {
        $commits = $payload['commits'] ?? [];

        foreach ($commits as $commitData) {
            ProcessGitHubPush::dispatch($repository, $commitData)
                ->onQueue('commits');
        }

        Log::info('Push event processed', [
            'repository' => $repository->full_name,
            'commits' => count($commits),
        ]);
    }

    private function handlePullRequest(Repository $repository, array $payload): void
    {
        $action = $payload['action'] ?? null;

        if (! in_array($action, ['opened', 'closed', 'merged'], true)) {
            return;
        }

        ProcessPullRequest::dispatch($repository, $payload)
            ->onQueue('commits');

        Log::info('Pull request event processed', [
            'repository' => $repository->full_name,
            'action' => $action,
        ]);
    }
}
```

---

## Queue Jobs

### ProcessGitHubPush

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Commits\CalculateImpactScore;
use App\Actions\Commits\CategorizeCommit;
use App\Actions\Commits\ParseCommitMessage;
use App\Data\CommitData;
use App\Events\CommitProcessed;
use App\Models\Commit;
use App\Models\Repository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGitHubPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public Repository $repository,
        public array $commitData,
    ) {}

    public function handle(
        ParseCommitMessage $parseMessage,
        CategorizeCommit $categorize,
        CalculateImpactScore $calculateScore,
    ): void {
        // Check for duplicate
        if (Commit::where('sha', $this->commitData['id'])->exists()) {
            Log::info('Duplicate commit skipped', ['sha' => $this->commitData['id']]);

            return;
        }

        // Parse commit data
        $data = CommitData::fromGitHubPayload($this->commitData);

        // Parse message for type and refs
        $parsed = $parseMessage->execute($data->message);
        $data->commit_type = $parsed->type ?? $categorize->execute($data->message);
        $data->external_refs = $parsed->refs;

        // Create commit record
        $commit = Commit::create([
            'repository_id' => $this->repository->id,
            'user_id' => $this->repository->user_id,
            'sha' => $data->sha,
            'message' => $data->message,
            'author_name' => $data->author_name,
            'author_email' => $data->author_email,
            'committed_at' => $data->committed_at,
            'additions' => $data->additions,
            'deletions' => $data->deletions,
            'files_changed' => $data->files_changed,
            'files' => $data->files,
            'commit_type' => $data->commit_type,
            'external_refs' => $data->external_refs,
            'is_merge' => $data->is_merge,
        ]);

        // Calculate impact score
        $impactScore = $calculateScore->execute($commit);
        $commit->update(['impact_score' => $impactScore]);

        // Update daily metrics
        CalculateDailyMetrics::dispatch($commit->user_id, $commit->committed_at->toDateString())
            ->onQueue('metrics');

        // Broadcast for real-time updates
        broadcast(new CommitProcessed($commit, $impactScore))->toOthers();

        Log::info('Commit processed', [
            'sha' => $commit->sha,
            'repository' => $this->repository->full_name,
            'impact_score' => $impactScore,
        ]);
    }

    public function tags(): array
    {
        return [
            'repository:' . $this->repository->id,
            'sha:' . ($this->commitData['id'] ?? 'unknown'),
        ];
    }
}
```

### ProcessPullRequest

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Commit;
use App\Models\Repository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessPullRequest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public Repository $repository,
        public array $payload,
    ) {}

    public function handle(): void
    {
        $action = $this->payload['action'];
        $pr = $this->payload['pull_request'];

        match ($action) {
            'opened' => $this->handleOpened($pr),
            'closed' => $this->handleClosed($pr),
            'merged' => $this->handleMerged($pr),
            default => null,
        };
    }

    private function handleOpened(array $pr): void
    {
        Log::info('PR opened', [
            'repository' => $this->repository->full_name,
            'pr_number' => $pr['number'],
            'title' => $pr['title'],
        ]);
    }

    private function handleClosed(array $pr): void
    {
        if ($pr['merged'] ?? false) {
            $this->handleMerged($pr);

            return;
        }

        Log::info('PR closed without merge', [
            'repository' => $this->repository->full_name,
            'pr_number' => $pr['number'],
        ]);
    }

    private function handleMerged(array $pr): void
    {
        // Boost impact scores for commits in this PR
        $mergeCommitSha = $pr['merge_commit_sha'] ?? null;

        if ($mergeCommitSha) {
            Commit::where('repository_id', $this->repository->id)
                ->where('sha', $mergeCommitSha)
                ->update(['is_merge' => true]);
        }

        Log::info('PR merged', [
            'repository' => $this->repository->full_name,
            'pr_number' => $pr['number'],
            'merge_commit' => $mergeCommitSha,
        ]);
    }

    public function tags(): array
    {
        return [
            'repository:' . $this->repository->id,
            'pr:' . ($this->payload['pull_request']['number'] ?? 'unknown'),
        ];
    }
}
```

---

## Configuration

### `config/webhook-client.php`

```php
<?php

return [
    'configs' => [
        [
            'name' => 'github',
            'signing_secret' => env('GITHUB_WEBHOOK_SECRET'),
            'signature_header_name' => 'X-Hub-Signature-256',
            'signature_validator' => App\Webhooks\GitHub\GitHubSignatureValidator::class,
            'webhook_profile' => App\Webhooks\GitHub\GitHubWebhookProfile::class,
            'webhook_model' => Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => App\Webhooks\GitHub\ProcessGitHubWebhook::class,
        ],
    ],

    'delete_after_days' => 30,
];
```

### `config/horizon.php` (Queue Configuration)

```php
'environments' => [
    'production' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['webhooks', 'commits', 'metrics', 'default'],
            'balance' => 'auto',
            'processes' => 10,
            'tries' => 3,
            'timeout' => 120,
        ],
    ],

    'local' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['webhooks', 'commits', 'metrics', 'default'],
            'balance' => 'auto',
            'processes' => 3,
            'tries' => 3,
        ],
    ],
],
```

---

## Rate Limiting

### `app/Providers/AppServiceProvider.php`

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('webhooks', function (Request $request) {
        return Limit::perMinute(100)->by($request->ip());
    });
}
```

### Apply to Routes

```php
// routes/webhooks.php
Route::middleware(['throttle:webhooks'])
    ->group(function () {
        Route::webhooks('/webhooks/github', 'github');
    });
```

---

## Broadcasting Events

### CommitProcessed Event

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Commit;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommitProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Commit $commit,
        public float $impact_score,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->commit->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'commit_id' => $this->commit->id,
            'sha' => $this->commit->sha,
            'message' => $this->commit->message,
            'repository' => $this->commit->repository->name,
            'impact_score' => $this->impact_score,
            'committed_at' => $this->commit->committed_at->toISOString(),
            'commit_type' => $this->commit->commit_type->value,
        ];
    }

    public function broadcastAs(): string
    {
        return 'CommitProcessed';
    }
}
```

### Channel Authorization

```php
// routes/channels.php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

---

## Service Classes

### GitHubService (Repository Management)

```php
<?php

declare(strict_types=1);

namespace App\Services\GitHub;

use App\Models\Repository;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class GitHubService
{
    private function client(User $user): PendingRequest
    {
        return Http::withToken($user->github_token)
            ->baseUrl('https://api.github.com')
            ->acceptJson();
    }

    public function listRepositories(User $user): array
    {
        $response = $this->client($user)
            ->get('/user/repos', [
                'sort' => 'updated',
                'per_page' => 100,
            ]);

        return $response->json();
    }

    public function createWebhook(User $user, Repository $repository): array
    {
        $response = $this->client($user)
            ->post("/repos/{$repository->full_name}/hooks", [
                'name' => 'web',
                'active' => true,
                'events' => ['push', 'pull_request'],
                'config' => [
                    'url' => config('app.url') . '/api/webhooks/github',
                    'content_type' => 'json',
                    'secret' => $repository->webhook_secret,
                    'insecure_ssl' => '0',
                ],
            ]);

        return $response->json();
    }

    public function deleteWebhook(User $user, Repository $repository): bool
    {
        if (! $repository->webhook_id) {
            return true;
        }

        $response = $this->client($user)
            ->delete("/repos/{$repository->full_name}/hooks/{$repository->webhook_id}");

        return $response->successful();
    }
}
```

---

## Error Handling

### Failed Job Handling

```php
// app/Providers/AppServiceProvider.php
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Queue;

public function boot(): void
{
    Queue::failing(function (JobFailed $event) {
        Log::error('Job failed', [
            'connection' => $event->connectionName,
            'job' => $event->job->getName(),
            'exception' => $event->exception->getMessage(),
        ]);
    });
}
```

### Retry Configuration

Jobs are configured with:
- 3 retry attempts
- Exponential backoff: 10s, 60s, 300s
- Dead letter queue for permanent failures
