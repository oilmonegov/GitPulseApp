# F10: Integrations

## Feature Summary

Connect GitPulse with external tools for notifications, issue tracking, and workflow automation. Extends the platform's utility within existing developer workflows.

## Priority

**P1 (Post-MVP)** - Enhanced feature for Phase 3

## Supported Integrations

| Integration | Type | Description |
|-------------|------|-------------|
| Slack | Notification | Daily/weekly summaries to channels |
| Linear | Issue Tracking | Link commits to Linear issues |
| Jira | Issue Tracking | Link commits to Jira tickets |
| Notion | Documentation | Auto-update work log databases |
| Email | Notification | Weekly digest via email |
| Zapier | Automation | Webhook triggers for custom workflows |

## Acceptance Criteria

- [ ] Users can connect/disconnect integrations
- [ ] Slack notifications are delivered reliably
- [ ] Issue references are linked correctly
- [ ] Notion databases are updated accurately
- [ ] Email digests are sent on schedule
- [ ] Zapier webhooks fire on configured events

## Stepwise Refinement

### Level 0: High-Level Flow

```
Configure Integration → Authenticate → Set Preferences → Trigger Events → Deliver Payload
```

### Level 1: Integration Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                   Integration System                         │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Integration Manager                                │    │
│  │  - List available integrations                      │    │
│  │  - Connect/disconnect                               │    │
│  │  - Store credentials (encrypted)                    │    │
│  │  - Test connection                                  │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Event Dispatcher                                   │    │
│  │  - Listen to GitPulse events                        │    │
│  │  - Route to configured integrations                 │    │
│  │  - Handle failures with retry                       │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌───────────────┬───────────────┬───────────────────┐     │
│  │ SlackService  │ LinearService │ NotionService     │     │
│  │               │               │                   │     │
│  │ - sendMessage │ - linkIssue   │ - createPage      │     │
│  │ - postSummary │ - getIssue    │ - updateDatabase  │     │
│  └───────────────┴───────────────┴───────────────────┘     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Slack Integration

### Features
- Daily commit summary to channel
- Weekly report delivery
- Real-time commit notifications (optional)
- Custom channel selection

### Configuration

```php
// User integration settings
{
    "slack": {
        "enabled": true,
        "webhook_url": "https://hooks.slack.com/...",
        "channel": "#engineering",
        "daily_summary": true,
        "daily_summary_time": "17:00",
        "weekly_report": true,
        "realtime_commits": false
    }
}
```

### Implementation

```php
<?php

namespace App\Services\Integrations;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class SlackService
{
    public function sendDailySummary(User $user, array $metrics): void
    {
        $config = $user->getIntegrationConfig('slack');

        if (! $config['enabled'] || ! $config['daily_summary']) {
            return;
        }

        $blocks = $this->buildSummaryBlocks($user, $metrics);

        Http::post($config['webhook_url'], [
            'blocks' => $blocks,
        ]);
    }

    private function buildSummaryBlocks(User $user, array $metrics): array
    {
        return [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => "Daily Summary for {$user->name}",
                ],
            ],
            [
                'type' => 'section',
                'fields' => [
                    ['type' => 'mrkdwn', 'text' => "*Commits:* {$metrics['total_commits']}"],
                    ['type' => 'mrkdwn', 'text' => "*Impact:* {$metrics['total_impact']}"],
                    ['type' => 'mrkdwn', 'text' => "*Features:* {$metrics['features']}"],
                    ['type' => 'mrkdwn', 'text' => "*Fixes:* {$metrics['fixes']}"],
                ],
            ],
        ];
    }
}
```

---

## Linear/Jira Integration

### Features
- Auto-link commits to issues via reference parsing
- Display issue status in GitPulse
- Aggregate commits by issue/ticket

### Reference Patterns

```php
// Already implemented in F2: ParseCommitMessage
private const REF_PATTERNS = [
    'github' => '/#(\d+)/',
    'jira' => '/([A-Z][A-Z0-9]+-\d+)/',
    'linear' => '/([A-Z]+-\d+)/',
];
```

### Issue Linking Service

```php
<?php

namespace App\Services\Integrations;

use App\Models\Commit;

class IssueLinkingService
{
    public function linkCommitToIssues(Commit $commit): void
    {
        $refs = $commit->external_refs ?? [];

        foreach ($refs as $ref) {
            if ($this->isJiraRef($ref)) {
                $this->linkToJira($commit, $ref);
            } elseif ($this->isLinearRef($ref)) {
                $this->linkToLinear($commit, $ref);
            }
        }
    }

    private function isJiraRef(string $ref): bool
    {
        return preg_match('/^[A-Z][A-Z0-9]+-\d+$/', $ref);
    }

    private function isLinearRef(string $ref): bool
    {
        return preg_match('/^[A-Z]+-\d+$/', $ref) && strlen($ref) <= 10;
    }
}
```

---

## Notion Integration

### Features
- Auto-create daily work log entries
- Update weekly summary database
- Sync project/repository list

### Configuration

```php
{
    "notion": {
        "enabled": true,
        "access_token": "secret_...",
        "daily_log_database_id": "abc123...",
        "weekly_summary_database_id": "def456...",
        "auto_log_commits": true
    }
}
```

### Implementation

```php
<?php

namespace App\Services\Integrations;

use App\Models\User;
use Illuminate\Support\Facades\Http;

class NotionService
{
    private const API_URL = 'https://api.notion.com/v1';

    public function createDailyLogEntry(User $user, array $metrics): void
    {
        $config = $user->getIntegrationConfig('notion');

        Http::withToken($config['access_token'])
            ->withHeaders(['Notion-Version' => '2022-06-28'])
            ->post(self::API_URL . '/pages', [
                'parent' => ['database_id' => $config['daily_log_database_id']],
                'properties' => [
                    'Date' => ['date' => ['start' => now()->toDateString()]],
                    'Commits' => ['number' => $metrics['total_commits']],
                    'Impact' => ['number' => $metrics['total_impact']],
                    'Features' => ['number' => $metrics['features']],
                    'Bugs Fixed' => ['number' => $metrics['fixes']],
                ],
            ]);
    }
}
```

---

## Email Digest

### Features
- Weekly summary email
- Configurable send day/time
- HTML and plain text versions

### Implementation

```php
<?php

namespace App\Notifications;

use App\Models\WeeklyReport;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyDigest extends Notification
{
    public function __construct(
        public WeeklyReport $report,
    ) {}

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Your Weekly GitPulse Summary")
            ->markdown('emails.weekly-digest', [
                'report' => $this->report,
                'user' => $notifiable,
            ]);
    }
}
```

---

## Zapier Integration

### Features
- Webhook triggers for custom automation
- Supported events: commit processed, report generated, goal completed

### Webhook Payload

```json
{
    "event": "commit.processed",
    "timestamp": "2026-01-16T10:30:00Z",
    "user_id": 123,
    "data": {
        "commit_sha": "abc123",
        "message": "feat: add login",
        "repository": "myapp",
        "impact_score": 7.5,
        "commit_type": "feat"
    }
}
```

---

## Data Model

### UserIntegration Model

```php
Schema::create('user_integrations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('provider'); // slack, linear, jira, notion, zapier
    $table->json('config'); // Encrypted settings
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'provider']);
});
```

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Models/UserIntegration.php` | Integration model |
| `app/Services/Integrations/SlackService.php` | Slack API |
| `app/Services/Integrations/NotionService.php` | Notion API |
| `app/Services/Integrations/LinearService.php` | Linear API |
| `app/Services/Integrations/JiraService.php` | Jira API |
| `app/Services/Integrations/ZapierService.php` | Zapier webhooks |
| `app/Jobs/SendSlackSummaryJob.php` | Daily Slack job |
| `app/Jobs/SyncNotionLogJob.php` | Notion sync job |
| `app/Http/Controllers/IntegrationController.php` | Integration management |
| `resources/js/Pages/Settings/Integrations.vue` | Settings page |
