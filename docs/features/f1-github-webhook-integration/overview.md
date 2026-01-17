# F1: GitHub Webhook Integration

## Feature Summary

Receive and process GitHub webhook events for commits, pushes, and pull requests. This is the foundational data ingestion layer that powers all GitPulse analytics.

## Priority

**P0 (MVP)** - Core feature required for launch

## Goals

1. Securely receive GitHub webhook events in real-time
2. Support multiple repositories per user
3. Support multiple GitHub organizations
4. Validate webhook signatures (HMAC-SHA256)
5. Process events asynchronously via queues
6. Handle rate limiting gracefully

## Supported Event Types

| Event | Actions | Description |
|-------|---------|-------------|
| `push` | - | Triggered on code pushes to any branch |
| `pull_request` | `opened`, `closed`, `merged` | PR lifecycle events |

## Acceptance Criteria

- [ ] Webhook receives commit within 5 seconds of push
- [ ] All commit metadata captured (message, author, timestamp, files changed, additions/deletions)
- [ ] Failed webhooks retry with exponential backoff
- [ ] Webhook endpoint validates GitHub signature before processing
- [ ] Rate limiting: 100 webhooks/minute per repository
- [ ] Events for inactive repositories are ignored
- [ ] Duplicate commits (same SHA) are not processed twice

## Stepwise Refinement

### Level 0: High-Level Flow

```
GitHub Push → GitPulse Webhook Endpoint → Queue Job → Database
```

### Level 1: Component Breakdown

```
GitHub Repository
       │
       ▼ (HTTP POST with signature)
┌──────────────────────────────────┐
│   /api/webhooks/github           │
│   (Spatie Webhook Client)        │
├──────────────────────────────────┤
│ 1. Verify X-Hub-Signature-256    │
│ 2. Check event type (profile)    │
│ 3. Store WebhookCall record      │
│ 4. Dispatch ProcessWebhook job   │
└──────────────────────────────────┘
       │
       ▼ (Redis Queue)
┌──────────────────────────────────┐
│   ProcessGitHubWebhook Job       │
├──────────────────────────────────┤
│ 1. Find Repository by github_id  │
│ 2. Check repository.is_active    │
│ 3. Route by event type           │
│    - push → ProcessGitHubPush    │
│    - PR → ProcessPullRequest     │
└──────────────────────────────────┘
       │
       ▼ (Per-commit jobs)
┌──────────────────────────────────┐
│   ProcessGitHubPush Job          │
├──────────────────────────────────┤
│ 1. Parse commit data             │
│ 2. Create/update Commit record   │
│ 3. Trigger CalculateImpactScore  │
│ 4. Broadcast CommitProcessed     │
└──────────────────────────────────┘
```

### Level 2: Detailed Steps

#### 2.1 Webhook Receipt

1. Spatie Webhook Client receives POST at `/api/webhooks/github`
2. `GitHubSignatureValidator` verifies `X-Hub-Signature-256` header
3. `GitHubWebhookProfile` checks if event type is supported
4. Request stored in `webhook_calls` table
5. `ProcessGitHubWebhook` job dispatched to `webhooks` queue

#### 2.2 Event Routing

1. Job loads `WebhookCall` payload
2. Extract `repository.id` from payload
3. Query `Repository::where('github_id', $id)->where('is_active', true)`
4. If not found or inactive, log and exit
5. Match event type:
   - `push`: Dispatch `ProcessGitHubPush` for each commit
   - `pull_request`: Dispatch `ProcessPullRequest`

#### 2.3 Push Processing

1. Extract commit array from payload
2. For each commit:
   - Check for existing commit by SHA (deduplication)
   - Parse commit message for type (feat, fix, etc.)
   - Extract file changes metadata
   - Create `Commit` record
   - Calculate impact score
   - Update `DailyMetric` aggregates
   - Broadcast `CommitProcessed` event (Reverb)

#### 2.4 Pull Request Processing

1. Extract PR data from payload
2. Handle based on action:
   - `opened`: Create PR tracking record
   - `merged`: Mark commits as merged, boost impact scores
   - `closed`: Update PR status

## Dependencies

### Internal
- Repository model (for webhook routing)
- Commit model (for data storage)
- User model (for ownership)

### External
- Spatie Laravel Webhook Client (`spatie/laravel-webhook-client`)
- Laravel Horizon (queue management)
- Redis (queue backend)

## Configuration

### Required Environment Variables

```env
GITHUB_WEBHOOK_SECRET=your-webhook-secret
```

### Webhook Client Config

File: `config/webhook-client.php`

```php
'configs' => [
    [
        'name' => 'github',
        'signing_secret' => env('GITHUB_WEBHOOK_SECRET'),
        'signature_header_name' => 'X-Hub-Signature-256',
        'signature_validator' => GitHubSignatureValidator::class,
        'webhook_profile' => GitHubWebhookProfile::class,
        'process_webhook_job' => ProcessGitHubWebhook::class,
    ],
],
```

## Error Handling

| Scenario | Handling |
|----------|----------|
| Invalid signature | Return 500, log attempt, do not store |
| Unsupported event | Accept (200), do not process |
| Repository not found | Log warning, exit job gracefully |
| Repository inactive | Log info, exit job gracefully |
| Duplicate commit SHA | Skip processing, log info |
| Job failure | Retry 3 times with exponential backoff |
| Rate limit exceeded | Return 429, client should retry |

## Security Considerations

1. **Signature Verification**: All webhooks must pass HMAC-SHA256 validation
2. **Rate Limiting**: 100 requests/minute per IP via Laravel rate limiter
3. **No Raw Storage**: Webhook payload stored for debugging, auto-deleted after 30 days
4. **Queue Isolation**: Webhook jobs run on dedicated `webhooks` queue

## Related Files

| File | Purpose |
|------|---------|
| `app/Webhooks/GitHub/GitHubSignatureValidator.php` | HMAC verification |
| `app/Webhooks/GitHub/GitHubWebhookProfile.php` | Event filtering |
| `app/Webhooks/GitHub/ProcessGitHubWebhook.php` | Main webhook job |
| `app/Jobs/ProcessGitHubPush.php` | Push event handler |
| `app/Jobs/ProcessPullRequest.php` | PR event handler |
| `config/webhook-client.php` | Spatie configuration |
| `routes/webhooks.php` | Route definition |
