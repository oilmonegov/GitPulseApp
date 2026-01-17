# Product Requirements Document
## GitPulse: Developer Productivity Analytics Platform

**Document Version:** 2.4  
**Author:** Product Team  
**Date:** January 16, 2026  
**Status:** Final Draft  
**Tech Stack:** Laravel 12 + Vue.js 3.5 + Inertia.js 2.0 + Tailwind CSS 4 + MySQL + Redis + Reverb

---

## Executive Summary

GitPulse is a web application that transforms Git commit activity into actionable productivity insights. By listening to GitHub webhooks (commits, pushes, pull requests), GitPulse automatically documents developer work, generates comparative analytics, and produces intelligent weekly reports suitable for stakeholder communication.

The platform addresses a universal pain point: the disconnect between actual engineering work and the time spent reporting on that work. By using commits as the single source of truth, GitPulse eliminates manual status updates while providing deeper insights than traditional time-tracking tools.

**Target Users:** Individual developers, tech leads, engineering managers, and CTOs who need visibility into development velocity and patterns.

---

## Problem Statement

### Current Pain Points

**For Individual Contributors:**
- Manually reconstructing weekly accomplishments from memory is time-consuming and inaccurate
- No objective way to measure personal productivity trends over time
- Difficult to articulate impact to stakeholders without underselling or overselling

**For Engineering Leaders:**
- Status meetings consume productive coding time
- Lack of standardized metrics to compare performance across time periods
- No early warning system for productivity dips or burnout indicators

### The Deeper Problem

Commit frequency alone is a poor proxy for productivity. A developer who ships one well-architected feature may create more value than someone with 50 trivial commits. Current tools either ignore this nuance (counting raw commits) or require extensive manual input (time tracking).

GitPulse solves this by combining automated data collection with intelligent analysis that weighs commit impact, categorizes work types, and surfaces patterns humans would miss.

---

## Goals & Success Metrics

### Primary Goals

| Goal | Description | Success Metric |
|------|-------------|----------------|
| Eliminate manual reporting | Auto-generate weekly reports from commits | 90% reduction in time spent on status updates |
| Surface productivity patterns | Identify high and low performance periods | Users identify 3+ actionable insights within first month |
| Enable data-driven improvement | Provide recommendations for optimization | 15% improvement in self-reported productivity after 8 weeks |

### Secondary Goals

| Goal | Description | Success Metric |
|------|-------------|----------------|
| Reduce meeting overhead | Replace sync meetings with async reports | 2+ hours/week reclaimed per user |
| Support career growth | Document accomplishments for reviews | 100% of users export annual summary |

### Anti-Goals (Explicitly Out of Scope)

- **Surveillance tool:** GitPulse is designed for self-improvement, not managerial monitoring
- **Code quality analysis:** We track activity patterns, not code quality (that's for linters and reviewers)
- **Project management replacement:** We complement tools like Jira/Linear, not replace them

---

## User Personas

### Persona 1: The Accountable IC (Primary)

**Name:** Emeka, Senior Software Engineer  
**Context:** Works across 3-4 repositories, reports to engineering manager, wants to demonstrate impact during performance reviews.

**Goals:**
- Automatically track daily accomplishments without manual logging
- Generate professional weekly reports for manager
- Understand personal productivity patterns to optimize work habits

**Frustrations:**
- Forgets what was accomplished by Friday
- Status updates feel like busywork
- No way to prove impact beyond "I worked hard"

### Persona 2: The Data-Driven Lead

**Name:** Amara, Tech Lead  
**Context:** Manages 5 engineers, needs visibility without micromanaging, accountable for team velocity.

**Goals:**
- Aggregate team activity without 1:1 interrogation
- Identify team members who may need support (early burnout detection)
- Produce stakeholder reports with minimal effort

**Frustrations:**
- Status meetings take too much time
- Manual report compilation is error-prone
- No objective baseline for "normal" productivity

### Persona 3: The Startup Founder

**Name:** Tunde, Technical Co-founder  
**Context:** Wears multiple hats, needs to report to investors/board, cares about team culture.

**Goals:**
- Dashboard for investor updates showing engineering velocity
- Week-over-week trend data for board meetings
- Ensure tool doesn't create toxic productivity culture

---

## Feature Requirements

### P0: Core Features (MVP)

#### F1: GitHub Webhook Integration

**Description:** Receive and process GitHub webhook events for commits, pushes, and pull requests.

**Requirements:**
- Support for multiple repositories per user
- Support for multiple GitHub organizations
- Secure webhook validation (signature verification)
- Event types: push, pull_request (opened, merged, closed)
- Handle rate limiting gracefully

**Acceptance Criteria:**
- Webhook receives commit within 5 seconds of push
- All commit metadata captured (message, author, timestamp, files changed, additions/deletions)
- Failed webhooks retry with exponential backoff

#### F2: Commit Documentation Engine

**Description:** Parse commit messages and transform them into human-readable work documentation.

**Requirements:**
- Support Conventional Commits format (feat:, fix:, chore:, docs:, refactor:, test:, style:, perf:)
- Auto-categorize commits without conventional format using NLP
- Extract ticket/issue references (#123, JIRA-456)
- Link commits to projects/repositories

**Commit Impact Scoring Algorithm:**

| Factor | Weight | Description |
|--------|--------|-------------|
| Lines changed | 20% | Normalized by project average |
| Files touched | 15% | Cross-cutting changes weighted higher |
| Commit type | 25% | feat > fix > refactor/perf > test > docs > style/chore |
| Merge commit | 20% | PR merges indicate completed work |
| External references | 10% | Linked issues add context |
| Time of day | 10% | Work during focus hours weighted slightly higher |

#### F3: Daily Dashboard

**Description:** Real-time view of today's activity with historical comparison.

**Requirements:**
- Today's commit count with hour-by-hour breakdown
- Commit list with message, repo, timestamp, impact score
- Comparison widget: "Today vs Yesterday" / "Today vs Same Day Last Week"
- Project breakdown (pie chart of repos)
- Streak indicator (consecutive days with commits)
- **Real-time updates via Laravel Reverb WebSockets**

**UI Components:**
- Metric cards: Total commits, Impact score, Active repos, Hours active
- Timeline visualization of commit distribution
- Quick filters: By repo, by type, by time range
- Live commit feed with animated entry (Reverb-powered)

#### F4: Weekly Report Generator

**Description:** Automated generation of stakeholder-ready weekly summary.

**Requirements:**
- Auto-generate every Friday at configurable time
- Sections: Summary stats, Key accomplishments, Projects touched, Trends
- Export formats: PDF, Markdown, HTML email
- Editable before sending (add context, remove items)
- Template customization (formal vs casual tone)

**Report Structure:**
```
Weekly Engineering Report: [Date Range]

SUMMARY
- Total commits: X (+Y% vs last week)
- Features shipped: X
- Bugs fixed: X
- Repos contributed to: X

KEY ACCOMPLISHMENTS
1. [Auto-generated from high-impact commits]
2. [Grouped by project/feature]
3. [Includes PR merges and issue closures]

TRENDS
- Most productive day: [Day]
- Focus areas: [Top 3 repos by activity]
- Week-over-week change: [+/-X%]

NEXT WEEK PREVIEW
[Optional: User-added goals]
```

#### F5: Comparative Analytics

**Description:** Day-over-day, week-over-week, and month-over-month comparisons.

**Requirements:**
- Trend lines for commits, impact score, active hours
- Anomaly detection (significantly above/below average)
- Best/worst period identification with date ranges
- Configurable comparison periods

**Visualization:**
- Line charts with dual-axis (commits + impact)
- Heat map calendar (GitHub contribution graph style)
- Sparklines for quick trend indication

### P1: Enhanced Features (Post-MVP)

#### F6: AI-Powered Performance Insights

**Description:** Intelligent analysis of productivity patterns with actionable recommendations.

**Requirements:**
- Identify high-productivity conditions (day of week, time of day, repo focus)
- Detect potential burnout indicators (declining trend, erratic patterns)
- Generate natural language insights

**Insight Examples:**
- "You ship 40% more features on Tuesdays. Consider protecting Tuesday mornings for deep work."
- "Your productivity dips after 3+ days of context switching between repos. Try batching similar work."
- "Last month's high output correlated with fewer meetings (per calendar data). Current month shows 30% more meeting time."

#### F7: Calendar Integration (Google/Outlook)

**Description:** Correlate meeting time with coding output.

**Requirements:**
- OAuth integration with Google Calendar and Outlook
- Calculate: Meeting hours vs coding hours ratio
- Identify meeting-free blocks and their productivity
- Optional: Auto-block focus time based on patterns

**Implementation Note:** Google Calendar OAuth requires the `socialiteproviders/google` community package, as Laravel Socialite doesn't include a first-party Google Calendar driver. Microsoft Outlook uses Microsoft Graph API via `socialiteproviders/microsoft`.

#### F8: Team Dashboard (for Leads)

**Description:** Aggregate view across team members (opt-in only).

**Requirements:**
- Team-level metrics (total commits, PRs merged, avg impact)
- Individual contribution breakdown (anonymizable)
- Trend comparison across team members
- No individual surveillance: Focus on team health, not ranking

#### F9: Goal Setting & Tracking

**Description:** User-defined targets with progress tracking.

**Requirements:**
- Set weekly/monthly commit targets
- Define focus areas (e.g., "Ship 2 features this sprint")
- Progress bar toward goals
- Notification when falling behind pace

#### F10: Integrations

**Supported Integrations:**
- **Slack**: Outgoing webhook notifications via Laravel HTTP client
- **Linear/Jira**: Link commits to issues via external refs parsing
- **Notion**: OAuth + API for auto-updating work log databases
- **Email**: Weekly digest via Laravel Notifications (Postmark/Mailgun)
- **Calendar**: Google Calendar OAuth via `socialiteproviders/google`

**Note:** GitHub webhooks are handled via Spatie Laravel Webhook Client with automatic signature verification, request logging, and retry handling.

### P2: Future Considerations

- GitLab and Bitbucket support
- VS Code extension for in-IDE insights
- Mobile app for on-the-go check-ins
- API for custom integrations
- Self-hosted/on-premise option for enterprise

---

## Technical Architecture

### System Overview

```
┌─────────────────────────────────────────────────────────────────────┐
│                      GITPULSE ARCHITECTURE                          │
│                   Laravel 12.x + Vue.js + Inertia.js                │
├─────────────────────────────────────────────────────────────────────┤
│                                                                     │
│  ┌──────────┐     ┌──────────────────┐     ┌──────────────────┐    │
│  │  GitHub  │────▶│  Spatie Webhook  │────▶│   Redis Queue    │    │
│  │ Webhooks │     │     Client       │     │  (Laravel Horizon)│    │
│  └──────────┘     └──────────────────┘     └────────┬─────────┘    │
│                                                      │              │
│                                                      ▼              │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                 Laravel Job Processing                        │  │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐  │  │
│  │  │ProcessGitHubPush│ │ CalculateImpact│  │ GenerateInsight│  │  │
│  │  │     Job        │  │      Job       │  │   Job (LLM)    │  │  │
│  │  └────────────────┘  └────────────────┘  └────────────────┘  │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                              │                                      │
│                              ▼                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                      Data Layer                               │  │
│  │  ┌─────────────────────┐      ┌───────────────────────────┐  │  │
│  │  │       MySQL 8.x     │      │         Redis             │  │  │
│  │  │  (Core Data +       │      │  - Queue Management       │  │  │
│  │  │   Partitioned       │      │  - Metrics Cache          │  │  │
│  │  │   Time-Series)      │      │  - Real-time Counters     │  │  │
│  │  └─────────────────────┘      └───────────────────────────┘  │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                              │                                      │
│                              ▼                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                 Laravel Application Layer                     │  │
│  │  ┌────────────────┐  ┌────────────────┐  ┌────────────────┐  │  │
│  │  │  Inertia.js    │  │  Laravel       │  │  Laravel       │  │  │
│  │  │  Controllers   │  │  Reverb (WS)   │  │  Fortify Auth  │  │  │
│  │  └────────────────┘  └────────────────┘  └────────────────┘  │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                              │                                      │
│                              ▼                                      │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │            Frontend (Vue.js 3 + TypeScript + Inertia.js)      │  │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐ │  │
│  │  │Dashboard │  │ Reports  │  │ Settings │  │   Insights   │ │  │
│  │  │  .vue    │  │  .vue    │  │  .vue    │  │     .vue     │ │  │
│  │  └──────────┘  └──────────┘  └──────────┘  └──────────────┘ │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │                    Infrastructure                             │  │
│  │         Laravel Forge + DigitalOcean Droplets                 │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────────────────┐  │  │
│  │  │ App Server │  │  Worker    │  │  Managed MySQL         │  │  │
│  │  │  (Nginx)   │  │ (Horizon)  │  │  + Redis Cluster       │  │  │
│  │  └────────────┘  └────────────┘  └────────────────────────┘  │  │
│  │  ┌────────────────────────────┐                              │  │
│  │  │  Reverb WebSocket Server   │                              │  │
│  │  │  (Supervisor managed)      │                              │  │
│  │  └────────────────────────────┘                              │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

### Technology Stack

| Layer | Technology | Rationale |
|-------|------------|-----------|
| **Frontend** | Vue.js 3.5 + TypeScript | Reactive, type-safe, excellent DX with Composition API |
| **SPA Bridge** | Inertia.js 2.0 | Seamless Laravel-Vue integration without REST API overhead |
| **UI Styling** | Tailwind CSS 4.0 | CSS-first config, native cascade layers, OKLCH colors |
| **Charts** | Chart.js + vue-chartjs | Proven library with Vue 3 bindings |
| **Backend** | Laravel 12.x (PHP 8.3+) | Robust ecosystem, excellent queue system, rapid development |
| **Database** | MySQL 8.x | Reliable, partitioning support for time-series data |
| **Cache/Queue** | Redis 7.x | Queue backend, caching, real-time counters via Horizon |
| **Time-Series** | MySQL Partitioned Tables + Redis | Partitioned by month for efficient range queries + Redis for hot metrics |
| **AI/LLM** | Claude API (Anthropic SDK) | Natural language insights generation |
| **Auth** | Laravel Fortify | First-party auth scaffolding with 2FA support |
| **GitHub OAuth** | Laravel Socialite | Official OAuth integration for GitHub |
| **Webhooks** | Spatie Laravel Webhook Client | Battle-tested webhook handling with signature verification |
| **Real-time** | Laravel Reverb | First-party WebSocket server for live dashboard updates |
| **Testing** | PestPHP 3.0 | Elegant, expressive testing syntax with parallel execution |
| **Code Style** | Laravel Pint | PSR-12 + Laravel preset code formatting |
| **Static Analysis** | Larastan 3.0 | PHPStan wrapper optimized for Laravel (Level 8) |
| **Refactoring** | Rector 2.0 | Automated code upgrades and quality improvements |
| **Hosting** | Laravel Forge + DigitalOcean | Zero-DevOps deployment, SSL, auto-backups |
| **Worker Management** | Laravel Horizon | Redis queue monitoring and management |
| **PDF Generation** | Laravel DomPDF | Weekly report export |

### Laravel Package Dependencies

```json
{
    "require": {
        "php": "^8.3",
        "laravel/framework": "^12.0",
        "laravel/fortify": "^1.21",
        "laravel/reverb": "^1.0",
        "laravel/socialite": "^5.12",
        "laravel/horizon": "^5.24",
        "inertiajs/inertia-laravel": "^2.0",
        "barryvdh/laravel-dompdf": "^3.0",
        "anthropic/anthropic-php": "^1.0",
        "spatie/laravel-data": "^4.0",
        "spatie/laravel-query-builder": "^6.0",
        "spatie/laravel-webhook-client": "^3.4"
    },
    "require-dev": {
        "laravel/pint": "^1.18",
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0",
        "pestphp/pest-plugin-faker": "^3.0",
        "pestphp/pest-plugin-type-coverage": "^3.0",
        "larastan/larastan": "^3.0",
        "rector/rector": "^2.0",
        "nunomaduro/collision": "^8.0"
    }
}
```

### Code Quality & Testing Stack

| Tool | Purpose | Command |
|------|---------|---------|
| **PestPHP 3.0** | Testing framework | `php artisan test` |
| **Laravel Pint** | Code style fixer (PSR-12 + Laravel preset) | `./vendor/bin/pint` |
| **Larastan 3.0** | Static analysis (PHPStan for Laravel) | `./vendor/bin/phpstan analyse` |
| **Rector 2.0** | Automated refactoring & code upgrades | `./vendor/bin/rector process` |

### Pint Configuration (`pint.json`)

```json
{
    "preset": "laravel",
    "rules": {
        "blank_line_before_statement": {
            "statements": ["return", "throw", "try", "if", "foreach", "while"]
        },
        "concat_space": {
            "spacing": "one"
        },
        "method_argument_space": {
            "on_multiline": "ensure_fully_multiline"
        },
        "not_operator_with_successor_space": true,
        "ordered_imports": {
            "sort_algorithm": "alpha"
        },
        "phpdoc_order": true,
        "phpdoc_separation": true,
        "single_line_empty_body": true,
        "trailing_comma_in_multiline": {
            "elements": ["arrays", "arguments", "parameters"]
        },
        "types_spaces": {
            "space": "none"
        }
    },
    "exclude": [
        "bootstrap",
        "storage",
        "vendor"
    ]
}
```

### PHPStan / Larastan Configuration (`phpstan.neon`)

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app
        - config
        - database
        - routes
        - tests

    level: 8

    ignoreErrors:
        # Inertia lazy props use closures
        - '#Parameter \#2 .* of method Inertia\\ResponseFactory::render\(\) expects#'

    excludePaths:
        - app/Http/Middleware/TrustProxies.php
        - vendor

    checkMissingIterableValueType: false
    checkGenericClassInNonGenericObjectType: false
    reportUnmatchedIgnoredErrors: false

    # Type coverage requirements
    treatPhpDocTypesAsCertain: false
```

### Rector Configuration (`rector.php`)

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Laravel\Set\LaravelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPromotedPropertyRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/bootstrap',
        __DIR__ . '/storage',
        __DIR__ . '/vendor',
    ])
    ->withPhpSets(php83: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        earlyReturn: true,
    )
    ->withSets([
        // Use the latest available Laravel set
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
        TypedPropertyFromAssignsRector::class,
        RemoveUnusedPromotedPropertyRector::class,
    ]);
```

### PestPHP Configuration (`tests/Pest.php`)

```php
<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(TestCase::class)->in('Feature', 'Unit');
uses(LazilyRefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeValidUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createUser(array $attributes = []): \App\Models\User
{
    return \App\Models\User::factory()->create($attributes);
}

function createAuthenticatedUser(array $attributes = []): \App\Models\User
{
    $user = createUser($attributes);
    test()->actingAs($user);

    return $user;
}
```

### Example Test Files

**Feature Test: Dashboard** (`tests/Feature/DashboardTest.php`)
```php
<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Commit;
use App\Models\Repository;

beforeEach(function () {
    $this->user = createAuthenticatedUser([
        'github_username' => 'testuser',
    ]);
});

describe('Dashboard', function () {
    it('displays the dashboard for authenticated users', function () {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('todayMetrics')
            ->has('yesterdayMetrics')
            ->has('recentCommits')
            ->has('weeklyTrend')
            ->has('streak')
        );
    });

    it('shows today\'s commits on the dashboard', function () {
        $repository = Repository::factory()
            ->for($this->user)
            ->create();

        $commits = Commit::factory()
            ->count(5)
            ->for($repository)
            ->for($this->user)
            ->today()
            ->create();

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('todayMetrics.total_commits', 5)
            ->has('recentCommits', 5)
        );
    });

    it('redirects guests to login', function () {
        auth()->logout();

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    });
});

describe('Dashboard Metrics Comparison', function () {
    it('calculates correct day-over-day change', function () {
        $repository = Repository::factory()
            ->for($this->user)
            ->create();

        // Yesterday: 10 commits
        Commit::factory()
            ->count(10)
            ->for($repository)
            ->for($this->user)
            ->yesterday()
            ->create();

        // Today: 15 commits
        Commit::factory()
            ->count(15)
            ->for($repository)
            ->for($this->user)
            ->today()
            ->create();

        $response = $this->get(route('dashboard'));

        $response->assertInertia(fn ($page) => $page
            ->where('todayMetrics.total_commits', 15)
            ->where('yesterdayMetrics.total_commits', 10)
        );
    });
});
```

**Unit Test: Impact Score Calculation** (`tests/Unit/Actions/CalculateImpactScoreTest.php`)
```php
<?php

declare(strict_types=1);

use App\Actions\Commits\CalculateImpactScore;
use App\Models\Commit;
use App\Models\Repository;
use App\Enums\CommitType;

describe('CalculateImpactScore', function () {
    it('scores feature commits higher than chore commits', function () {
        $repository = Repository::factory()->create();
        $action = new CalculateImpactScore();

        $featureCommit = Commit::factory()
            ->for($repository)
            ->create([
                'commit_type' => CommitType::FEAT,
                'additions' => 100,
                'deletions' => 20,
                'files_changed' => 5,
            ]);

        $choreCommit = Commit::factory()
            ->for($repository)
            ->create([
                'commit_type' => CommitType::CHORE,
                'additions' => 100,
                'deletions' => 20,
                'files_changed' => 5,
            ]);

        $featureScore = $action->execute($featureCommit);
        $choreScore = $action->execute($choreCommit);

        expect($featureScore)->toBeGreaterThan($choreScore);
    });

    it('gives bonus points for merge commits', function () {
        $repository = Repository::factory()->create();
        $action = new CalculateImpactScore();

        $regularCommit = Commit::factory()
            ->for($repository)
            ->create(['is_merge' => false]);

        $mergeCommit = Commit::factory()
            ->for($repository)
            ->create(['is_merge' => true]);

        expect($action->execute($mergeCommit))
            ->toBeGreaterThan($action->execute($regularCommit));
    });

    it('normalizes line changes against repository average', function () {
        $repository = Repository::factory()->create();
        
        // Create existing commits to establish average
        Commit::factory()
            ->count(10)
            ->for($repository)
            ->create(['additions' => 50, 'deletions' => 10]);

        $action = new CalculateImpactScore();

        $smallCommit = Commit::factory()
            ->for($repository)
            ->create(['additions' => 10, 'deletions' => 5]);

        $largeCommit = Commit::factory()
            ->for($repository)
            ->create(['additions' => 200, 'deletions' => 50]);

        expect($action->execute($largeCommit))
            ->toBeGreaterThan($action->execute($smallCommit));
    });
});
```

**Feature Test: GitHub Webhook** (`tests/Feature/Webhooks/GitHubWebhookTest.php`)
```php
<?php

declare(strict_types=1);

use App\Models\Repository;
use App\Models\User;
use App\Jobs\ProcessGitHubPush;
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

describe('GitHub Webhook Processing', function () {
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
});

describe('Webhook Event Filtering', function () {
    it('processes push events', function () {
        $payload = createPushPayload($this->repository->github_id);
        $signature = createGitHubSignature($payload);

        $response = $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'push',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        $response->assertOk();
    });

    it('processes pull_request events', function () {
        $payload = createPullRequestPayload($this->repository->github_id);
        $signature = createGitHubSignature($payload);

        $response = $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'pull_request',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        $response->assertOk();
    });

    it('ignores unsupported events', function () {
        $payload = ['action' => 'created'];
        $signature = createGitHubSignature($payload);

        $response = $this->postJson('/api/webhooks/github', $payload, [
            'X-GitHub-Event' => 'star',
            'X-Hub-Signature-256' => $signature,
            'X-GitHub-Delivery' => fake()->uuid(),
        ]);

        // Webhook is received but not processed
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
            'message' => fake()->sentence(),
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

function createPullRequestPayload(string $repoId): array
{
    return [
        'action' => 'opened',
        'repository' => ['id' => $repoId],
        'pull_request' => [
            'id' => fake()->randomNumber(8),
            'title' => fake()->sentence(),
            'merged' => false,
        ],
    ];
}

function createGitHubSignature(array $payload): string
{
    $secret = config('webhook-client.configs.0.signing_secret');
    
    return 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);
}
```

### Frontend Package Dependencies

```json
{
    "dependencies": {
        "vue": "^3.5",
        "@inertiajs/vue3": "^2.0",
        "@vueuse/core": "^12.0",
        "laravel-echo": "^2.0",
        "pusher-js": "^8.4",
        "chart.js": "^4.4",
        "vue-chartjs": "^5.3",
        "date-fns": "^4.1",
        "lodash-es": "^4.17"
    },
    "devDependencies": {
        "typescript": "^5.6",
        "vite": "^6.0",
        "laravel-vite-plugin": "^1.0",
        "@vitejs/plugin-vue": "^5.2",
        "@tailwindcss/vite": "^4.0",
        "tailwindcss": "^4.0",
        "@headlessui/vue": "^1.7",
        "@heroicons/vue": "^2.2",
        "eslint": "^9.0",
        "@typescript-eslint/eslint-plugin": "^8.0",
        "eslint-plugin-vue": "^9.0",
        "husky": "^9.0",
        "lint-staged": "^15.0"
    }
}
```

### Tailwind CSS v4 Configuration

Tailwind CSS v4 uses a CSS-first configuration approach. No `tailwind.config.js` required.

**Main CSS File** (`resources/css/app.css`)
```css
@import "tailwindcss";

/* Theme customization using CSS variables */
@theme {
    /* Colors */
    --color-primary-50: oklch(0.97 0.02 250);
    --color-primary-100: oklch(0.93 0.04 250);
    --color-primary-500: oklch(0.55 0.2 250);
    --color-primary-600: oklch(0.48 0.2 250);
    --color-primary-700: oklch(0.4 0.18 250);
    
    /* GitPulse brand colors */
    --color-pulse-green: oklch(0.7 0.18 145);
    --color-pulse-blue: oklch(0.6 0.15 250);
    --color-pulse-orange: oklch(0.75 0.18 55);
    
    /* Fonts */
    --font-sans: "Inter", ui-sans-serif, system-ui, sans-serif;
    --font-mono: "JetBrains Mono", ui-monospace, monospace;
    
    /* Custom spacing */
    --spacing-18: 4.5rem;
    --spacing-88: 22rem;
    
    /* Border radius */
    --radius-xl: 1rem;
    --radius-2xl: 1.5rem;
}

/* Dark mode overrides */
@media (prefers-color-scheme: dark) {
    @theme {
        --color-primary-500: oklch(0.65 0.2 250);
    }
}

/* Component layers */
@layer components {
    .btn-primary {
        @apply bg-primary-600 text-white px-4 py-2 rounded-lg font-medium
               hover:bg-primary-700 transition-colors duration-150
               focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2;
    }
    
    .card {
        @apply bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 
               dark:border-gray-700 p-6;
    }
    
    .metric-card {
        @apply card flex flex-col gap-2;
    }
    
    .input-field {
        @apply block w-full rounded-lg border-gray-300 dark:border-gray-600 
               dark:bg-gray-700 shadow-sm 
               focus:border-primary-500 focus:ring-primary-500;
    }
}

/* Animations */
@layer utilities {
    .animate-slide-in {
        animation: slide-in 0.3s ease-out;
    }
    
    @keyframes slide-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
}
```

**Vite Configuration** (`vite.config.ts`)
```typescript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
});
```

### Data Models (Laravel Eloquent)

**User Model** (`app/Models/User.php`)
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->string('github_id')->unique()->nullable();
    $table->string('github_username')->nullable();
    $table->text('github_token')->nullable(); // Encrypted via cast
    $table->string('avatar_url')->nullable();
    $table->json('preferences')->nullable();
    $table->string('timezone')->default('UTC');
    $table->rememberToken();
    $table->timestamps();
});
```

**Repository Model** (`app/Models/Repository.php`)
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
    $table->index(['user_id', 'is_active']);
});
```

**Commit Model** (`app/Models/Commit.php`)
```php
// Main commits table (partitioned by committed_at month)
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
    $table->json('files')->nullable(); // Array of changed file paths
    $table->enum('commit_type', ['feat', 'fix', 'chore', 'docs', 'refactor', 'test', 'style', 'perf', 'other'])->default('other');
    $table->decimal('impact_score', 5, 2)->default(0);
    $table->json('external_refs')->nullable(); // Issue numbers, ticket IDs
    $table->boolean('is_merge')->default(false);
    $table->timestamps();
    
    $table->index(['user_id', 'committed_at']);
    $table->index(['repository_id', 'committed_at']);
    $table->index(['committed_at']); // For partitioning queries
});

// MySQL Partitioning (run via raw migration)
// PARTITION BY RANGE (YEAR(committed_at) * 100 + MONTH(committed_at))
```

**DailyMetric Model** (`app/Models/DailyMetric.php`)
```php
Schema::create('daily_metrics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('date');
    $table->unsignedInteger('total_commits')->default(0);
    $table->decimal('total_impact', 8, 2)->default(0);
    $table->unsignedSmallInteger('repos_active')->default(0);
    $table->decimal('hours_active', 4, 2)->default(0);
    $table->json('commit_types')->nullable(); // {feat: 3, fix: 2, ...}
    $table->json('hourly_distribution')->nullable(); // {0: 0, 1: 2, ..., 23: 1}
    $table->unsignedInteger('additions')->default(0);
    $table->unsignedInteger('deletions')->default(0);
    $table->timestamps();
    
    $table->unique(['user_id', 'date']);
    $table->index(['date']); // For comparative queries
});
```

**WeeklyReport Model** (`app/Models/WeeklyReport.php`)
```php
Schema::create('weekly_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('week_start');
    $table->date('week_end');
    $table->json('summary_stats'); // Aggregated metrics
    $table->json('accomplishments'); // AI-generated highlights
    $table->json('trends'); // WoW comparisons
    $table->json('insights')->nullable(); // AI recommendations
    $table->text('user_notes')->nullable(); // Manual additions
    $table->string('pdf_path')->nullable();
    $table->enum('status', ['draft', 'generated', 'sent'])->default('draft');
    $table->timestamp('generated_at')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamps();
    
    $table->unique(['user_id', 'week_start']);
});
```

**Insight Model** (`app/Models/Insight.php`)
```php
Schema::create('insights', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['productivity', 'pattern', 'recommendation', 'warning']);
    $table->enum('period', ['daily', 'weekly', 'monthly']);
    $table->string('title');
    $table->text('description');
    $table->json('data_points')->nullable(); // Supporting metrics
    $table->decimal('confidence', 3, 2)->default(0.80);
    $table->boolean('is_read')->default(false);
    $table->boolean('is_dismissed')->default(false);
    $table->date('valid_from');
    $table->date('valid_until')->nullable();
    $table->timestamps();
    
    $table->index(['user_id', 'is_read', 'created_at']);
});
```

**Note on P1 Feature Models:** The following models are part of Phase 2 (Post-MVP) and will be defined during Week 11-14 development:
- **Team Model** (F8: Team Dashboard) - For opt-in team aggregation
- **Goal Model** (F9: Goal Setting & Tracking) - For weekly/monthly targets

### Redis Data Structures

```php
// Real-time counters (updated on each commit)
Redis::hincrby("user:{$userId}:today", 'commits', 1);
Redis::hincrby("user:{$userId}:today", 'impact', $impactScore);
Redis::expire("user:{$userId}:today", 86400 * 2);

// Streak tracking
Redis::set("user:{$userId}:streak", $streakCount);
Redis::set("user:{$userId}:last_commit_date", $date);

// Hot metrics cache (refreshed hourly)
Redis::setex("user:{$userId}:dashboard", 3600, json_encode($dashboardData));

// Rate limiting for webhooks
Redis::throttle("webhook:{$repoId}")->allow(100)->every(60);
```

### Laravel Project Structure

```
gitpulse/
├── app/
│   ├── Actions/                    # Single-purpose action classes
│   │   ├── Commits/
│   │   │   ├── ParseCommitMessage.php
│   │   │   ├── CalculateImpactScore.php
│   │   │   └── CategorizeCommit.php
│   │   ├── Reports/
│   │   │   ├── GenerateWeeklyReport.php
│   │   │   └── ExportReportToPdf.php
│   │   └── Insights/
│   │       └── GenerateAIInsights.php
│   │
│   ├── Data/                       # Spatie Data DTOs
│   │   ├── CommitData.php
│   │   ├── DashboardData.php
│   │   └── ReportData.php
│   │
│   ├── Enums/
│   │   ├── CommitType.php
│   │   ├── InsightType.php
│   │   └── ReportStatus.php
│   │
│   ├── Events/                     # Broadcasting Events (Reverb)
│   │   ├── CommitProcessed.php
│   │   ├── DailyMetricsUpdated.php
│   │   ├── ReportGenerated.php
│   │   └── InsightCreated.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── ReportController.php
│   │   │   ├── RepositoryController.php
│   │   │   ├── SettingsController.php
│   │   │   └── InsightController.php
│   │   │
│   │   └── Middleware/
│   │       └── ...
│   │
│   ├── Jobs/
│   │   ├── ProcessGitHubPush.php
│   │   ├── ProcessPullRequest.php
│   │   ├── SyncRepositoryCommits.php
│   │   ├── CalculateDailyMetrics.php
│   │   ├── GenerateWeeklyReportJob.php
│   │   └── GenerateInsightsJob.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Repository.php
│   │   ├── Commit.php
│   │   ├── DailyMetric.php
│   │   ├── WeeklyReport.php
│   │   └── Insight.php
│   │
│   ├── Services/
│   │   ├── GitHub/
│   │   │   ├── GitHubService.php
│   │   │   └── WebhookService.php
│   │   ├── Analytics/
│   │   │   ├── MetricsService.php
│   │   │   └── ComparisonService.php
│   │   └── AI/
│   │       └── ClaudeInsightService.php
│   │
│   ├── Webhooks/                   # Spatie Webhook Client
│   │   └── GitHub/
│   │       ├── GitHubSignatureValidator.php
│   │       ├── GitHubWebhookProfile.php
│   │       └── ProcessGitHubWebhook.php
│   │
│   └── Providers/
│       └── AppServiceProvider.php
│
├── config/
│   ├── services.php                # GitHub OAuth config
│   ├── horizon.php                 # Queue configuration
│   ├── reverb.php                  # WebSocket configuration
│   ├── broadcasting.php            # Broadcasting driver config
│   ├── webhook-client.php          # Spatie Webhook Client config
│   └── gitpulse.php                # App-specific config
│
├── database/
│   ├── factories/                  # Model factories for testing
│   │   ├── UserFactory.php
│   │   ├── RepositoryFactory.php
│   │   ├── CommitFactory.php
│   │   ├── DailyMetricFactory.php
│   │   └── WeeklyReportFactory.php
│   ├── migrations/
│   └── seeders/
│
├── resources/
│   ├── css/
│   │   └── app.css                 # Tailwind v4 with @theme config
│   │
│   ├── js/
│   │   ├── app.ts                  # Inertia + Vue bootstrap
│   │   ├── bootstrap.ts            # Laravel Echo + Reverb setup
│   │   ├── types/                  # TypeScript interfaces
│   │   │   ├── index.d.ts
│   │   │   ├── models.ts
│   │   │   ├── events.ts           # Broadcasting event types
│   │   │   └── inertia.d.ts
│   │   ├── Components/
│   │   │   ├── Charts/
│   │   │   │   ├── CommitTimeline.vue
│   │   │   │   ├── ImpactTrend.vue
│   │   │   │   ├── HeatmapCalendar.vue
│   │   │   │   └── TypeBreakdown.vue
│   │   │   ├── Dashboard/
│   │   │   │   ├── MetricCard.vue
│   │   │   │   ├── TodayVsYesterday.vue
│   │   │   │   ├── CommitList.vue
│   │   │   │   ├── LiveCommitFeed.vue  # Real-time via Reverb
│   │   │   │   └── StreakBadge.vue
│   │   │   └── UI/
│   │   │       ├── AppLayout.vue
│   │   │       ├── Sidebar.vue
│   │   │       ├── Button.vue
│   │   │       ├── Card.vue
│   │   │       └── ...
│   │   ├── Pages/
│   │   │   ├── Dashboard.vue
│   │   │   ├── Reports/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── Show.vue
│   │   │   │   └── Edit.vue
│   │   │   ├── Repositories/
│   │   │   │   └── Index.vue
│   │   │   ├── Insights.vue
│   │   │   ├── Settings.vue
│   │   │   └── Auth/
│   │   │       ├── Login.vue
│   │   │       └── Register.vue
│   │   ├── Composables/
│   │   │   ├── useMetrics.ts
│   │   │   ├── useRealtime.ts      # Reverb subscription hooks
│   │   │   └── useCharts.ts
│   │   └── Layouts/
│   │       └── AuthenticatedLayout.vue
│   │
│   └── views/
│       ├── app.blade.php           # Inertia root template
│       └── reports/
│           └── pdf.blade.php       # PDF export template
│
├── routes/
│   ├── web.php                     # Inertia routes
│   ├── api.php                     # API routes (if needed)
│   ├── channels.php                # Reverb channel authorization
│   └── webhooks.php                # Spatie webhook routes
│
├── tests/
│   ├── Pest.php                    # PestPHP configuration
│   ├── TestCase.php                # Base test case
│   ├── Feature/
│   │   ├── DashboardTest.php
│   │   ├── ReportTest.php
│   │   ├── RepositoryTest.php
│   │   ├── Auth/
│   │   │   ├── LoginTest.php
│   │   │   └── RegistrationTest.php
│   │   └── Webhooks/
│   │       └── GitHubWebhookTest.php
│   └── Unit/
│       ├── Actions/
│       │   ├── CalculateImpactScoreTest.php
│       │   ├── ParseCommitMessageTest.php
│       │   └── CategorizeCommitTest.php
│       ├── Services/
│       │   └── MetricsServiceTest.php
│       └── Models/
│           └── CommitTest.php
│
├── pint.json                       # Laravel Pint configuration
├── phpstan.neon                    # Larastan configuration  
├── rector.php                      # Rector configuration
├── vite.config.ts                  # Vite with Tailwind v4 plugin
├── tsconfig.json                   # TypeScript configuration
└── phpunit.xml                     # PHPUnit/Pest configuration
```

### Key Laravel Implementations

**GitHub Webhook Controller (Spatie Webhook Client)**

Spatie's Laravel Webhook Client handles signature verification, request logging, and job dispatching automatically.

```php
// config/webhook-client.php
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

```php
// app/Webhooks/GitHub/GitHubSignatureValidator.php
namespace App\Webhooks\GitHub;

use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class GitHubSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
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

```php
// app/Webhooks/GitHub/GitHubWebhookProfile.php
namespace App\Webhooks\GitHub;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

class GitHubWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        $event = $request->header('X-GitHub-Event');
        
        // Only process push and pull_request events
        return in_array($event, ['push', 'pull_request']);
    }
}
```

```php
// app/Webhooks/GitHub/ProcessGitHubWebhook.php
namespace App\Webhooks\GitHub;

use App\Jobs\ProcessGitHubPush;
use App\Jobs\ProcessPullRequest;
use App\Models\Repository;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

class ProcessGitHubWebhook extends ProcessWebhookJob
{
    public function handle(): void
    {
        $payload = $this->webhookCall->payload;
        $event = $this->webhookCall->headers['x-github-event'][0] ?? null;
        
        $repository = Repository::where('github_id', $payload['repository']['id'])
            ->where('is_active', true)
            ->first();
        
        if (!$repository) {
            return;
        }
        
        match ($event) {
            'push' => $this->handlePush($repository, $payload),
            'pull_request' => $this->handlePullRequest($repository, $payload),
            default => null,
        };
    }
    
    private function handlePush(Repository $repository, array $payload): void
    {
        foreach ($payload['commits'] ?? [] as $commitData) {
            ProcessGitHubPush::dispatch($repository, $commitData)
                ->onQueue('commits');
        }
    }
    
    private function handlePullRequest(Repository $repository, array $payload): void
    {
        if (in_array($payload['action'], ['opened', 'closed', 'merged'])) {
            ProcessPullRequest::dispatch($repository, $payload)
                ->onQueue('commits');
        }
    }
}
```

```php
// routes/webhooks.php
use Illuminate\Support\Facades\Route;

Route::webhooks('/webhooks/github', 'github');
```

```php
// app/Providers/AppServiceProvider.php (register routes)
public function boot(): void
{
    // Register webhook routes with 'api' prefix
    // Final URL: /api/webhooks/github
    Route::middleware('api')
        ->prefix('api')
        ->group(base_path('routes/webhooks.php'));
}
```

**Note:** The webhook endpoint will be accessible at `https://gitpulse.app/api/webhooks/github`. Configure this URL in your GitHub repository webhook settings.

**Inertia Dashboard Controller**
```php
// app/Http/Controllers/DashboardController.php

use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MetricsService $metrics): Response
    {
        $user = $request->user();
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();
        
        // Data is also updated in real-time via Reverb WebSockets
        return Inertia::render('Dashboard', [
            'todayMetrics' => fn () => $metrics->getForDate($user, $today),
            'yesterdayMetrics' => fn () => $metrics->getForDate($user, $yesterday),
            'recentCommits' => fn () => $user->commits()
                ->with('repository')
                ->whereDate('committed_at', $today)
                ->latest('committed_at')
                ->take(20)
                ->get(),
            'weeklyTrend' => fn () => $metrics->getWeeklyTrend($user),
            'streak' => fn () => $metrics->getCurrentStreak($user),
            'repositories' => fn () => $user->repositories()
                ->where('is_active', true)
                ->get(['id', 'name', 'full_name']),
        ]);
    }
}
```

**Vue Dashboard Page (TypeScript + Reverb Real-time)**
```vue
<!-- resources/js/Pages/Dashboard.vue -->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import MetricCard from '@/Components/Dashboard/MetricCard.vue'
import TodayVsYesterday from '@/Components/Dashboard/TodayVsYesterday.vue'
import CommitTimeline from '@/Components/Charts/CommitTimeline.vue'
import CommitList from '@/Components/Dashboard/CommitList.vue'
import LiveCommitFeed from '@/Components/Dashboard/LiveCommitFeed.vue'
import { useRealtimeMetrics } from '@/Composables/useRealtime'

interface Props {
    todayMetrics: DailyMetric
    yesterdayMetrics: DailyMetric
    recentCommits: Commit[]
    weeklyTrend: TrendData[]
    streak: number
    repositories: Repository[]
}

const props = defineProps<Props>()

// Real-time updates via Laravel Reverb
const { 
    todayCommits, 
    todayImpact, 
    liveCommits,
    isConnected 
} = useRealtimeMetrics(props.todayMetrics)

const percentChange = computed(() => {
    const today = todayCommits.value
    const yesterday = props.yesterdayMetrics.total_commits
    if (yesterday === 0) return today > 0 ? 100 : 0
    return Math.round(((today - yesterday) / yesterday) * 100)
})

// Merge live commits with initial data
const allCommits = computed(() => {
    const liveIds = new Set(liveCommits.value.map(c => c.id))
    const filtered = props.recentCommits.filter(c => !liveIds.has(c.id))
    return [...liveCommits.value, ...filtered].slice(0, 20)
})
</script>

<template>
    <AuthenticatedLayout title="Dashboard">
        <!-- Connection status indicator -->
        <div v-if="isConnected" class="mb-4 flex items-center gap-2 text-sm text-green-600">
            <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
            Live updates active
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <MetricCard
                title="Today's Commits"
                :value="todayCommits"
                :change="percentChange"
                icon="code-branch"
                :animate="true"
            />
            <MetricCard
                title="Impact Score"
                :value="todayImpact.toFixed(1)"
                icon="bolt"
            />
            <MetricCard
                title="Active Repos"
                :value="todayMetrics.repos_active"
                icon="folder"
            />
            <MetricCard
                title="Current Streak"
                :value="`${streak} days`"
                icon="fire"
            />
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <CommitTimeline :data="weeklyTrend" />
            </div>
            <div>
                <TodayVsYesterday
                    :today="{ ...todayMetrics, total_commits: todayCommits, total_impact: todayImpact }"
                    :yesterday="yesterdayMetrics"
                />
            </div>
        </div>
        
        <!-- Live commit feed with animated entries -->
        <LiveCommitFeed :commits="allCommits" class="mt-8" />
    </AuthenticatedLayout>
</template>
```

**Impact Score Calculation**
```php
// app/Actions/Commits/CalculateImpactScore.php

class CalculateImpactScore
{
    private const WEIGHTS = [
        'lines_changed' => 0.20,
        'files_touched' => 0.15,
        'commit_type' => 0.25,
        'is_merge' => 0.20,
        'external_refs' => 0.10,
        'focus_time' => 0.10,
    ];
    
    private const TYPE_SCORES = [
        'feat' => 1.0,
        'fix' => 0.8,
        'refactor' => 0.7,
        'perf' => 0.7,
        'test' => 0.5,
        'docs' => 0.3,
        'style' => 0.2,
        'chore' => 0.2,
        'other' => 0.4,
    ];
    
    public function execute(Commit $commit): float
    {
        $repo = $commit->repository;
        $avgLines = $repo->commits()->avg(DB::raw('additions + deletions')) ?: 100;
        
        $scores = [
            'lines_changed' => min(($commit->additions + $commit->deletions) / $avgLines, 2.0),
            'files_touched' => min($commit->files_changed / 5, 1.5),
            'commit_type' => self::TYPE_SCORES[$commit->commit_type->value] ?? 0.4,
            'is_merge' => $commit->is_merge ? 1.5 : 0.5,
            'external_refs' => count($commit->external_refs ?? []) > 0 ? 1.0 : 0.5,
            'focus_time' => $this->getFocusTimeMultiplier($commit->committed_at),
        ];
        
        $weightedScore = collect(self::WEIGHTS)
            ->map(fn ($weight, $key) => $weight * $scores[$key])
            ->sum();
        
        return round($weightedScore * 10, 2); // Scale to 0-10+
    }
    
    private function getFocusTimeMultiplier(Carbon $time): float
    {
        $hour = $time->hour;
        // Peak productivity hours (9-12, 14-17) get bonus
        return match (true) {
            $hour >= 9 && $hour <= 12 => 1.2,
            $hour >= 14 && $hour <= 17 => 1.1,
            $hour >= 6 && $hour <= 21 => 1.0,
            default => 0.8, // Late night/early morning penalty
        };
    }
}
```

### DigitalOcean Infrastructure (via Forge)

```
Production Environment
├── App Server (s-2vcpu-4gb)
│   ├── Ubuntu 24.04 LTS
│   ├── PHP 8.3 + OPcache
│   ├── Nginx + SSL (Let's Encrypt)
│   ├── Node.js 20 LTS (for Vite builds)
│   └── Supervisor (queue workers)
│
├── Worker Server (s-1vcpu-2gb) [Optional - can run on app server]
│   ├── Laravel Horizon
│   └── 4 queue workers (commits, reports, insights, default)
│
├── Managed MySQL (db-s-2vcpu-4gb)
│   ├── MySQL 8.0
│   ├── Daily backups
│   └── Read replica (for analytics queries)
│
├── Managed Redis (db-s-1vcpu-1gb)
│   ├── Queue backend
│   ├── Cache
│   └── Real-time counters
│
└── Spaces (S3-compatible storage)
    ├── PDF reports
    └── Backups
```

**Forge Deployment Script**
```bash
cd /home/forge/gitpulse.app

git pull origin main

composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

npm ci
npm run build

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache

# Restart queue workers and WebSocket server
php artisan horizon:terminate
php artisan reverb:restart

echo "Deployment complete!"
```

### CI/CD Pipeline (GitHub Actions)

```yaml
# .github/workflows/ci.yml
name: CI Pipeline

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  code-quality:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite, bcmath, redis
          coverage: xdebug
          
      - name: Cache Composer dependencies
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: composer-
          
      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction
        
      - name: Run Pint (Code Style)
        run: ./vendor/bin/pint --test
        
      - name: Run Larastan (Static Analysis)
        run: ./vendor/bin/phpstan analyse --memory-limit=2G
        
      - name: Run Rector (Dry Run)
        run: ./vendor/bin/rector process --dry-run

  tests:
    runs-on: ubuntu-latest
    needs: code-quality
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: gitpulse_test
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3
        
      redis:
        image: redis:7
        ports:
          - 6379:6379
        options: --health-cmd="redis-cli ping" --health-interval=10s --health-timeout=5s --health-retries=3
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, pdo_mysql, bcmath, redis
          coverage: xdebug
          
      - name: Install dependencies
        run: composer install --prefer-dist --no-interaction
        
      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
          cache: 'npm'
          
      - name: Install NPM dependencies
        run: npm ci
        
      - name: Build assets
        run: npm run build
        
      - name: Copy .env
        run: cp .env.ci .env
        
      - name: Generate key
        run: php artisan key:generate
        
      - name: Run migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: gitpulse_test
          DB_USERNAME: root
          DB_PASSWORD: password
          
      - name: Run Pest Tests
        run: php artisan test --parallel --coverage --min=80
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: gitpulse_test
          DB_USERNAME: root
          DB_PASSWORD: password
          REDIS_HOST: 127.0.0.1
          
      - name: Run Type Coverage
        run: ./vendor/bin/pest --type-coverage --min=90

  deploy:
    runs-on: ubuntu-latest
    needs: tests
    if: github.ref == 'refs/heads/main'
    
    steps:
      - name: Deploy to Forge
        uses: jbrooksuk/laravel-forge-action@v1.0.4
        with:
          trigger_url: ${{ secrets.FORGE_DEPLOY_WEBHOOK }}
```

### Composer Scripts

```json
{
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "lint": "./vendor/bin/pint",
        "lint:check": "./vendor/bin/pint --test",
        "analyse": "./vendor/bin/phpstan analyse --memory-limit=2G",
        "refactor": "./vendor/bin/rector process",
        "refactor:check": "./vendor/bin/rector process --dry-run",
        "test": "@php artisan test",
        "test:coverage": "@php artisan test --coverage --min=80",
        "test:parallel": "@php artisan test --parallel",
        "test:types": "./vendor/bin/pest --type-coverage --min=90",
        "quality": [
            "@lint:check",
            "@analyse",
            "@refactor:check",
            "@test:coverage"
        ]
    }
}
```

### Pre-commit Hooks (via Husky + lint-staged alternative)

```json
// package.json
{
    "scripts": {
        "prepare": "husky install"
    },
    "lint-staged": {
        "*.php": [
            "./vendor/bin/pint",
            "./vendor/bin/phpstan analyse --memory-limit=2G"
        ],
        "*.{vue,ts,js}": [
            "eslint --fix"
        ]
    }
}
```

```bash
# .husky/pre-commit
#!/usr/bin/env sh
. "$(dirname -- "$0")/_/husky.sh"

# PHP Quality Checks
./vendor/bin/pint --dirty
./vendor/bin/phpstan analyse --memory-limit=2G

# Run fast unit tests only
php artisan test --testsuite=Unit --stop-on-failure
```

---

## Non-Functional Requirements

### Performance

| Metric | Target | Implementation |
|--------|--------|----------------|
| Webhook processing latency | < 500ms | Redis queue with dedicated `commits` queue |
| Dashboard load time | < 2s | Inertia.js partial reloads + Redis caching |
| Report generation | < 10s | Background job via Horizon, user notified via Broadcasting |
| API response time (P95) | < 200ms | Eager loading, query caching, MySQL indexes |
| Concurrent webhook handling | 1000/min | Laravel rate limiting + Redis throttle |

### MySQL Optimization Strategies

```sql
-- Partitioned commits table for efficient range queries
ALTER TABLE commits
PARTITION BY RANGE (YEAR(committed_at) * 100 + MONTH(committed_at)) (
    PARTITION p202501 VALUES LESS THAN (202502),
    PARTITION p202502 VALUES LESS THAN (202503),
    -- Add partitions monthly via scheduled job
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- Key indexes for common queries
CREATE INDEX idx_commits_user_date ON commits(user_id, committed_at);
CREATE INDEX idx_commits_repo_date ON commits(repository_id, committed_at);
CREATE INDEX idx_daily_metrics_lookup ON daily_metrics(user_id, date);
```

### Scalability

- Support 10,000+ users at launch
- Handle 1M+ commits per day via Horizon workers
- Horizontal scaling: Add worker droplets via Forge
- Read replica for analytics queries (Forge managed MySQL)
- Redis Cluster for queue scaling

### Security

- GitHub OAuth with minimal scope (`repo` read-only)
- Encrypted tokens via Laravel's `encrypted` cast
- HTTPS everywhere (Forge auto-SSL)
- CSRF protection on all forms (Inertia handles automatically)
- Rate limiting on webhooks and API endpoints
- GDPR compliant: `php artisan user:export` and `user:delete` commands
- No source code storage (metadata only)
- Webhook signature verification middleware

### Laravel Security Configuration

```php
// config/fortify.php
'features' => [
    Features::registration(),
    Features::resetPasswords(),
    Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],

// Rate limiting in RouteServiceProvider
RateLimiter::for('webhooks', function (Request $request) {
    return Limit::perMinute(100)->by($request->ip());
});

RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### Reliability

- 99.9% uptime SLA (DigitalOcean managed services)
- Automated backups every 6 hours (Forge + DO managed DB)
- Failed webhook retry with exponential backoff (Laravel job retries)
- Graceful degradation if Claude API unavailable
- Dead letter queue for failed jobs
- Health check endpoint for monitoring

### Code Quality Thresholds

| Metric | Target | Enforcement |
|--------|--------|-------------|
| **Code Coverage** | ≥ 80% | CI fails below threshold |
| **Type Coverage** | ≥ 90% | `pest --type-coverage --min=90` |
| **PHPStan Level** | 8 (max) | Zero errors allowed |
| **Pint Style** | Laravel preset | CI fails on violations |
| **Rector** | Clean | Dry-run must pass |

**Quality Gates in CI:**
1. `./vendor/bin/pint --test` — Code style check
2. `./vendor/bin/phpstan analyse` — Static analysis at Level 8
3. `./vendor/bin/rector process --dry-run` — No pending refactors
4. `php artisan test --coverage --min=80` — Test coverage
5. `./vendor/bin/pest --type-coverage --min=90` — Type coverage

---

## User Stories

### Epic 1: Onboarding

| ID | Story | Priority |
|----|-------|----------|
| US-1.1 | As a user, I can sign up with my GitHub account so that I don't need a separate password | P0 |
| US-1.2 | As a user, I can select which repositories to track so that I only see relevant data | P0 |
| US-1.3 | As a user, I receive a guided tour on first login so that I understand the dashboard | P1 |

### Epic 2: Daily Tracking

| ID | Story | Priority |
|----|-------|----------|
| US-2.1 | As a user, I can see today's commits in real-time so that I know my activity is being tracked | P0 |
| US-2.2 | As a user, I can compare today with yesterday so that I can gauge my progress | P0 |
| US-2.3 | As a user, I can filter by repository so that I can focus on specific projects | P0 |
| US-2.4 | As a user, I can see my commit streak so that I stay motivated | P1 |

### Epic 3: Weekly Reports

| ID | Story | Priority |
|----|-------|----------|
| US-3.1 | As a user, I receive an automated weekly report so that I don't have to write status updates | P0 |
| US-3.2 | As a user, I can edit the report before sending so that I can add context | P0 |
| US-3.3 | As a user, I can export as PDF/Markdown so that I can share with stakeholders | P0 |
| US-3.4 | As a user, I can customize report templates so that they match my company's style | P1 |

### Epic 4: Analytics & Insights

| ID | Story | Priority |
|----|-------|----------|
| US-4.1 | As a user, I can see week-over-week trends so that I understand my patterns | P0 |
| US-4.2 | As a user, I can identify my most productive days so that I can optimize my schedule | P1 |
| US-4.3 | As a user, I receive AI-generated insights so that I get actionable recommendations | P1 |
| US-4.4 | As a user, I can set productivity goals so that I have targets to aim for | P2 |

---

## Roadmap

### Phase 1: MVP (Weeks 1-6)

**Week 1-2: Foundation & Authentication**
- [ ] Laravel 12 project setup with Vite + Vue 3.5 + TypeScript
- [ ] Tailwind CSS v4 setup with `@tailwindcss/vite` plugin
- [ ] Custom theme configuration in `app.css` using `@theme` directive
- [ ] Inertia.js 2.0 configuration with SSR
- [ ] Headless UI component library setup
- [ ] Laravel Fortify authentication (login, register, password reset)
- [ ] Laravel Socialite GitHub OAuth integration
- [ ] User model with GitHub token encryption
- [ ] Base layout components (AuthenticatedLayout, Sidebar, Navigation)
- [ ] Basic settings page for account management
- [ ] **Testing & Code Quality Setup:**
  - [ ] PestPHP configuration with `tests/Pest.php`
  - [ ] Pint configuration (`pint.json`)
  - [ ] Larastan configuration (`phpstan.neon`) at Level 8
  - [ ] Rector configuration (`rector.php`)
  - [ ] GitHub Actions CI/CD pipeline
  - [ ] Pre-commit hooks with Husky
  - [ ] Model factories for User, Repository

**Week 3-4: Core Data Pipeline**
- [ ] Repository model and migration
- [ ] Commit model with partitioned table migration
- [ ] Spatie Webhook Client configuration (`config/webhook-client.php`)
- [ ] `GitHubSignatureValidator` for HMAC-SHA256 verification
- [ ] `GitHubWebhookProfile` for event filtering
- [ ] `ProcessGitHubWebhook` job class
- [ ] `ProcessGitHubPush` job with Redis queue
- [ ] `ProcessPullRequest` job for PR events
- [ ] `ParseCommitMessage` action (Conventional Commits parser)
- [ ] `CategorizeCommit` action (NLP fallback)
- [ ] `CalculateImpactScore` action
- [ ] Laravel Horizon setup and configuration
- [ ] Repository management page (connect/disconnect repos)
- [ ] Webhook call logging and retry handling (Spatie built-in)
- [ ] **Testing:**
  - [ ] Model factories for Commit, DailyMetric
  - [ ] Unit tests for `CalculateImpactScore` action
  - [ ] Unit tests for `ParseCommitMessage` action
  - [ ] Feature tests for GitHub webhook processing
  - [ ] Feature tests for repository management

**Week 5-6: Dashboard & Reports**
- [ ] DailyMetric model and aggregation job
- [ ] `MetricsService` for dashboard data
- [ ] Dashboard page with real-time metrics (via Reverb Broadcasting)
- [ ] `MetricCard`, `CommitList`, `TodayVsYesterday` Vue components
- [ ] `CommitTimeline` chart component (Chart.js)
- [ ] WeeklyReport model and migrations
- [ ] `GenerateWeeklyReportJob` scheduled task
- [ ] Report view and edit pages
- [ ] PDF export via DomPDF
- [ ] Markdown export endpoint
- [ ] **Testing:**
  - [ ] Model factories for WeeklyReport
  - [ ] Feature tests for dashboard (authenticated/guest)
  - [ ] Feature tests for report generation
  - [ ] Unit tests for `MetricsService`
  - [ ] Unit tests for day-over-day comparison logic
  - [ ] **Target: 80% code coverage, 90% type coverage**

**Deliverable:** Working MVP for 10 beta users

### Phase 2: Analytics & Insights (Weeks 7-10)

**Week 7-8: Comparative Analytics**
- [ ] `ComparisonService` for WoW, MoM calculations
- [ ] `HeatmapCalendar` component (GitHub-style)
- [ ] `ImpactTrend` line chart component
- [ ] `TypeBreakdown` pie chart component
- [ ] Trend sparklines on dashboard
- [ ] Date range picker with presets
- [ ] Repository filter on all pages
- [ ] Historical data backfill job (sync existing commits)

**Week 9-10: AI-Powered Insights**
- [ ] Insight model and migrations
- [ ] `ClaudeInsightService` integration
- [ ] `GenerateInsightsJob` (weekly scheduled)
- [ ] Insight cards on dashboard
- [ ] Insights page with history
- [ ] Insight feedback mechanism (helpful/not helpful)
- [ ] Pattern detection algorithms (productivity peaks, burnout signals)

**Deliverable:** Analytics-enabled version for 100 users

### Phase 3: Scale & Polish (Weeks 11-14)

**Week 11-12: Integrations & Notifications**
- [ ] Slack webhook integration (daily/weekly summaries)
- [ ] Email notification preferences
- [ ] Weekly report email delivery (Mailgun/Postmark)
- [ ] Google Calendar OAuth (optional)
- [ ] Meeting time correlation analytics
- [ ] Push notification support (web)

**Week 13-14: Team Features & Optimization**
- [ ] Team model (opt-in team creation)
- [ ] Team dashboard with aggregated metrics
- [ ] Goal model (weekly/monthly targets)
- [ ] Goal progress tracking UI
- [ ] Performance optimization (query caching, eager loading)
- [ ] MySQL query profiling and indexing
- [ ] Redis cache warming strategies
- [ ] Load testing and horizontal scaling prep

**Deliverable:** Production-ready v1.0

### Phase 4: Growth (Weeks 15+)

- [ ] GitLab integration
- [ ] Bitbucket integration
- [ ] Public API with rate limiting
- [ ] Zapier integration
- [ ] Mobile-responsive PWA
- [ ] Self-hosted Enterprise edition
- [ ] Annual review generator
- [ ] Team comparison features (anonymized)

---

## Risks & Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| GitHub API rate limits | Medium | High | Implement caching, use webhooks (push-based) over polling |
| Low webhook reliability | Medium | Medium | Implement retry logic, webhook resync mechanism |
| Gaming the metrics | Medium | Medium | Focus on impact scoring over raw counts, encourage honesty via private defaults |
| Privacy concerns | Low | High | Clear data policy, minimal data collection, user-controlled sharing |
| AI hallucination in insights | Medium | Medium | Validate insights against data, allow user feedback/correction |
| Scope creep | High | Medium | Strict prioritization, MVP-first approach |

---

## Success Criteria for Launch

### Quantitative

- 100+ beta users within 4 weeks of launch
- 80% weekly active user retention after 30 days
- Average time to generate weekly report: < 30 seconds
- Net Promoter Score (NPS) > 40

### Qualitative

- Users report saving 2+ hours/week on status reporting
- At least 10 testimonials citing productivity insights
- Zero critical security vulnerabilities

---

## Appendix

### A. Competitive Analysis

| Product | Strengths | Weaknesses | GitPulse Differentiation |
|---------|-----------|------------|-------------------------|
| WakaTime | Detailed coding metrics | Requires IDE plugin, complex setup | Zero-config via webhooks |
| GitHub Insights | Native integration | Limited analytics, no reports | AI-powered insights, export-ready reports |
| LinearB | Enterprise features | Expensive, team-focused | Individual-first, affordable |
| Pluralsight Flow | Deep analytics | Complex, surveillance-adjacent | Privacy-first, self-improvement focus |

### B. Glossary

| Term | Definition |
|------|------------|
| Impact Score | Weighted metric combining commit size, type, and context |
| Conventional Commits | Standardized commit message format (feat:, fix:, etc.) |
| Webhook | GitHub's push notification system for repository events |
| Streak | Consecutive days with at least one commit |

### C. Open Questions

1. Should free tier have limited repos or limited history?
2. How to handle pair programming (shared commits)?
3. Should we support private/self-hosted GitHub Enterprise?
4. Optimal AI model for insight generation (cost vs quality)?

---

**Document History**

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Jan 16, 2026 | Product Team | Initial draft |
| 2.0 | Jan 16, 2026 | Product Team | Updated to Laravel 12 + Vue.js 3 + Inertia.js stack |
| 2.1 | Jan 16, 2026 | Product Team | Replaced Pusher with Laravel Reverb for WebSockets |
| 2.2 | Jan 16, 2026 | Product Team | Upgraded to Tailwind CSS v4, added Spatie Webhook Client |
| 2.3 | Jan 16, 2026 | Product Team | Added testing (PestPHP), code quality (Pint, Larastan, Rector), CI/CD pipeline |
| 2.4 | Jan 16, 2026 | Product Team | Alignment audit: Fixed architecture diagram, composable types, Rector config, missing deps |

---

## Appendix B: Environment Configuration

### Required Environment Variables

```env
# Application
APP_NAME=GitPulse
APP_ENV=production
APP_DEBUG=false
APP_URL=https://gitpulse.app

# Database
DB_CONNECTION=mysql
DB_HOST=your-db-cluster.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=gitpulse
DB_USERNAME=gitpulse
DB_PASSWORD=your-secure-password
MYSQL_ATTR_SSL_CA=/etc/ssl/certs/ca-certificates.crt

# Redis
REDIS_HOST=your-redis-cluster.db.ondigitalocean.com
REDIS_PASSWORD=your-redis-password
REDIS_PORT=25061
REDIS_CLIENT=phpredis

# Queue
QUEUE_CONNECTION=redis

# Broadcasting (Laravel Reverb)
BROADCAST_CONNECTION=reverb

REVERB_APP_ID=gitpulse
REVERB_APP_KEY=your-reverb-app-key
REVERB_APP_SECRET=your-reverb-app-secret
REVERB_HOST=ws.gitpulse.app
REVERB_PORT=443
REVERB_SCHEME=https

# Reverb Server Configuration
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_SCALING_ENABLED=true
REVERB_SCALING_CHANNEL=reverb

# Frontend Reverb Connection (Vite)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"

# GitHub OAuth (Laravel Socialite)
GITHUB_CLIENT_ID=your-github-oauth-app-id
GITHUB_CLIENT_SECRET=your-github-oauth-secret
GITHUB_REDIRECT_URI=https://gitpulse.app/auth/github/callback

# GitHub Webhooks
GITHUB_WEBHOOK_SECRET=your-webhook-secret

# AI Service (Claude API)
ANTHROPIC_API_KEY=your-anthropic-api-key

# Mail
MAIL_MAILER=postmark
POSTMARK_TOKEN=your-postmark-token

# Storage (DigitalOcean Spaces)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-spaces-key
AWS_SECRET_ACCESS_KEY=your-spaces-secret
AWS_DEFAULT_REGION=nyc3
AWS_BUCKET=gitpulse-storage
AWS_ENDPOINT=https://nyc3.digitaloceanspaces.com
AWS_URL=https://gitpulse-storage.nyc3.cdn.digitaloceanspaces.com
```

### Forge Server Requirements

| Component | Specification |
|-----------|---------------|
| PHP | 8.3+ with extensions: redis, pdo_mysql, gd, zip, bcmath, pcntl |
| Node.js | 20 LTS |
| MySQL | 8.0+ |
| Redis | 7.0+ |
| Nginx | Latest stable |
| Supervisor | For Horizon queue workers + Reverb WebSocket server |

### Reverb WebSocket Configuration

**Nginx Configuration for Reverb (via Forge)**
```nginx
# Add to Nginx site configuration
# Forge: Settings > Files > Edit Nginx Configuration

location /app {
    proxy_http_version 1.1;
    proxy_set_header Host $http_host;
    proxy_set_header Scheme $scheme;
    proxy_set_header SERVER_PORT $server_port;
    proxy_set_header REMOTE_ADDR $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header Upgrade $http_upgrade;
    proxy_set_header Connection "Upgrade";

    proxy_pass http://0.0.0.0:8080;
}
```

**Supervisor Configuration for Reverb (via Forge Daemons)**
```ini
# Forge: Server > Daemons > New Daemon

Command: php artisan reverb:start --host=0.0.0.0 --port=8080
Directory: /home/forge/gitpulse.app
User: forge
Processes: 1
Start Seconds: 1
Stop Seconds: 10
Stop Signal: SIGTERM
```

**Laravel Echo Configuration (Frontend)**
```typescript
// resources/js/bootstrap.ts
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});
```

**Real-time Dashboard Composable**
```typescript
// resources/js/Composables/useRealtime.ts
import { ref, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface CommitProcessedEvent {
    commit_id: number;
    message: string;
    repository: string;
    impact_score: number;
    committed_at: string;
}

interface DailyMetricsEvent {
    total_commits: number;
    total_impact: number;
}

interface DailyMetric {
    total_commits: number;
    total_impact: number;
}

interface LiveCommit {
    id: number;
    message: string;
    repository: string;
    impact_score: number;
    committed_at: string;
}

export function useRealtimeMetrics(initialMetrics: DailyMetric) {
    const todayCommits = ref(initialMetrics.total_commits);
    const todayImpact = ref(initialMetrics.total_impact);
    const liveCommits = ref<LiveCommit[]>([]);
    const isConnected = ref(false);

    onMounted(() => {
        const userId = usePage().props.auth.user.id;

        window.Echo.private(`user.${userId}`)
            .listen('CommitProcessed', (e: CommitProcessedEvent) => {
                todayCommits.value++;
                todayImpact.value += e.impact_score;
                
                // Add to live commits feed (prepend for newest first)
                liveCommits.value.unshift({
                    id: e.commit_id,
                    message: e.message,
                    repository: e.repository,
                    impact_score: e.impact_score,
                    committed_at: e.committed_at,
                });
                
                // Keep only last 20 live commits
                if (liveCommits.value.length > 20) {
                    liveCommits.value.pop();
                }
            })
            .listen('DailyMetricsUpdated', (e: DailyMetricsEvent) => {
                todayCommits.value = e.total_commits;
                todayImpact.value = e.total_impact;
            });
        
        // Track connection status
        window.Echo.connector.pusher.connection.bind('connected', () => {
            isConnected.value = true;
        });
        
        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            isConnected.value = false;
        });
        
        // Set initial connection status
        isConnected.value = window.Echo.connector.pusher.connection.state === 'connected';
    });

    onUnmounted(() => {
        const userId = usePage().props.auth.user.id;
        window.Echo.leave(`user.${userId}`);
    });

    return { todayCommits, todayImpact, liveCommits, isConnected };
}
```

**Broadcasting Events**
```php
// app/Events/CommitProcessed.php
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
            'message' => $this->commit->message,
            'repository' => $this->commit->repository->name,
            'impact_score' => $this->impact_score,
            'committed_at' => $this->commit->committed_at->toISOString(),
        ];
    }
}
```

```php
// routes/channels.php
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

### Scheduled Tasks (Cron via Forge)

```
* * * * * cd /home/forge/gitpulse.app && php artisan schedule:run >> /dev/null 2>&1
```

**Laravel Scheduler Commands:**
```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Aggregate daily metrics at midnight user's timezone
    $schedule->command('metrics:aggregate-daily')
        ->dailyAt('00:05')
        ->withoutOverlapping();
    
    // Generate weekly reports every Friday at 5 PM UTC
    $schedule->command('reports:generate-weekly')
        ->weeklyOn(5, '17:00')
        ->withoutOverlapping();
    
    // Generate AI insights every Monday
    $schedule->command('insights:generate')
        ->weeklyOn(1, '06:00')
        ->withoutOverlapping();
    
    // Clean up old Redis counters
    $schedule->command('redis:cleanup-counters')
        ->dailyAt('03:00');
    
    // Horizon snapshot for metrics
    $schedule->command('horizon:snapshot')
        ->everyFiveMinutes();
}
```

---

*End of Document*
