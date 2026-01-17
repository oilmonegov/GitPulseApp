# F1: Testing

## Test Coverage Requirements

- **Unit Tests**: 90% coverage for webhook processing classes
- **Feature Tests**: All webhook endpoints and job flows
- **Integration Tests**: End-to-end webhook receipt to database

---

## Feature Tests

### `tests/Feature/Webhooks/GitHubWebhookTest.php`

```php
<?php

declare(strict_types=1);

use App\Jobs\ProcessGitHubPush;
use App\Jobs\ProcessPullRequest;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookClient\Models\WebhookCall;

beforeEach(function () {
    Queue::fake();

    $this->user = User::factory()->create([
        'github_id' => '12345',
    ]);

    $this->repository = Repository::factory()
        ->for($this->user)
        ->create([
            'github_id' => '67890',
            'is_active' => true,
        ]);
});

describe('Webhook Signature Validation', function () {
    it('accepts valid webhook with correct signature', function () {
        $payload = createPushPayload($this->repository->github_id);
        $signature = createGitHubSignature($payload);

        $response = $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        $response->assertOk();
        expect(WebhookCall::count())->toBe(1);
    });

    it('rejects webhook with invalid signature', function () {
        $payload = createPushPayload($this->repository->github_id);

        $response = $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => 'sha256=invalid',
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        $response->assertStatus(500);
        expect(WebhookCall::count())->toBe(0);
    });

    it('rejects webhook without signature header', function () {
        $payload = createPushPayload($this->repository->github_id);

        $response = $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        $response->assertStatus(500);
    });
});

describe('Push Event Processing', function () {
    it('dispatches job for each commit in push event', function () {
        $payload = createPushPayload($this->repository->github_id, commitCount: 3);
        $signature = createGitHubSignature($payload);

        $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        Queue::assertPushed(ProcessGitHubPush::class, 3);
    });

    it('dispatches jobs to commits queue', function () {
        $payload = createPushPayload($this->repository->github_id);
        $signature = createGitHubSignature($payload);

        $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        Queue::assertPushedOn('commits', ProcessGitHubPush::class);
    });

    it('ignores events for inactive repositories', function () {
        $this->repository->update(['is_active' => false]);

        $payload = createPushPayload($this->repository->github_id);
        $signature = createGitHubSignature($payload);

        $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        Queue::assertNothingPushed();
    });

    it('ignores events for unknown repositories', function () {
        $payload = createPushPayload('unknown-repo-id');
        $signature = createGitHubSignature($payload);

        $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        Queue::assertNothingPushed();
    });
});

describe('Pull Request Event Processing', function () {
    it('processes opened pull requests', function () {
        $payload = createPullRequestPayload($this->repository->github_id, 'opened');
        $signature = createGitHubSignature($payload);

        $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'pull_request',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        Queue::assertPushed(ProcessPullRequest::class);
    });

    it('processes merged pull requests', function () {
        $payload = createPullRequestPayload($this->repository->github_id, 'closed', merged: true);
        $signature = createGitHubSignature($payload);

        $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'pull_request',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        Queue::assertPushed(ProcessPullRequest::class);
    });

    it('ignores unsupported PR actions', function () {
        $payload = createPullRequestPayload($this->repository->github_id, 'labeled');
        $signature = createGitHubSignature($payload);

        $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'pull_request',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        Queue::assertNotPushed(ProcessPullRequest::class);
    });
});

describe('Event Type Filtering', function () {
    it('ignores unsupported event types', function () {
        $payload = ['action' => 'created'];
        $signature = createGitHubSignature($payload);

        $response = $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'star',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        $response->assertOk();
        Queue::assertNothingPushed();
    });
});

// Helper functions
function createPushPayload(string $repoId, int $commitCount = 1): array
{
    return [
        'repository' => ['id' => $repoId],
        'commits' => collect(range(1, $commitCount))->map(fn () => [
            'id' => fake()->sha1(),
            'message' => fake()->randomElement(['feat', 'fix', 'chore']) . ': ' . fake()->sentence(),
            'author' => [
                'name' => fake()->name(),
                'email' => fake()->email(),
            ],
            'timestamp' => now()->toISOString(),
            'added' => [],
            'removed' => [],
            'modified' => ['src/' . fake()->word() . '.php'],
        ])->toArray(),
    ];
}

function createPullRequestPayload(string $repoId, string $action = 'opened', bool $merged = false): array
{
    return [
        'action' => $action,
        'repository' => ['id' => $repoId],
        'pull_request' => [
            'id' => fake()->randomNumber(8),
            'number' => fake()->randomNumber(3),
            'title' => fake()->sentence(),
            'merged' => $merged,
            'merge_commit_sha' => $merged ? fake()->sha1() : null,
        ],
    ];
}

function createGitHubSignature(array $payload): string
{
    $secret = config('webhook-client.configs.0.signing_secret');

    return 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
}
```

---

## Unit Tests

### `tests/Unit/Webhooks/GitHubSignatureValidatorTest.php`

```php
<?php

declare(strict_types=1);

use App\Webhooks\GitHub\GitHubSignatureValidator;
use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;

describe('GitHubSignatureValidator', function () {
    it('validates correct HMAC-SHA256 signature', function () {
        $validator = new GitHubSignatureValidator();
        $secret = 'test-secret';
        $payload = '{"test": "data"}';

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Hub-Signature-256', $signature);

        $config = new WebhookConfig([
            'name' => 'github',
            'signing_secret' => $secret,
            'signature_header_name' => 'X-Hub-Signature-256',
            'signature_validator' => GitHubSignatureValidator::class,
            'webhook_profile' => \App\Webhooks\GitHub\GitHubWebhookProfile::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\Webhooks\GitHub\ProcessGitHubWebhook::class,
        ]);

        expect($validator->isValid($request, $config))->toBeTrue();
    });

    it('rejects invalid signature', function () {
        $validator = new GitHubSignatureValidator();
        $secret = 'test-secret';
        $payload = '{"test": "data"}';

        $request = Request::create('/webhook', 'POST', [], [], [], [], $payload);
        $request->headers->set('X-Hub-Signature-256', 'sha256=invalid');

        $config = new WebhookConfig([
            'name' => 'github',
            'signing_secret' => $secret,
            'signature_header_name' => 'X-Hub-Signature-256',
            'signature_validator' => GitHubSignatureValidator::class,
            'webhook_profile' => \App\Webhooks\GitHub\GitHubWebhookProfile::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\Webhooks\GitHub\ProcessGitHubWebhook::class,
        ]);

        expect($validator->isValid($request, $config))->toBeFalse();
    });

    it('rejects missing signature header', function () {
        $validator = new GitHubSignatureValidator();

        $request = Request::create('/webhook', 'POST', [], [], [], [], '{"test": "data"}');

        $config = new WebhookConfig([
            'name' => 'github',
            'signing_secret' => 'test-secret',
            'signature_header_name' => 'X-Hub-Signature-256',
            'signature_validator' => GitHubSignatureValidator::class,
            'webhook_profile' => \App\Webhooks\GitHub\GitHubWebhookProfile::class,
            'webhook_model' => \Spatie\WebhookClient\Models\WebhookCall::class,
            'process_webhook_job' => \App\Webhooks\GitHub\ProcessGitHubWebhook::class,
        ]);

        expect($validator->isValid($request, $config))->toBeFalse();
    });
});
```

### `tests/Unit/Webhooks/GitHubWebhookProfileTest.php`

```php
<?php

declare(strict_types=1);

use App\Webhooks\GitHub\GitHubWebhookProfile;
use Illuminate\Http\Request;

describe('GitHubWebhookProfile', function () {
    it('accepts push events', function () {
        $profile = new GitHubWebhookProfile();

        $request = Request::create('/webhook', 'POST');
        $request->headers->set('X-GitHub-Event', 'push');

        expect($profile->shouldProcess($request))->toBeTrue();
    });

    it('accepts pull_request events', function () {
        $profile = new GitHubWebhookProfile();

        $request = Request::create('/webhook', 'POST');
        $request->headers->set('X-GitHub-Event', 'pull_request');

        expect($profile->shouldProcess($request))->toBeTrue();
    });

    it('rejects unsupported events', function () {
        $profile = new GitHubWebhookProfile();

        $unsupportedEvents = ['star', 'fork', 'issues', 'release', 'deployment'];

        foreach ($unsupportedEvents as $event) {
            $request = Request::create('/webhook', 'POST');
            $request->headers->set('X-GitHub-Event', $event);

            expect($profile->shouldProcess($request))->toBeFalse();
        }
    });

    it('rejects requests without event header', function () {
        $profile = new GitHubWebhookProfile();

        $request = Request::create('/webhook', 'POST');

        expect($profile->shouldProcess($request))->toBeFalse();
    });
});
```

---

## Job Tests

### `tests/Feature/Jobs/ProcessGitHubPushTest.php`

```php
<?php

declare(strict_types=1);

use App\Events\CommitProcessed;
use App\Jobs\CalculateDailyMetrics;
use App\Jobs\ProcessGitHubPush;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Event::fake([CommitProcessed::class]);
    Queue::fake([CalculateDailyMetrics::class]);

    $this->user = User::factory()->create();
    $this->repository = Repository::factory()
        ->for($this->user)
        ->create();
});

describe('ProcessGitHubPush Job', function () {
    it('creates commit record from webhook payload', function () {
        $commitData = [
            'id' => fake()->sha1(),
            'message' => 'feat: add user authentication',
            'author' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
            'timestamp' => now()->toISOString(),
            'added' => ['src/Auth/LoginController.php'],
            'removed' => [],
            'modified' => ['routes/web.php'],
        ];

        $job = new ProcessGitHubPush($this->repository, $commitData);
        $job->handle(
            new \App\Actions\Commits\ParseCommitMessage(),
            new \App\Actions\Commits\CategorizeCommit(),
            new \App\Actions\Commits\CalculateImpactScore(),
        );

        expect(Commit::count())->toBe(1);

        $commit = Commit::first();
        expect($commit->sha)->toBe($commitData['id']);
        expect($commit->message)->toBe($commitData['message']);
        expect($commit->author_name)->toBe('Test User');
        expect($commit->commit_type->value)->toBe('feat');
    });

    it('skips duplicate commits', function () {
        $sha = fake()->sha1();

        Commit::factory()
            ->for($this->repository)
            ->for($this->user)
            ->create(['sha' => $sha]);

        $commitData = [
            'id' => $sha,
            'message' => 'duplicate commit',
            'author' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
            'timestamp' => now()->toISOString(),
            'added' => [],
            'removed' => [],
            'modified' => [],
        ];

        $job = new ProcessGitHubPush($this->repository, $commitData);
        $job->handle(
            new \App\Actions\Commits\ParseCommitMessage(),
            new \App\Actions\Commits\CategorizeCommit(),
            new \App\Actions\Commits\CalculateImpactScore(),
        );

        expect(Commit::count())->toBe(1);
    });

    it('broadcasts CommitProcessed event', function () {
        $commitData = [
            'id' => fake()->sha1(),
            'message' => 'fix: resolve login bug',
            'author' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
            'timestamp' => now()->toISOString(),
            'added' => [],
            'removed' => [],
            'modified' => ['src/Auth.php'],
        ];

        $job = new ProcessGitHubPush($this->repository, $commitData);
        $job->handle(
            new \App\Actions\Commits\ParseCommitMessage(),
            new \App\Actions\Commits\CategorizeCommit(),
            new \App\Actions\Commits\CalculateImpactScore(),
        );

        Event::assertDispatched(CommitProcessed::class);
    });

    it('dispatches daily metrics calculation', function () {
        $commitData = [
            'id' => fake()->sha1(),
            'message' => 'chore: update dependencies',
            'author' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
            'timestamp' => now()->toISOString(),
            'added' => [],
            'removed' => [],
            'modified' => ['composer.json'],
        ];

        $job = new ProcessGitHubPush($this->repository, $commitData);
        $job->handle(
            new \App\Actions\Commits\ParseCommitMessage(),
            new \App\Actions\Commits\CategorizeCommit(),
            new \App\Actions\Commits\CalculateImpactScore(),
        );

        Queue::assertPushed(CalculateDailyMetrics::class);
    });

    it('extracts external references from commit message', function () {
        $commitData = [
            'id' => fake()->sha1(),
            'message' => 'fix: resolve issue #123 and JIRA-456',
            'author' => [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ],
            'timestamp' => now()->toISOString(),
            'added' => [],
            'removed' => [],
            'modified' => ['src/Bug.php'],
        ];

        $job = new ProcessGitHubPush($this->repository, $commitData);
        $job->handle(
            new \App\Actions\Commits\ParseCommitMessage(),
            new \App\Actions\Commits\CategorizeCommit(),
            new \App\Actions\Commits\CalculateImpactScore(),
        );

        $commit = Commit::first();
        expect($commit->external_refs)->toContain('#123');
        expect($commit->external_refs)->toContain('JIRA-456');
    });
});
```

---

## Integration Tests

### `tests/Feature/Webhooks/WebhookIntegrationTest.php`

```php
<?php

declare(strict_types=1);

use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;

describe('End-to-End Webhook Processing', function () {
    it('processes webhook and creates commit in database', function () {
        $user = User::factory()->create();
        $repository = Repository::factory()
            ->for($user)
            ->create(['is_active' => true]);

        $sha = fake()->sha1();
        $payload = [
            'repository' => ['id' => $repository->github_id],
            'commits' => [
                [
                    'id' => $sha,
                    'message' => 'feat: implement new feature',
                    'author' => [
                        'name' => 'Test User',
                        'email' => 'test@example.com',
                    ],
                    'timestamp' => now()->toISOString(),
                    'added' => ['src/NewFeature.php'],
                    'removed' => [],
                    'modified' => [],
                ],
            ],
        ];

        $signature = 'sha256=' . hash_hmac(
            'sha256',
            json_encode($payload),
            config('webhook-client.configs.0.signing_secret')
        );

        $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ])->assertOk();

        // Process queued jobs synchronously for testing
        $this->artisan('queue:work', ['--once' => true]);

        expect(Commit::where('sha', $sha)->exists())->toBeTrue();

        $commit = Commit::where('sha', $sha)->first();
        expect($commit->repository_id)->toBe($repository->id);
        expect($commit->user_id)->toBe($user->id);
        expect($commit->impact_score)->toBeGreaterThan(0);
    });
});
```

---

## Test Utilities

### `tests/Pest.php` Additions

```php
// Add webhook helper functions
function postWebhook(string $event, array $payload, ?string $secret = null): \Illuminate\Testing\TestResponse
{
    $secret ??= config('webhook-client.configs.0.signing_secret');
    $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

    return test()->postJson('/api/webhooks/github', $payload, [
        'X-GitHub-Event' => $event,
        'X-Hub-Signature-256' => $signature,
        'X-GitHub-Delivery' => fake()->uuid(),
    ]);
}
```

---

## Test Environment Configuration

### `.env.testing`

```env
GITHUB_WEBHOOK_SECRET=test-webhook-secret
QUEUE_CONNECTION=sync
```

### `phpunit.xml` Configuration

```xml
<env name="GITHUB_WEBHOOK_SECRET" value="test-webhook-secret"/>
<env name="QUEUE_CONNECTION" value="sync"/>
```
