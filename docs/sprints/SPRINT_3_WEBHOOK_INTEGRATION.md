# Sprint 3: GitHub Webhook Integration

**Status:** Completed
**Duration:** Sprint 3
**Date Completed:** 2026-01-17

## Overview

Sprint 3 implemented the full GitHub webhook processing pipeline using Spatie's `laravel-webhook-client` package. This sprint connects GitHub push events to commit storage, including DTOs for type-safe data transfer, queued job processing, and CQRS actions for repository and commit creation.

---

## Completed Tasks

### 1. Webhook Processing Pipeline

The webhook pipeline follows this flow:

```
GitHub Push Event
       │
       ▼
┌──────────────────────┐
│ POST /webhooks/github│
└──────────────────────┘
       │
       ▼
┌──────────────────────┐
│ GitHubSignatureValidator │ (HMAC SHA-256 verification)
└──────────────────────┘
       │
       ▼
┌──────────────────────┐
│ GitHubWebhookProfile │ (Event filtering: push, pull_request, ping)
└──────────────────────┘
       │
       ▼
┌──────────────────────┐
│ ProcessGitHubWebhookJob │ (Event routing)
└──────────────────────┘
       │
       ▼
┌──────────────────────┐
│ ProcessGitHubPushJob │ (Queued commit processing)
└──────────────────────┘
       │
       ▼
┌──────────────────────┐
│ ProcessPushEventAction │ (CQRS orchestrator)
└──────────────────────┘
       │
       ├── FindOrCreateRepositoryAction
       ├── StoreCommitAction (per commit)
       └── EnrichCommitAction (per commit)
```

---

### 2. DTOs (Data Transfer Objects)

#### PushEventData
- **File:** `app/DTOs/GitHub/PushEventData.php`
- Encapsulates GitHub push event webhook payload
- Properties:
  - `ref` - Git reference (e.g., "refs/heads/main")
  - `before`, `after` - Commit SHAs before/after push
  - `repository` - RepositoryData DTO
  - `pusherName`, `pusherEmail` - Git pusher details
  - `senderLogin`, `senderId` - GitHub sender details
  - `commits` - Array of CommitData DTOs
  - `created`, `deleted`, `forced` - Push event flags
- Factory method: `fromWebhook(array $payload)`
- Helper methods:
  - `getBranch()` - Extract branch from ref
  - `isDefaultBranch()` - Check if push is to default branch
  - `hasCommits()` - Check if push has commits to process
  - `getDistinctCommits()` - Filter to unique commits only

#### RepositoryData
- **File:** `app/DTOs/GitHub/RepositoryData.php`
- Encapsulates repository data from webhook payload
- Properties:
  - `id` - GitHub repository ID
  - `name`, `fullName` - Repository names
  - `description` - Repository description
  - `defaultBranch` - Default branch name
  - `language` - Primary language
  - `isPrivate` - Visibility flag
- Factory method: `fromWebhook(array $payload)`
- Conversion method: `toArray()` - For database storage

#### CommitData
- **File:** `app/DTOs/GitHub/CommitData.php`
- Encapsulates commit data from webhook or API
- Properties:
  - `sha` - Commit SHA
  - `message` - Commit message
  - `authorName`, `authorEmail` - Git author
  - `committedAt` - Commit timestamp
  - `additions`, `deletions`, `filesChanged` - Stats
  - `files` - Array of file changes
  - `url` - GitHub commit URL
  - `distinct` - Whether commit is new (not previously pushed)
- Factory methods:
  - `fromWebhook(array $payload)` - From push event
  - `fromApi(array $payload)` - From GitHub API (full details)
- Conversion method: `toArray()` - For database storage

---

### 3. Queued Jobs

#### ProcessGitHubWebhookJob
- **File:** `app/Jobs/ProcessGitHubWebhookJob.php`
- Extends Spatie's `ProcessWebhookJob`
- Routes events by type:
  - `ping` - Logs webhook verification
  - `push` - Dispatches ProcessGitHubPushJob
  - `pull_request` - Logs PR activity (future implementation)
- Extracts event type from `X-GitHub-Event` header

#### ProcessGitHubPushJob
- **File:** `app/Jobs/ProcessGitHubPushJob.php`
- Implements `ShouldQueue` for async processing
- Configuration:
  - `$tries = 3` - Retry on failure
  - `$backoff = 60` - 60 second retry delay
- Processing:
  1. Creates PushEventData from payload
  2. Executes ProcessPushEventAction
  3. Logs results (stored commit count)
- Error handling:
  - `failed()` method logs errors with repository context

---

### 4. CQRS Actions

#### ProcessPushEventAction
- **File:** `app/Actions/GitHub/ProcessPushEventAction.php`
- Orchestrates the full push event processing
- Steps:
  1. Find user by GitHub sender ID
  2. Find or create repository
  3. Skip if repository is inactive
  4. Skip if branch deletion or no commits
  5. For each distinct commit:
     - Optionally fetch full commit data from API
     - Store commit via StoreCommitAction
     - Enrich commit via EnrichCommitAction
  6. Update repository `last_sync_at`
- Returns: Collection of stored commits
- Optional: `$fetchFullCommitData` flag for API enrichment

#### FindOrCreateRepositoryAction
- **File:** `app/Actions/GitHub/FindOrCreateRepositoryAction.php`
- Uses `updateOrCreate` for idempotency
- Matches on: `user_id` + `github_id`
- Updates all repository fields from RepositoryData

#### StoreCommitAction
- **File:** `app/Actions/GitHub/StoreCommitAction.php`
- Idempotent: Skips if commit SHA already exists
- Creates commit record from CommitData
- Note: Enrichment handled separately by EnrichCommitAction

---

### 5. Configuration

#### Webhook Client Config
- **File:** `config/webhook-client.php`
- GitHub-specific configuration:
  - Name: `github`
  - Signing secret: `GITHUB_WEBHOOK_SECRET` env var
  - Signature header: `X-Hub-Signature-256`
  - Signature validator: `GitHubSignatureValidator`
  - Webhook profile: `GitHubWebhookProfile`
  - Process job: `ProcessGitHubWebhookJob`
  - Stored headers: `X-GitHub-Event`, `X-GitHub-Delivery`, `X-Hub-Signature-256`
- Retention: 30 days
- Clean URL: No unique token in route name

#### Routes
- **File:** `routes/webhooks.php`
- Endpoint: `POST /webhooks/github`
- Named route: `webhooks.github`
- CSRF excluded in `bootstrap/app.php`

---

### 6. Tests

#### Feature Tests
- **File:** `tests/Feature/Actions/GitHub/ProcessPushEventActionTest.php`
  - Full push event processing
  - Unknown user handling
  - Inactive repository skipping
  - Branch deletion handling
  - Distinct commit filtering
  - Commit enrichment integration

- **File:** `tests/Feature/Actions/GitHub/FindOrCreateRepositoryActionTest.php`
  - Repository creation
  - Repository update on existing
  - Idempotency tests

- **File:** `tests/Feature/Actions/GitHub/StoreCommitActionTest.php`
  - Commit creation
  - Duplicate SHA skipping
  - Data mapping tests

- **File:** `tests/Feature/Jobs/ProcessGitHubPushJobTest.php`
  - Job dispatch and execution
  - Retry configuration
  - Error handling

#### Unit Tests
- **File:** `tests/Unit/DTOs/GitHub/PushEventDataTest.php`
  - Factory method tests
  - Helper method tests
  - Branch extraction
  - Distinct commit filtering

- **File:** `tests/Unit/DTOs/GitHub/RepositoryDataTest.php`
  - Factory method tests
  - Array conversion

- **File:** `tests/Unit/DTOs/GitHub/CommitDataTest.php`
  - Factory method tests (webhook and API)
  - Array conversion
  - Optional fields handling

---

## Design Decisions

### Spatie Webhook Client
- **Decision:** Use `spatie/laravel-webhook-client` over DIY implementation
- **Reason:** Battle-tested signature validation, job dispatching, webhook call storage
- **Benefits:**
  - Secure HMAC SHA-256 verification
  - Automatic webhook call logging
  - Clean job-based processing
  - Built-in retry logic

### Event-Driven Architecture
- **Decision:** Process webhook events through queued jobs
- **Reason:** Webhook endpoints must respond quickly (< 10s GitHub timeout)
- **Implementation:**
  - ProcessGitHubWebhookJob routes events (sync)
  - ProcessGitHubPushJob processes commits (async/queued)

### DTO Factory Methods
- **Decision:** DTOs have `fromWebhook()` and `fromApi()` factory methods
- **Reason:** Webhook payloads differ from API responses
- **Benefit:** Single DTO handles both sources with appropriate parsing

### Distinct Commit Filtering
- **Decision:** Only process commits where `distinct = true`
- **Reason:** Force pushes resend previously pushed commits
- **Implementation:** `PushEventData::getDistinctCommits()` filters array

### Optional API Enrichment
- **Decision:** Optionally fetch full commit data from GitHub API
- **Reason:** Webhook payload has limited commit stats
- **Implementation:** `$fetchFullCommitData` flag on ProcessPushEventAction
- **Trade-off:** API calls vs richer data (lines added/deleted per file)

### User Lookup by Sender ID
- **Decision:** Look up GitPulse user by `sender.id` from webhook
- **Reason:** Sender is the person who triggered the push (may differ from commit author)
- **Implication:** Only tracks commits for registered GitPulse users

---

## Files Created

### DTOs
- `app/DTOs/GitHub/PushEventData.php`
- `app/DTOs/GitHub/RepositoryData.php`
- `app/DTOs/GitHub/CommitData.php`

### Jobs
- `app/Jobs/ProcessGitHubPushJob.php`

### Actions
- `app/Actions/GitHub/ProcessPushEventAction.php`
- `app/Actions/GitHub/FindOrCreateRepositoryAction.php`
- `app/Actions/GitHub/StoreCommitAction.php`

### Tests
- `tests/Feature/Actions/GitHub/ProcessPushEventActionTest.php`
- `tests/Feature/Actions/GitHub/FindOrCreateRepositoryActionTest.php`
- `tests/Feature/Actions/GitHub/StoreCommitActionTest.php`
- `tests/Feature/Jobs/ProcessGitHubPushJobTest.php`
- `tests/Unit/DTOs/GitHub/PushEventDataTest.php`
- `tests/Unit/DTOs/GitHub/RepositoryDataTest.php`
- `tests/Unit/DTOs/GitHub/CommitDataTest.php`

---

## Quality Gates

| Check | Status |
|-------|--------|
| PHPStan Level 8 | Pass |
| Pint Code Style | Pass |
| Pest Tests | Pass |
| Architecture Tests | Pass |

---

## Environment Variables Required

```env
# GitHub Webhooks
GITHUB_WEBHOOK_SECRET=your_webhook_secret
```

---

## Next Steps (Sprint 4)

1. **Commit Documentation Engine**
   - ParseCommitMessageAction for conventional commits
   - CategorizeCommitAction for NLP-based categorization
   - CalculateImpactScoreAction for productivity metrics
   - EnrichCommitAction to orchestrate parsing and scoring

2. **Impact Score Calculation**
   - Lines changed factor
   - Files touched factor
   - Commit type weight
   - Merge status factor
   - External references factor
   - Focus time factor

---

## Lessons Learned

Sprint 3 lessons are captured in Sprint 4 section of `docs/lessons/LESSONS.md`:
- Sprint 4 started before Sprint 3 was fully complete (created TODO markers)
- Integration points between webhook processing and commit enrichment were designed but required Sprint 4 for full implementation

Key learnings:
- Webhook payloads differ significantly from API responses - DTOs with separate factory methods handle this cleanly
- Queued jobs are essential for webhook processing - GitHub has a 10s timeout
- Idempotency is critical - duplicate webhooks can occur, StoreCommitAction skips existing SHAs
- Sender vs Author distinction matters - webhook sender may differ from commit author
