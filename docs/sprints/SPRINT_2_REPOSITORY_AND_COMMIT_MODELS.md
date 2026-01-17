# Sprint 2: Repository and Commit Models

**Status:** Completed
**Duration:** Sprint 2
**Date Completed:** 2026-01-17

## Overview

Sprint 2 established the core data models for GitPulse's productivity tracking: Repository and Commit models with comprehensive factories, enums, scopes, and relationships. This sprint laid the foundation for storing and querying Git activity data.

---

## Completed Tasks

### 1. Repository Model

#### Database Migration
- **File:** `database/migrations/2026_01_17_184536_create_repositories_table.php`
- Fields:
  - `user_id` (FK) - Owner of the repository
  - `github_id` - GitHub's unique repository ID
  - `name` - Repository name (e.g., "gitpulse")
  - `full_name` - Full name (e.g., "user/gitpulse")
  - `description` - Repository description (nullable)
  - `default_branch` - Default branch name
  - `language` - Primary programming language (nullable)
  - `webhook_id` - GitHub webhook ID (nullable)
  - `webhook_secret` - Webhook secret for signature verification (hidden)
  - `is_active` - Whether repository is actively tracked
  - `is_private` - Repository visibility
  - `last_sync_at` - Last webhook/API sync timestamp

#### Model Implementation
- **File:** `app/Models/Repository.php`
- Relationships:
  - `user()` - BelongsTo User
  - `commits()` - HasMany Commit
- Scopes:
  - `scopeActive()` - Filter active repositories
  - `scopePublic()` - Filter public repositories
  - `scopePrivate()` - Filter private repositories
  - `scopeWithWebhook()` - Filter repositories with webhooks
- Accessors:
  - `getGitHubUrlAttribute()` - Returns GitHub URL
  - `getTotalCommitsAttribute()` - Returns commit count
- Helper methods:
  - `hasWebhook()` - Check if webhook is configured
- Casts:
  - `is_active`, `is_private` as boolean
  - All dates as `immutable_datetime`
- Security:
  - `webhook_secret` hidden from JSON serialization

#### Factory
- **File:** `database/factories/RepositoryFactory.php`
- States:
  - `private()` - Private repository
  - `inactive()` - Inactive repository
  - `withWebhook()` - Repository with webhook configured
  - `synced()` - Recently synced repository
  - `withLanguage(string)` - Specific programming language
  - `laravel()` - Laravel project preset

---

### 2. Commit Model

#### Database Migration
- **File:** `database/migrations/2026_01_17_184537_create_commits_table.php`
- Fields:
  - `repository_id` (FK) - Parent repository
  - `user_id` (FK) - Commit author (GitPulse user)
  - `sha` - Commit SHA (40 characters, unique)
  - `message` - Full commit message
  - `author_name` - Git author name
  - `author_email` - Git author email
  - `committed_at` - Commit timestamp
  - `additions` - Lines added
  - `deletions` - Lines deleted
  - `files_changed` - Number of files changed
  - `files` - JSON array of file details
  - `commit_type` - CommitType enum value
  - `scope` - Commit scope (from conventional commits)
  - `impact_score` - Calculated productivity impact (decimal 8,2)
  - `external_refs` - JSON array of issue/PR references
  - `is_merge` - Whether this is a merge commit
  - `url` - Direct URL to commit on GitHub

#### Model Implementation
- **File:** `app/Models/Commit.php`
- Relationships:
  - `repository()` - BelongsTo Repository
  - `user()` - BelongsTo User
- Scopes:
  - `scopeOfType(CommitType)` - Filter by commit type
  - `scopeMerge()` - Filter merge commits
  - `scopeNonMerge()` - Filter non-merge commits
  - `scopeOnDate(Carbon)` - Filter by specific date
  - `scopeBetweenDates(Carbon, Carbon)` - Filter by date range
  - `scopeHighImpact(float)` - Filter by impact score threshold
  - `scopeRecent()` - Order by most recent
- Accessors:
  - `getShortShaAttribute()` - First 7 characters of SHA
  - `getTotalLinesChangedAttribute()` - additions + deletions
  - `getTitleAttribute()` - First line of commit message
  - `getBodyAttribute()` - Rest of commit message
  - `getGitHubUrlAttribute()` - URL to commit on GitHub
- Helper methods:
  - `hasExternalRefs()` - Check for issue/PR references
- Casts:
  - `committed_at`, `created_at`, `updated_at` as `immutable_datetime`
  - `files`, `external_refs` as `array`
  - `commit_type` as `CommitType::class`
  - `impact_score` as `decimal:2`
  - `is_merge` as `boolean`

#### Factory
- **File:** `database/factories/CommitFactory.php`
- Realistic commit message templates for each type
- States:
  - `merge()` - Merge commit
  - `feature()` - Feature commit
  - `fix()` - Bug fix commit
  - `docs()` - Documentation commit
  - `refactor()` - Refactor commit
  - `test()` - Test commit
  - `chore()` - Chore commit
  - `highImpact()` - High impact score
  - `lowImpact()` - Low impact score
  - `committedOn(Carbon)` - Specific date
  - `today()` - Today's commit
  - `yesterday()` - Yesterday's commit
  - `withExternalRefs()` - Include issue references
  - `withFiles()` - Include file details
  - `ofType(CommitType)` - Specific commit type

---

### 3. CommitType Enum

- **File:** `app/Constants/CommitType.php`
- Backed string enum based on Conventional Commits specification
- Cases:
  - `Feat` - New feature
  - `Fix` - Bug fix
  - `Docs` - Documentation
  - `Style` - Code style/formatting
  - `Refactor` - Code refactoring
  - `Perf` - Performance improvement
  - `Test` - Tests
  - `Build` - Build system
  - `Ci` - CI/CD
  - `Chore` - Maintenance
  - `Revert` - Revert commit
  - `Other` - Uncategorized
- Helper methods:
  - `displayName()` - Human-readable name
  - `emoji()` - Unicode emoji for UI
  - `weight()` - Impact score weight (0.3 - 1.0)
  - `color()` - Tailwind CSS classes for UI
  - `options()` - Array for select dropdowns
  - `fromString()` - Parse from string with fallback

---

### 4. Tests

#### Model Tests
- **File:** `tests/Feature/Models/RepositoryTest.php`
  - Relationship tests
  - Scope tests
  - Accessor tests
  - Factory state tests

- **File:** `tests/Feature/Models/CommitTest.php`
  - Relationship tests
  - Scope tests
  - Accessor tests
  - CommitType cast tests
  - Factory state tests

#### Unit Tests
- **File:** `tests/Unit/Constants/CommitTypeTest.php`
  - All enum cases
  - Helper method tests
  - Weight range validation
  - Options array structure

---

## Design Decisions

### String Over Enum Columns
- **Decision:** Use `string('commit_type', 20)` instead of MySQL `enum()` column type
- **Reason:** SQLite (used for dev/test) doesn't support MySQL enums
- **Implementation:** PHP enum with string backing provides type safety at application level

### Repository-Centric Model
- **Decision:** Commits belong to Repository, not just User
- **Reason:** Supports team analytics where multiple users may push to the same repo
- **Trade-off:** Requires repository lookup for every commit, but enables richer analytics

### Immutable DateTime Casts
- **Decision:** All date fields use `immutable_datetime` cast
- **Reason:** Prevents accidental mutation of Carbon instances
- **Benefit:** Safer date handling, especially in calculations and comparisons

### Hidden Webhook Secret
- **Decision:** `webhook_secret` in `$hidden` array
- **Reason:** Defense in depth - sensitive field excluded from JSON serialization by default
- **Benefit:** Prevents accidental exposure in API responses or logs

### CommitType Weight System
- **Decision:** Assign weights (0.3 - 1.0) to commit types
- **Reason:** Prepare for Sprint 4 impact score calculation
- **Values:**
  - Features (1.0) - Highest impact
  - Fixes (0.8) - High impact
  - Refactors/Perf (0.7) - Medium-high impact
  - Tests (0.6) - Medium impact
  - Docs/Reverts (0.5) - Medium impact
  - Build/CI (0.4) - Lower impact
  - Style/Chore (0.3) - Lowest impact

### Comprehensive Factory States
- **Decision:** Create many factory states for testing
- **Reason:** Makes tests more expressive and reduces boilerplate
- **Examples:** `Commit::factory()->feature()->highImpact()->today()->create()`

---

## Files Created

### Models
- `app/Models/Repository.php`
- `app/Models/Commit.php`

### Constants
- `app/Constants/CommitType.php`

### Migrations
- `database/migrations/2026_01_17_184536_create_repositories_table.php`
- `database/migrations/2026_01_17_184537_create_commits_table.php`

### Factories
- `database/factories/RepositoryFactory.php`
- `database/factories/CommitFactory.php`

### Tests
- `tests/Feature/Models/RepositoryTest.php`
- `tests/Feature/Models/CommitTest.php`
- `tests/Unit/Constants/CommitTypeTest.php`

---

## Quality Gates

| Check | Status |
|-------|--------|
| PHPStan Level 8 | Pass |
| Pint Code Style | Pass |
| Pest Tests | Pass |
| Architecture Tests | Pass |

---

## Next Steps (Sprint 3)

1. **GitHub Webhook Processing**
   - Process push events to store commits
   - Parse commit messages for type and scope
   - Handle repository creation from webhooks

2. **DTOs for GitHub Data**
   - PushEventData DTO
   - CommitData DTO
   - RepositoryData DTO

3. **CQRS Actions**
   - ProcessPushEventAction
   - StoreCommitAction
   - FindOrCreateRepositoryAction

---

## Lessons Learned

See `docs/lessons/LESSONS.md` for Sprint 2 specific lessons:
- String columns over enum for SQLite compatibility
- Factory states for expressive testing
- CommitType enum with helper methods for UI and scoring
- Immutable datetime casts for safety
- Repository-centric model for team analytics
