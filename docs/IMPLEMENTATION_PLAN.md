# GitPulse Implementation Plan

**Version:** 1.0
**Created:** January 17, 2026
**PRD Reference:** [GitPulse_PRD_v2.4_FINAL.md](./GitPulse_PRD_v2.4_FINAL.md)
**Project Management:** [PROJECT_MANAGEMENT.md](./PROJECT_MANAGEMENT.md)

---

## Executive Summary

This document provides a detailed sprint-by-sprint implementation plan for GitPulse, a developer productivity analytics platform. The plan is structured into 4 phases spanning 14+ weeks, with clear deliverables, dependencies, and acceptance criteria for each sprint.

---

## Current Project State

### Completed (Sprint 0 - Partial)
- [x] Laravel 12 project initialized
- [x] Inertia.js v2 + Vue.js 3 configured
- [x] Tailwind CSS v4 with `@tailwindcss/vite` plugin
- [x] Laravel Fortify (login, register, 2FA)
- [x] Base UI components (shadcn/ui style with Reka UI)
- [x] PestPHP v4 testing framework
- [x] Laravel Pint code formatter
- [x] Laravel Wayfinder route generation
- [x] Basic settings pages (profile, password, 2FA)
- [x] SQLite database (development)

### Pending Installation (Sprint 0 - Remaining)
- [ ] Laravel Socialite (GitHub OAuth)
- [ ] Spatie Laravel Webhook Client
- [ ] Laravel Horizon (Redis queues)
- [ ] Laravel Reverb (WebSockets)
- [ ] Laravel DomPDF (PDF export)
- [ ] Anthropic PHP SDK (AI insights)
- [ ] Larastan (static analysis)
- [ ] Rector (automated refactoring)
- [ ] Chart.js + vue-chartjs (visualizations)
- [ ] Laravel Echo + pusher-js (real-time frontend)
- [ ] date-fns (date manipulation)
- [ ] Husky + lint-staged (pre-commit hooks)

---

## Phase 1: MVP Foundation (Weeks 1-6)

### Sprint 1: Infrastructure & Authentication (Week 1-2)

**Goal:** Complete project scaffolding and GitHub OAuth integration

#### Tasks

##### Day 1-2: Dependencies & Tooling
```bash
# Backend dependencies
composer require laravel/socialite
composer require spatie/laravel-webhook-client
composer require laravel/horizon
composer require laravel/reverb
composer require barryvdh/laravel-dompdf
composer require spatie/laravel-data
composer require spatie/laravel-query-builder

# Dev dependencies
composer require --dev larastan/larastan
composer require --dev rector/rector

# Frontend dependencies
npm install chart.js vue-chartjs date-fns laravel-echo pusher-js lodash-es
npm install -D husky lint-staged
```

##### Day 2-3: Code Quality Setup
| Task | File | Command |
|------|------|---------|
| Configure Larastan | `phpstan.neon` | Level 8 |
| Configure Rector | `rector.php` | Laravel + PHP 8.3 sets |
| Configure Husky | `.husky/pre-commit` | lint-staged |
| Update Pint config | `pint.json` | Per PRD spec |

##### Day 3-4: GitHub OAuth
| Task | Description |
|------|-------------|
| Create GitHub OAuth App | Developer settings on GitHub |
| Add Socialite config | `config/services.php` |
| Create `GitHubController` | OAuth callback handling |
| Update User migration | Add `github_id`, `github_username`, `github_token`, `avatar_url`, `preferences`, `timezone` |
| Create OAuth routes | `routes/web.php` |
| Update UserFactory | Include GitHub fields |

##### Day 5: Auth UI Updates
| Task | File |
|------|------|
| Add "Sign in with GitHub" button | `Login.vue` |
| Add "Connect GitHub" to Register | `Register.vue` |
| Show GitHub status in Settings | `Settings/Profile.vue` |

##### Day 6-7: Testing & Quality
| Test File | Coverage |
|-----------|----------|
| `tests/Feature/Auth/GitHubOAuthTest.php` | OAuth flow, token storage |
| `tests/Feature/Auth/LoginTest.php` | Email + GitHub login |
| `tests/Unit/UserTest.php` | User model, GitHub attributes |

**Sprint 1 Deliverables:**
- [ ] All dependencies installed and configured
- [ ] GitHub OAuth working end-to-end
- [ ] Code quality tools passing (Pint, Larastan Level 8)
- [ ] 80%+ test coverage on new code

---

### Sprint 2: Core Data Models (Week 3)

**Goal:** Create Repository and Commit models with webhook infrastructure

#### Database Schema

##### Repository Model
```php
Schema::create('repositories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('github_id')->index();
    $table->string('name');
    $table->string('full_name');
    $table->string('webhook_id')->nullable();
    $table->string('webhook_secret', 64)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_sync_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'github_id']);
});
```

##### Commit Model
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
    $table->unsignedInteger('files_changed')->default(0);
    $table->json('files')->nullable();
    $table->string('commit_type', 20)->default('other');
    $table->decimal('impact_score', 5, 2)->default(0);
    $table->json('external_refs')->nullable();
    $table->boolean('is_merge')->default(false);
    $table->timestamps();

    $table->index(['user_id', 'committed_at']);
    $table->index(['repository_id', 'committed_at']);
});
```

##### CommitType Enum
```php
enum CommitType: string
{
    case FEAT = 'feat';
    case FIX = 'fix';
    case DOCS = 'docs';
    case STYLE = 'style';
    case REFACTOR = 'refactor';
    case PERF = 'perf';
    case TEST = 'test';
    case BUILD = 'build';
    case CI = 'ci';
    case CHORE = 'chore';
    case REVERT = 'revert';
    case OTHER = 'other';
}
```

#### Tasks

| Day | Task | Files |
|-----|------|-------|
| 1 | Create migrations | `database/migrations/` |
| 1 | Create Eloquent models | `app/Models/Repository.php`, `app/Models/Commit.php` |
| 1 | Create CommitType enum | `app/Enums/CommitType.php` |
| 2 | Create model factories | `database/factories/` |
| 2 | Create seeders | `database/seeders/` |
| 3 | Create Spatie Data DTOs | `app/Data/` |
| 3-4 | Write model tests | `tests/Unit/Models/` |
| 4-5 | Create relationships & scopes | Model files |

**Sprint 2 Deliverables:**
- [ ] Repository and Commit models with full relationships
- [ ] CommitType enum with all types
- [ ] Factories and seeders for test data
- [ ] Unit tests for models and relationships

---

### Sprint 3: Webhook Integration - F1 (Week 4)

**Goal:** Implement GitHub webhook receiving and processing

#### Architecture

```
GitHub Push → /api/webhooks/github → GitHubSignatureValidator
                                   → GitHubWebhookProfile
                                   → ProcessGitHubWebhook (queued)
                                   → ProcessGitHubPush (per commit)
```

#### Tasks

| Day | Task | Files |
|-----|------|-------|
| 1 | Configure Spatie Webhook Client | `config/webhook-client.php` |
| 1 | Create signature validator | `app/Webhooks/GitHub/GitHubSignatureValidator.php` |
| 1 | Create webhook profile | `app/Webhooks/GitHub/GitHubWebhookProfile.php` |
| 2 | Create main webhook job | `app/Webhooks/GitHub/ProcessGitHubWebhook.php` |
| 2 | Create push processing job | `app/Jobs/ProcessGitHubPush.php` |
| 2 | Create PR processing job | `app/Jobs/ProcessPullRequest.php` |
| 3 | Set up Horizon config | `config/horizon.php` |
| 3 | Create webhook routes | `routes/webhooks.php` |
| 4 | Create GitHubService | `app/Services/GitHub/GitHubService.php` |
| 4 | Create WebhookService | `app/Services/GitHub/WebhookService.php` |
| 5 | Write webhook tests | `tests/Feature/Webhooks/` |

#### Test Scenarios
1. Valid webhook with correct signature → 200 OK, job dispatched
2. Invalid signature → 500, no processing
3. Unsupported event type → 200 OK, ignored
4. Inactive repository → Job exits gracefully
5. Duplicate commit SHA → Skipped
6. Multiple commits in push → Multiple jobs dispatched

**Sprint 3 Deliverables:**
- [ ] Webhook endpoint receiving GitHub events
- [ ] Signature validation working
- [ ] Push and PR events processed via queue
- [ ] Comprehensive webhook tests

---

### Sprint 4: Commit Documentation Engine - F2 (Week 5)

**Goal:** Parse commits, categorize them, and calculate impact scores

#### Actions

| Action | Purpose | Location |
|--------|---------|----------|
| `ParseCommitMessage` | Extract type, scope, description, refs from conventional commits | `app/Actions/Commits/` |
| `CategorizeCommit` | NLP-based categorization for non-conventional commits | `app/Actions/Commits/` |
| `CalculateImpactScore` | Calculate weighted impact score | `app/Actions/Commits/` |

#### Impact Score Formula
```
Score = Σ(weight × factor_score) × 10

Factors:
- Lines changed (20%): min((additions + deletions) / repo_avg, 2.0)
- Files touched (15%): min(files_changed / 5, 1.5)
- Commit type (25%): feat=1.0, fix=0.8, refactor=0.7, perf=0.7, test=0.6, docs=0.5, style=0.3, chore=0.3
- Merge commit (20%): merge=1.5, regular=0.5
- External refs (10%): has_refs=1.0, no_refs=0.5
- Focus time (10%): peak_hours=1.2, normal=1.0, late=0.8
```

#### Tasks

| Day | Task | Files |
|-----|------|-------|
| 1 | Create ParseCommitMessage action | `app/Actions/Commits/ParseCommitMessage.php` |
| 1 | Create ParsedCommitData DTO | `app/Data/ParsedCommitData.php` |
| 2 | Create CategorizeCommit action | `app/Actions/Commits/CategorizeCommit.php` |
| 2 | Define keyword mappings | Within CategorizeCommit |
| 3 | Create CalculateImpactScore action | `app/Actions/Commits/CalculateImpactScore.php` |
| 4 | Integrate with ProcessGitHubPush job | Update job |
| 5 | Write comprehensive unit tests | `tests/Unit/Actions/` |

#### Test Scenarios
1. Conventional commit parsing (100% accuracy required)
2. Non-conventional commit categorization (80%+ accuracy)
3. Issue reference extraction (#123, JIRA-456, LINEAR-789)
4. Impact score boundaries (0-10+ range)
5. Type weight ordering (feat > fix > refactor > ... > chore)

**Sprint 4 Deliverables:**
- [ ] Commit message parser with conventional commit support
- [ ] NLP-based categorization fallback
- [ ] Impact score calculator with configurable weights
- [ ] Unit tests with 90%+ coverage

---

### Sprint 5: Daily Dashboard - F3 (Week 6 - Part 1)

**Goal:** Build the primary dashboard interface with real-time updates

#### Data Model

##### DailyMetric Model
```php
Schema::create('daily_metrics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('date');
    $table->unsignedInteger('total_commits')->default(0);
    $table->decimal('total_impact', 8, 2)->default(0);
    $table->unsignedInteger('repos_active')->default(0);
    $table->unsignedInteger('hours_active')->default(0);
    $table->json('commit_types')->nullable();
    $table->json('hourly_distribution')->nullable();
    $table->unsignedInteger('additions')->default(0);
    $table->unsignedInteger('deletions')->default(0);
    $table->timestamps();

    $table->unique(['user_id', 'date']);
    $table->index(['user_id', 'date']);
});
```

#### Backend Tasks

| Day | Task | Files |
|-----|------|-------|
| 1 | Create DailyMetric migration & model | `database/migrations/`, `app/Models/` |
| 1 | Create MetricsService | `app/Services/Analytics/MetricsService.php` |
| 2 | Create CalculateDailyMetrics job | `app/Jobs/CalculateDailyMetrics.php` |
| 2 | Create DashboardController | `app/Http/Controllers/DashboardController.php` |
| 3 | Set up Laravel Reverb | `config/reverb.php`, `config/broadcasting.php` |
| 3 | Create CommitProcessed event | `app/Events/CommitProcessed.php` |
| 3 | Create DailyMetricsUpdated event | `app/Events/DailyMetricsUpdated.php` |
| 4 | Configure channel authorization | `routes/channels.php` |

#### Frontend Tasks

| Day | Task | Files |
|-----|------|-------|
| 4 | Set up Laravel Echo | `resources/js/bootstrap.ts` |
| 4 | Create useRealtime composable | `resources/js/Composables/useRealtime.ts` |
| 5 | Create Dashboard.vue page | `resources/js/Pages/Dashboard.vue` |
| 5-6 | Create MetricCard component | `resources/js/Components/Dashboard/MetricCard.vue` |
| 5-6 | Create CommitList component | `resources/js/Components/Dashboard/CommitList.vue` |
| 5-6 | Create TodayVsYesterday component | `resources/js/Components/Dashboard/TodayVsYesterday.vue` |
| 6 | Create LiveCommitFeed component | `resources/js/Components/Dashboard/LiveCommitFeed.vue` |
| 6 | Create StreakBadge component | `resources/js/Components/Dashboard/StreakBadge.vue` |

**Sprint 5 Deliverables:**
- [ ] DailyMetric model with aggregation logic
- [ ] MetricsService for dashboard data
- [ ] Real-time WebSocket setup with Reverb
- [ ] Basic dashboard UI rendering

---

### Sprint 6: Dashboard & Charts (Week 6 - Part 2)

**Goal:** Complete dashboard with charts and weekly report foundation

#### Chart Components

| Component | Type | Library |
|-----------|------|---------|
| CommitTimeline | Line/Bar | Chart.js |
| TypeBreakdown | Pie/Doughnut | Chart.js |
| RepoBreakdown | Pie/Doughnut | Chart.js |

#### Tasks

| Day | Task | Files |
|-----|------|-------|
| 1 | Create useCharts composable | `resources/js/Composables/useCharts.ts` |
| 1-2 | Create CommitTimeline chart | `resources/js/Components/Charts/CommitTimeline.vue` |
| 2 | Create TypeBreakdown chart | `resources/js/Components/Charts/TypeBreakdown.vue` |
| 2 | Create RepoBreakdown chart | `resources/js/Components/Charts/RepoBreakdown.vue` |
| 3 | Integrate charts into Dashboard | `Dashboard.vue` |
| 3 | Add date range filtering | Dashboard components |
| 4-5 | Write dashboard feature tests | `tests/Feature/DashboardTest.php` |

**Sprint 6 Deliverables:**
- [ ] Complete dashboard with all metric cards
- [ ] Interactive charts (timeline, breakdowns)
- [ ] Real-time commit feed working
- [ ] Feature tests for dashboard

---

## Phase 2: Reports & Analytics (Weeks 7-10)

### Sprint 7: Weekly Report Generator - F4 (Week 7-8)

**Goal:** Automated weekly report generation with PDF export

#### Data Model

##### WeeklyReport Model
```php
Schema::create('weekly_reports', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('week_start');
    $table->date('week_end');
    $table->json('summary_stats');
    $table->json('accomplishments');
    $table->json('trends');
    $table->json('insights')->nullable();
    $table->text('user_notes')->nullable();
    $table->string('pdf_path')->nullable();
    $table->string('status')->default('draft');
    $table->timestamp('generated_at')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'week_start']);
});
```

#### Tasks

| Day | Task |
|-----|------|
| 1-2 | Create WeeklyReport model, migration, factory |
| 2-3 | Create GenerateWeeklyReport action |
| 3-4 | Create GenerateWeeklyReportJob (scheduled) |
| 4-5 | Create ExportReportToPdf action (DomPDF) |
| 5-6 | Create PDF Blade template |
| 6-7 | Create ReportController |
| 7-8 | Create Reports pages (Index, Show, Edit) |
| 9-10 | Write feature tests |

**Sprint 7 Deliverables:**
- [ ] Automated weekly report generation
- [ ] PDF export with professional template
- [ ] Markdown export
- [ ] Report editing UI
- [ ] Email delivery option

---

### Sprint 8: Comparative Analytics - F5 (Week 9-10)

**Goal:** Trend visualization and anomaly detection

#### Tasks

| Day | Task |
|-----|------|
| 1-2 | Create ComparisonService |
| 2-3 | Implement WoW, MoM calculations |
| 3-4 | Implement anomaly detection |
| 4-5 | Create HeatmapCalendar component |
| 5-6 | Create ImpactTrend chart |
| 6-7 | Create DateRangePicker component |
| 7-8 | Create Analytics page |
| 9-10 | Write feature tests |

**Sprint 8 Deliverables:**
- [ ] ComparisonService with trend calculations
- [ ] Anomaly detection (>2 std dev)
- [ ] GitHub-style heatmap calendar
- [ ] Analytics page with date filtering

---

## Phase 3: AI & Integrations (Weeks 11-14)

### Sprint 9: AI-Powered Insights - F6 (Week 11-12)

**Goal:** Claude API integration for intelligent insights

#### Tasks

| Day | Task |
|-----|------|
| 1-2 | Install Anthropic PHP SDK |
| 2-3 | Create ClaudeInsightService |
| 3-4 | Define insight prompt templates |
| 4-5 | Create Insight model and migration |
| 5-6 | Create GenerateInsightsJob |
| 6-7 | Create Insights page and InsightCard component |
| 7-8 | Implement feedback system (helpful/not helpful) |
| 9-10 | Write feature tests |

**Sprint 9 Deliverables:**
- [ ] Claude API integration working
- [ ] Weekly insight generation
- [ ] Insight display with confidence scores
- [ ] User feedback mechanism

---

### Sprint 10: Notifications & Repository Management (Week 13-14)

**Goal:** Complete repository management UI and notification system

#### Tasks

| Day | Task |
|-----|------|
| 1-3 | Create Repository management pages |
| 3-5 | Implement repository connection flow (webhook setup) |
| 5-7 | Email notification system (weekly digest) |
| 7-8 | Slack webhook integration |
| 8-10 | Polish and integration testing |

**Sprint 10 Deliverables:**
- [ ] Repository connect/disconnect UI
- [ ] Email weekly digest
- [ ] Slack notification option
- [ ] End-to-end integration tests

---

## Phase 4: Polish & Launch Prep (Weeks 15+)

### Sprint 11+: Future Features (P1)

| Feature | Sprint | Priority |
|---------|--------|----------|
| F7: Calendar Integration | Week 15-16 | P1 |
| F8: Team Dashboard | Week 17-18 | P1 |
| F9: Goal Setting & Tracking | Week 19-20 | P1 |
| F10: Additional Integrations | Week 21+ | P1 |

---

## Quality Gates

### Per-Sprint Quality Requirements

| Check | Requirement | Command |
|-------|-------------|---------|
| Code Style | Pint clean | `./vendor/bin/pint --test` |
| Static Analysis | Larastan Level 8, zero errors | `./vendor/bin/phpstan analyse` |
| Test Coverage | 80% minimum | `php artisan test --coverage --min=80` |
| Type Coverage | 90% minimum | `./vendor/bin/pest --type-coverage --min=90` |

### MVP Quality Gate (End of Phase 1)

- [ ] All P0 features (F1-F4) complete
- [ ] 80% code coverage
- [ ] 90% type coverage
- [ ] PHPStan Level 8 passing
- [ ] Performance: Dashboard < 2s load time
- [ ] Performance: Webhook processing < 5s
- [ ] 10 beta users validated

---

## Risk Mitigation

| Risk | Mitigation | Status |
|------|------------|--------|
| GitHub API rate limits | Use webhooks, cache responses | Planned |
| Webhook reliability | Retry logic, manual resync | Planned |
| AI hallucination | Validate against data, confidence scores | Planned |
| Scope creep | Strict MVP focus, phase gating | Active |

---

## Recommended Execution Order

### Week 1-2: Sprint 1 (Infrastructure & Auth)
**Why first:** Everything depends on authenticated users with GitHub connections.

### Week 3: Sprint 2 (Data Models)
**Why second:** Webhooks and processing need Repository and Commit models.

### Week 4: Sprint 3 (Webhooks)
**Why third:** This is the data ingestion layer; dashboards need data.

### Week 5: Sprint 4 (Documentation Engine)
**Why fourth:** Commit parsing enables meaningful analytics and reports.

### Week 6: Sprints 5-6 (Dashboard)
**Why fifth:** Users need to see their data; validates the pipeline works.

### Week 7-8: Sprint 7 (Weekly Reports)
**Why sixth:** Core value proposition; stakeholder communication.

### Week 9-10: Sprint 8 (Comparative Analytics)
**Why seventh:** Enhances dashboard with trends and historical context.

### Week 11-14: Sprints 9-10 (AI & Polish)
**Why last in MVP:** Enhances value but not blocking for beta launch.

---

## Next Actions

1. **Immediate:** Begin Sprint 1 - Install remaining dependencies
2. **Day 1:** Set up Larastan and Rector configurations
3. **Day 2-3:** Implement GitHub OAuth with Socialite
4. **Day 4-5:** Update User model and create migrations
5. **Week 1 End:** All code quality tools passing, GitHub OAuth working
