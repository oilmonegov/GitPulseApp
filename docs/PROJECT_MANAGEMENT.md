# GitPulse Project Management

**Last Updated:** January 16, 2026
**Project Status:** Pre-Development (Scaffolding Phase)
**PRD Reference:** [GitPulse_PRD_v2.4_FINAL.md](./GitPulse_PRD_v2.4_FINAL.md)

---

## Quick Navigation

- [Project Overview](#project-overview)
- [Current Sprint](#current-sprint)
- [Feature Backlog](#feature-backlog)
- [Phase 1: MVP](#phase-1-mvp-weeks-1-6)
- [Phase 2: Analytics](#phase-2-analytics--insights-weeks-7-10)
- [Phase 3: Scale](#phase-3-scale--polish-weeks-11-14)
- [Phase 4: Growth](#phase-4-growth-weeks-15)
- [Technical Debt](#technical-debt)
- [Blockers & Risks](#blockers--risks)

---

## Project Overview

GitPulse is a developer productivity analytics platform that transforms Git commit activity into actionable insights through GitHub webhooks, comparative analytics, and AI-powered weekly reports.

### Tech Stack
| Layer | Technology |
|-------|------------|
| Backend | Laravel 12.x (PHP 8.3+) |
| Frontend | Vue.js 3.5 + TypeScript + Inertia.js 2.0 |
| Styling | Tailwind CSS 4.0 |
| Database | MySQL 8.x (partitioned tables) |
| Cache/Queue | Redis 7.x + Laravel Horizon |
| Real-time | Laravel Reverb (WebSockets) |
| Auth | Laravel Fortify + GitHub OAuth |
| Webhooks | Spatie Laravel Webhook Client |
| AI | Claude API (Anthropic PHP SDK) |
| PDF | Laravel DomPDF |

### Success Metrics
- 90% reduction in time spent on status updates
- Users identify 3+ actionable insights within first month
- 15% improvement in self-reported productivity after 8 weeks
- 80% weekly active user retention after 30 days

---

## Current Sprint

### Sprint 0: Project Scaffolding
**Goal:** Initialize Laravel 12 project with all dependencies and tooling

| Task | Status | Assignee | Notes |
|------|--------|----------|-------|
| Create Laravel 12 project | `pending` | - | `composer create-project laravel/laravel` |
| Install PHP dependencies | `pending` | - | See composer.json in PRD |
| Install NPM dependencies | `pending` | - | See package.json in PRD |
| Configure Tailwind CSS v4 | `pending` | - | CSS-first config with @theme |
| Configure Vite | `pending` | - | vite.config.ts |
| Set up PestPHP | `pending` | - | tests/Pest.php |
| Set up Laravel Pint | `pending` | - | pint.json |
| Set up Larastan | `pending` | - | phpstan.neon (Level 8) |
| Set up Rector | `pending` | - | rector.php |
| Configure GitHub Actions CI | `pending` | - | .github/workflows/ci.yml |
| Set up Husky pre-commit hooks | `pending` | - | lint-staged config |
| Create .env.example | `pending` | - | All required env vars |

---

## Feature Backlog

### P0: Core Features (MVP)

| ID | Feature | PRD Section | Status | Sprint |
|----|---------|-------------|--------|--------|
| F1 | GitHub Webhook Integration | F1 | `not_started` | Week 3-4 |
| F2 | Commit Documentation Engine | F2 | `not_started` | Week 3-4 |
| F3 | Daily Dashboard | F3 | `not_started` | Week 5-6 |
| F4 | Weekly Report Generator | F4 | `not_started` | Week 5-6 |
| F5 | Comparative Analytics | F5 | `not_started` | Week 7-8 |

### P1: Enhanced Features (Post-MVP)

| ID | Feature | PRD Section | Status | Sprint |
|----|---------|-------------|--------|--------|
| F6 | AI-Powered Performance Insights | F6 | `not_started` | Week 9-10 |
| F7 | Calendar Integration | F7 | `not_started` | Week 11-12 |
| F8 | Team Dashboard | F8 | `not_started` | Week 13-14 |
| F9 | Goal Setting & Tracking | F9 | `not_started` | Week 13-14 |
| F10 | Integrations (Slack, Linear, Notion) | F10 | `not_started` | Week 11-12 |

### P2: Future Considerations

| ID | Feature | Status |
|----|---------|--------|
| F11 | GitLab Support | `backlog` |
| F12 | Bitbucket Support | `backlog` |
| F13 | VS Code Extension | `backlog` |
| F14 | Mobile App | `backlog` |
| F15 | Public API | `backlog` |
| F16 | Self-hosted Enterprise | `backlog` |

---

## Phase 1: MVP (Weeks 1-6)

### Week 1-2: Foundation & Authentication

#### Laravel Project Setup
- [ ] Initialize Laravel 12 project
- [ ] Configure app settings (config/app.php)
- [ ] Set up environment variables (.env)

#### Frontend Tooling
- [ ] Install Vue.js 3.5 + TypeScript
- [ ] Configure Inertia.js 2.0 with SSR
- [ ] Set up Tailwind CSS v4 with `@tailwindcss/vite` plugin
- [ ] Create custom theme in `resources/css/app.css` using `@theme` directive
- [ ] Install Headless UI component library
- [ ] Install Heroicons

#### Authentication (Laravel Fortify + Socialite)
- [ ] Install Laravel Fortify
- [ ] Configure Fortify features (login, register, password reset, 2FA)
- [ ] Install Laravel Socialite
- [ ] Create GitHub OAuth app
- [ ] Implement GitHub OAuth callback controller
- [ ] Create User migration with GitHub fields:
  - `github_id` (unique)
  - `github_username`
  - `github_token` (encrypted)
  - `avatar_url`
  - `preferences` (JSON)
  - `timezone`

#### Base UI Components
- [ ] Create `AuthenticatedLayout.vue`
- [ ] Create `Sidebar.vue` navigation
- [ ] Create `Button.vue` component
- [ ] Create `Card.vue` component
- [ ] Create `InputField.vue` component
- [ ] Create Auth pages:
  - [ ] `Login.vue`
  - [ ] `Register.vue`
  - [ ] `ForgotPassword.vue`
- [ ] Create `Settings.vue` page

#### Code Quality Setup
- [ ] Configure PestPHP (`tests/Pest.php`)
- [ ] Configure Laravel Pint (`pint.json`)
- [ ] Configure Larastan (`phpstan.neon`) at Level 8
- [ ] Configure Rector (`rector.php`)
- [ ] Set up GitHub Actions CI/CD pipeline
- [ ] Configure Husky + lint-staged pre-commit hooks
- [ ] Create UserFactory

#### Testing (Week 1-2)
- [ ] Auth feature tests (login, register, logout)
- [ ] GitHub OAuth feature tests
- [ ] Settings page feature tests

---

### Week 3-4: Core Data Pipeline

#### Models & Migrations
- [ ] Create Repository model and migration:
  - `user_id` (FK)
  - `github_id`
  - `name`, `full_name`
  - `webhook_id`, `webhook_secret`
  - `is_active`
  - `last_sync_at`
- [ ] Create Commit model with partitioned migration:
  - `repository_id` (FK)
  - `user_id` (FK)
  - `sha` (unique, 40 chars)
  - `message`
  - `author_name`, `author_email`
  - `committed_at`
  - `additions`, `deletions`, `files_changed`
  - `files` (JSON)
  - `commit_type` (enum)
  - `impact_score`
  - `external_refs` (JSON)
  - `is_merge`
- [ ] Create CommitType enum
- [ ] Set up MySQL partitioning by month on `committed_at`

#### Spatie Webhook Client Setup
- [ ] Install `spatie/laravel-webhook-client`
- [ ] Configure `config/webhook-client.php`
- [ ] Create `GitHubSignatureValidator` (HMAC-SHA256)
- [ ] Create `GitHubWebhookProfile` (event filtering)
- [ ] Create `ProcessGitHubWebhook` job class
- [ ] Register webhook routes (`routes/webhooks.php`)

#### Queue Jobs
- [ ] Set up Laravel Horizon (`config/horizon.php`)
- [ ] Create `ProcessGitHubPush` job
- [ ] Create `ProcessPullRequest` job

#### Actions (Domain Logic)
- [ ] Create `ParseCommitMessage` action (Conventional Commits parser)
- [ ] Create `CategorizeCommit` action (NLP fallback for non-conventional)
- [ ] Create `CalculateImpactScore` action with weights:
  - Lines changed: 20%
  - Files touched: 15%
  - Commit type: 25%
  - Merge status: 20%
  - External refs: 10%
  - Focus time: 10%

#### Services
- [ ] Create `GitHubService` for API interactions
- [ ] Create `WebhookService` for webhook management

#### Repository Management UI
- [ ] Create `Repositories/Index.vue` page
- [ ] Implement repository connection flow
- [ ] Implement repository disconnection
- [ ] Show webhook status per repository

#### Testing (Week 3-4)
- [ ] Create RepositoryFactory
- [ ] Create CommitFactory
- [ ] Unit tests for `CalculateImpactScore`
- [ ] Unit tests for `ParseCommitMessage`
- [ ] Unit tests for `CategorizeCommit`
- [ ] Feature tests for GitHub webhook (valid signature)
- [ ] Feature tests for GitHub webhook (invalid signature)
- [ ] Feature tests for repository management

---

### Week 5-6: Dashboard & Reports

#### Daily Metrics
- [ ] Create DailyMetric model and migration:
  - `user_id` (FK)
  - `date`
  - `total_commits`
  - `total_impact`
  - `repos_active`
  - `hours_active`
  - `commit_types` (JSON)
  - `hourly_distribution` (JSON)
  - `additions`, `deletions`
- [ ] Create `CalculateDailyMetrics` job (scheduled)
- [ ] Create `MetricsService` for dashboard data

#### Real-time with Laravel Reverb
- [ ] Install Laravel Reverb
- [ ] Configure `config/reverb.php`
- [ ] Configure `config/broadcasting.php`
- [ ] Create `CommitProcessed` event (ShouldBroadcast)
- [ ] Create `DailyMetricsUpdated` event
- [ ] Set up Laravel Echo in `bootstrap.ts`
- [ ] Create `useRealtime.ts` composable
- [ ] Configure channel authorization (`routes/channels.php`)

#### Dashboard Page
- [ ] Create `DashboardController`
- [ ] Create `Dashboard.vue` page
- [ ] Create dashboard components:
  - [ ] `MetricCard.vue`
  - [ ] `CommitList.vue`
  - [ ] `TodayVsYesterday.vue`
  - [ ] `LiveCommitFeed.vue` (real-time)
  - [ ] `StreakBadge.vue`
- [ ] Create chart components:
  - [ ] `CommitTimeline.vue` (Chart.js line chart)
- [ ] Create `useMetrics.ts` composable
- [ ] Create `useCharts.ts` composable

#### Weekly Reports
- [ ] Create WeeklyReport model and migration:
  - `user_id` (FK)
  - `week_start`, `week_end`
  - `summary_stats` (JSON)
  - `accomplishments` (JSON)
  - `trends` (JSON)
  - `insights` (JSON)
  - `user_notes`
  - `pdf_path`
  - `status` (enum: draft, generated, sent)
  - `generated_at`, `sent_at`
- [ ] Create ReportStatus enum
- [ ] Create `GenerateWeeklyReportJob` (scheduled Friday)
- [ ] Create `ReportController`
- [ ] Install Laravel DomPDF
- [ ] Create PDF template (`resources/views/reports/pdf.blade.php`)
- [ ] Create report pages:
  - [ ] `Reports/Index.vue`
  - [ ] `Reports/Show.vue`
  - [ ] `Reports/Edit.vue`
- [ ] Implement PDF export endpoint
- [ ] Implement Markdown export endpoint

#### Testing (Week 5-6)
- [ ] Create DailyMetricFactory
- [ ] Create WeeklyReportFactory
- [ ] Feature tests for dashboard (authenticated)
- [ ] Feature tests for dashboard (guest redirect)
- [ ] Feature tests for report generation
- [ ] Feature tests for PDF export
- [ ] Unit tests for `MetricsService`
- [ ] Unit tests for day-over-day comparison

#### Quality Gates (End of Phase 1)
- [ ] **80% code coverage** (`php artisan test --coverage --min=80`)
- [ ] **90% type coverage** (`./vendor/bin/pest --type-coverage --min=90`)
- [ ] **PHPStan Level 8** (zero errors)
- [ ] **Pint clean** (`./vendor/bin/pint --test`)
- [ ] **Rector clean** (`./vendor/bin/rector process --dry-run`)

**Deliverable:** Working MVP for 10 beta users

---

## Phase 2: Analytics & Insights (Weeks 7-10)

### Week 7-8: Comparative Analytics

#### Services
- [ ] Create `ComparisonService` for WoW, MoM calculations
- [ ] Implement anomaly detection (significantly above/below average)
- [ ] Implement best/worst period identification

#### Chart Components
- [ ] Create `HeatmapCalendar.vue` (GitHub contribution graph style)
- [ ] Create `ImpactTrend.vue` (line chart with dual-axis)
- [ ] Create `TypeBreakdown.vue` (pie chart)
- [ ] Add sparklines to dashboard

#### UI Enhancements
- [ ] Create date range picker with presets
- [ ] Add repository filter to all pages
- [ ] Create analytics page with full visualizations

#### Data Backfill
- [ ] Create `SyncRepositoryCommits` job
- [ ] Implement historical commit import from GitHub API

#### Testing (Week 7-8)
- [ ] Unit tests for `ComparisonService`
- [ ] Feature tests for analytics page
- [ ] Feature tests for date filtering

---

### Week 9-10: AI-Powered Insights

#### Insight System
- [ ] Create Insight model and migration:
  - `user_id` (FK)
  - `type` (enum: productivity, pattern, recommendation, warning)
  - `period` (enum: daily, weekly, monthly)
  - `title`, `description`
  - `data_points` (JSON)
  - `confidence`
  - `is_read`, `is_dismissed`
  - `valid_from`, `valid_until`
- [ ] Create InsightType enum
- [ ] Install `anthropic/anthropic-php` SDK

#### AI Integration
- [ ] Create `ClaudeInsightService`
- [ ] Define insight prompt templates
- [ ] Create `GenerateInsightsJob` (weekly scheduled)
- [ ] Implement pattern detection algorithms:
  - Productivity peaks (best days/times)
  - Burnout signals (declining trends, erratic patterns)
  - Context switching impact

#### UI
- [ ] Create insight cards on dashboard
- [ ] Create `Insights.vue` page with history
- [ ] Implement insight feedback (helpful/not helpful)
- [ ] Create `InsightController`

#### Testing (Week 9-10)
- [ ] Create InsightFactory
- [ ] Unit tests for `ClaudeInsightService`
- [ ] Feature tests for insights page
- [ ] Feature tests for insight feedback

**Deliverable:** Analytics-enabled version for 100 users

---

## Phase 3: Scale & Polish (Weeks 11-14)

### Week 11-12: Integrations & Notifications

#### Slack Integration
- [ ] Create Slack webhook configuration UI
- [ ] Implement daily summary Slack notification
- [ ] Implement weekly report Slack notification

#### Email Notifications
- [ ] Configure Mailgun/Postmark
- [ ] Create email notification preferences UI
- [ ] Create weekly report email template
- [ ] Implement scheduled email delivery

#### Calendar Integration (Optional)
- [ ] Install `socialiteproviders/google` for Google Calendar
- [ ] Implement Google Calendar OAuth flow
- [ ] Create meeting time correlation analytics
- [ ] Install `socialiteproviders/microsoft` for Outlook (optional)

#### Push Notifications
- [ ] Implement web push notification support
- [ ] Create notification preferences

---

### Week 13-14: Team Features & Optimization

#### Team Features
- [ ] Create Team model (opt-in creation)
- [ ] Create team invitation system
- [ ] Create Team Dashboard with aggregated metrics
- [ ] Implement anonymizable individual contribution breakdown
- [ ] Create Goal model (weekly/monthly targets)
- [ ] Create goal progress tracking UI

#### Performance Optimization
- [ ] Implement query caching strategies
- [ ] Add eager loading to all queries
- [ ] Profile MySQL queries and add indexes
- [ ] Implement Redis cache warming
- [ ] Set up read replica for analytics queries

#### Load Testing
- [ ] Set up load testing environment
- [ ] Run load tests for webhook throughput (1000/min target)
- [ ] Run load tests for dashboard (< 2s target)
- [ ] Document horizontal scaling procedures

**Deliverable:** Production-ready v1.0

---

## Phase 4: Growth (Weeks 15+)

| Feature | Priority | Status |
|---------|----------|--------|
| GitLab integration | High | `backlog` |
| Bitbucket integration | Medium | `backlog` |
| Public API with rate limiting | High | `backlog` |
| Zapier integration | Medium | `backlog` |
| Mobile-responsive PWA | Medium | `backlog` |
| Self-hosted Enterprise edition | Low | `backlog` |
| Annual review generator | Medium | `backlog` |
| Team comparison features | Low | `backlog` |

---

## Technical Debt

| Item | Priority | Sprint | Notes |
|------|----------|--------|-------|
| - | - | - | No technical debt yet (greenfield project) |

---

## Blockers & Risks

| Risk | Likelihood | Impact | Mitigation | Status |
|------|------------|--------|------------|--------|
| GitHub API rate limits | Medium | High | Use webhooks over polling, implement caching | `monitoring` |
| Low webhook reliability | Medium | Medium | Implement retry logic, webhook resync | `mitigated` |
| Gaming the metrics | Medium | Medium | Impact scoring over raw counts | `mitigated` |
| Privacy concerns | Low | High | Clear data policy, minimal collection | `mitigated` |
| AI hallucination | Medium | Medium | Validate against data, user feedback | `monitoring` |
| Scope creep | High | Medium | Strict prioritization, MVP-first | `active` |

---

## Definition of Done

A task/feature is considered **done** when:

1. **Code Complete**
   - All acceptance criteria met
   - Code follows Laravel/Vue conventions
   - No `@todo` or `FIXME` comments left

2. **Quality Verified**
   - Passes Pint style check
   - Passes PHPStan Level 8
   - Passes Rector dry-run
   - Has unit/feature tests
   - Meets coverage thresholds (80% code, 90% type)

3. **Reviewed**
   - PR reviewed and approved
   - No unresolved comments

4. **Deployed**
   - CI/CD pipeline passes
   - Deployed to staging
   - Smoke tested

---

## Code Quality Commands

```bash
# Run all quality checks
composer quality

# Individual checks
./vendor/bin/pint --test          # Code style
./vendor/bin/phpstan analyse      # Static analysis
./vendor/bin/rector process --dry-run  # Refactoring
php artisan test --coverage --min=80   # Test coverage
./vendor/bin/pest --type-coverage --min=90  # Type coverage
```

---

## Environment Setup

See [PRD Appendix B](./GitPulse_PRD_v2.4_FINAL.md#appendix-b-environment-configuration) for complete environment variable reference.

---

## Meeting Notes

### Kickoff Meeting
- **Date:** TBD
- **Attendees:** TBD
- **Decisions:** TBD

---

## Changelog

| Date | Change | Author |
|------|--------|--------|
| 2026-01-16 | Initial project management document created | Claude |

---

## Next Actions

1. **Immediate:** Start Sprint 0 - Project Scaffolding
2. **This Week:** Complete Laravel 12 setup with all dependencies
3. **Next Week:** Begin Week 1-2 Foundation & Authentication tasks
