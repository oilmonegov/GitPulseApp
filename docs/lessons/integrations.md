---
tags: [oauth, socialite, saloon, webhooks, github-api, http-client]
updated: 2026-01-18
---

# Integration Lessons

> **Quick summary for agents**: Use regular `<a>` tags for OAuth redirects (not Inertia Link). Cast Socialite user to `Laravel\Socialite\Two\User` for type safety. Saloon provides structured request classes with MockClient for testing. Use Spatie webhook-client for receiving webhooks with custom SignatureValidators. Store OAuth tokens with Laravel's `encrypted` cast.

---

## Sprint 1: OAuth & Socialite Integration

### What went wrong?
- OAuth redirect requires regular `<a>` tags, not Inertia `<Link>` - external OAuth providers need full page redirects. Took debugging to realize this.
- PHPStan Level 8 initially flagged Socialite return types as mixed - had to add proper type annotations and casts

### What went well?
- Spatie webhook-client handled all signature validation complexity
- Encrypted token storage provides security at rest

### Why we chose this direction
- **Regular anchors for OAuth**: Inertia's `<Link>` component performs SPA navigation. OAuth flows redirect to external providers (GitHub), which requires a full page redirect. This is a common pitfall.
- **Spatie webhook-client over DIY**: Webhook signature validation is security-critical. Spatie's package is battle-tested, handles retries, stores webhook calls for debugging.
- **Encrypted token storage**: GitHub OAuth tokens are sensitive. Using Laravel's `encrypted` cast ensures they're encrypted at rest.

### Code Pattern
```vue
<!-- OAuth - use regular anchor (redirects to external provider) -->
<a href="/auth/github">Sign in with GitHub</a>

<!-- Internal navigation - use Inertia Link -->
<Link href="/dashboard">Dashboard</Link>
```

```php
// Proper Socialite type casting
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

public function callback(): RedirectResponse
{
    try {
        /** @var SocialiteUser $githubUser */
        $githubUser = Socialite::driver('github')->user();

        // Access token for API calls
        $token = $githubUser->token;
    } catch (InvalidStateException $e) {
        return redirect('/login')->with('error', 'Authentication failed');
    }
}

// Model: encrypted token storage
protected function casts(): array
{
    return [
        'github_token' => 'encrypted',
    ];
}
```

---

## Sprint 4: Saloon HTTP Client Integration

### What went wrong?
- PHPStan wasn't in pre-push hooks - static analysis errors slipped through to CI
- Wayfinder `--with-form` flag was missing in CI - frontend type-check failed because `.form()` helpers weren't generated
- Local `npm run type-check` passed because Wayfinder types were already generated from previous builds - CI started fresh without them

### What went well?
- Saloon v3 refactoring went smoothly - GitHubService maintains backward compatibility as an adapter
- MockClient testing pattern is cleaner than Http::fake() - request-class-based mocking is more explicit
- Architecture tests for Integrations namespace catch structural violations automatically

### Why we chose this direction
- **Saloon over HTTP client**: Saloon provides structured request classes, better testability with MockClient, and a consistent API pattern. Each endpoint is a self-contained Request class.
- **Service as adapter**: GitHubService wraps the Saloon connector, keeping all public method signatures identical. Consumers (ProcessPushEventAction) work without changes.
- **Final classes for connectors/requests**: Matches codebase conventions. Architecture tests enforce this automatically.

### Code Pattern
```php
// Saloon Request class
final class GetRepository extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        private readonly string $owner,
        private readonly string $repo,
    ) {}

    public function resolveEndpoint(): string
    {
        return "/repos/{$this->owner}/{$this->repo}";
    }
}

// Saloon Connector
final class GitHubConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://api.github.com';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github.v3+json',
        ];
    }
}

// Service adapter pattern
final class GitHubService
{
    public function __construct(
        private readonly GitHubConnector $connector,
    ) {}

    public function getRepository(string $owner, string $repo): array
    {
        $response = $this->connector->send(new GetRepository($owner, $repo));
        return $response->json();
    }
}

// MockClient testing
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('fetches repository data', function () {
    $mockClient = new MockClient([
        GetRepository::class => MockResponse::make(['name' => 'my-repo'], 200),
    ]);

    $connector = new GitHubConnector();
    $connector->withMockClient($mockClient);

    $response = $connector->send(new GetRepository('owner', 'repo'));

    expect($response->json('name'))->toBe('my-repo');
});
```

---

## Webhook Integration

### What went well?
- Spatie webhook-client handles signature validation, storage, and retry logic
- Custom SignatureValidator allows GitHub's specific header format

### Why we chose this direction
- **Spatie webhook-client over custom implementation**: The package handles all the complexity - signature validation, storing webhook payloads for debugging, queue-based processing, retry logic. Security-critical code shouldn't be DIY.
- **Custom SignatureValidator**: GitHub uses `X-Hub-Signature-256` header with HMAC-SHA256. The validator interface is clean and testable.
- **ProcessWebhookJob for async processing**: Webhooks should return 200 immediately. Heavy processing happens in queued jobs to avoid timeouts.

### Code Pattern
```php
// Custom SignatureValidator for GitHub
final class GitHubSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header($config->signatureHeaderName);
        $computed = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            $config->signingSecret
        );

        return hash_equals($computed, $signature ?? '');
    }
}

// Webhook processing job
class ProcessGitHubWebhookJob extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = $this->webhookCall->headers['x-github-event'][0] ?? null;

        match ($event) {
            'push' => $this->handlePush($payload),
            'pull_request' => $this->handlePullRequest($payload),
            default => null,
        };
    }
}

// config/webhook-client.php
return [
    'configs' => [
        [
            'name' => 'github',
            'signing_secret' => env('GITHUB_WEBHOOK_SECRET'),
            'signature_header_name' => 'X-Hub-Signature-256',
            'signature_validator' => GitHubSignatureValidator::class,
            'webhook_profile' => GitHubWebhookProfile::class,
            'process_webhook_job' => ProcessGitHubWebhookJob::class,
        ],
    ],
];

// Exclude from CSRF in bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->validateCsrfTokens(except: [
        'webhooks/*',
    ]);
});
```

---

## Entry Template

```markdown
## [Feature Name]

### What went wrong?
- Issue description and root cause

### What went well?
- Success description and contributing factors

### Why we chose this direction
- Decision and reasoning
```
