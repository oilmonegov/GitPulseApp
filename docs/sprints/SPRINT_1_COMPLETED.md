# Sprint 1: Infrastructure & GitHub OAuth Integration

**Status:** Completed
**Duration:** Sprint 1
**Date Completed:** 2026-01-17

## Overview

Sprint 1 established the foundational infrastructure for GitPulse, including CQRS architecture, code quality tools, GitHub OAuth integration, webhook handling, modular architecture, and CI/CD pipelines.

---

## Completed Tasks

### 1. CQRS Architecture

#### Contracts
- **File:** `app/Contracts/Action.php` - Interface for mutation operations
- **File:** `app/Contracts/Query.php` - Interface for read operations

#### Actions (Write Operations)
- **File:** `app/Actions/Auth/ConnectGitHubAction.php` - Links GitHub to user
- **File:** `app/Actions/Auth/DisconnectGitHubAction.php` - Disconnects GitHub
- **File:** `app/Actions/Auth/RegisterViaGitHubAction.php` - Creates user via OAuth

#### Queries (Read Operations)
- **File:** `app/Queries/User/FindUserByGitHubIdQuery.php` - Finds user by GitHub ID
- **File:** `app/Queries/User/FindUserByEmailQuery.php` - Finds user by email

### 2. DTOs (Data Transfer Objects)

- **Directory:** `app/DTOs/`
- **File:** `app/DTOs/GitHubUserData.php`
  - Type-safe data transfer for GitHub OAuth
  - Factory methods: `fromSocialite()`
  - Conversion methods: `toArray()`, `displayName()`
  - `final readonly` class structure

### 3. Constants (PHP Enums)

- **Directory:** `app/Constants/`
- **File:** `app/Constants/OAuthProvider.php`
  - Backed string enum for OAuth providers
  - Helper methods: `displayName()`, `iconName()`, `scopes()`

### 4. Database Compatibility

- **File:** `app/Concerns/DatabaseCompatible.php`
- Cross-database helpers for SQLite (dev) and MySQL (production):
  - Date extraction: `yearFromDate()`, `monthFromDate()`, `dayFromDate()`
  - Date formatting: `dateFormat()`
  - Current timestamp: `currentTimestamp()`, `currentDate()`
  - String operations: `concat()`, `groupConcat()`, `coalesce()`
  - Date math: `dateDiffDays()`, `dateAddDays()`

### 5. Code Quality Infrastructure

#### Larastan (PHPStan Level 8)
- **File:** `phpstan.neon`
- Configured static analysis at the strictest level
- Added appropriate ignores for Inertia and Socialite type hints
- All code passes Level 8 analysis

#### Rector
- **File:** `rector.php`
- Configured for PHP 8.4 compatibility
- Enabled Laravel and Symfony sets
- Added dead code, code quality, and type declaration rules

#### Laravel Pint
- **File:** `pint.json`
- Extended Laravel preset with custom rules
- Configured for consistent code formatting
- Integrated with Husky pre-commit hooks

### 6. Git Hooks (Husky + lint-staged)

#### Pre-commit Hook
- **File:** `.husky/pre-commit`
- Runs lint-staged for PHP (Pint) and JS/TS (ESLint + Prettier)
- Warns when modifying critical files (migrations, tests, config)

#### Commit-msg Hook
- **File:** `.husky/commit-msg`
- Enforces Conventional Commits format
- Validates commit message structure

#### Pre-push Hook
- **File:** `.husky/pre-push`
- Protects main/master branches from direct pushes
- Runs test suite before push

### 7. GitHub OAuth Integration

#### Backend Implementation
- **Controller:** `app/Http/Controllers/Auth/GitHubController.php`
  - OAuth redirect with scopes (read:user, user:email, repo, admin:repo_hook)
  - Callback handling using CQRS Actions and Queries
  - Account linking for authenticated users
  - Account disconnection

#### Database Migration
- **File:** `database/migrations/2026_01_17_173434_add_github_fields_to_users_table.php`
- Added fields: `github_id`, `github_username`, `github_token`, `avatar_url`, `preferences`, `timezone`

#### User Model Updates
- **File:** `app/Models/User.php`
- Immutable datetime casts for all date fields (UTC)
- `hasGitHubConnected()` helper method
- Encrypted GitHub token storage

#### Factory States
- **File:** `database/factories/UserFactory.php`
- `withGitHub()`, `withPreferences()`, `withTimezone()` states

### 8. GitHub Webhook Integration

Using Spatie `laravel-webhook-client`:

#### Signature Validator
- **File:** `app/Webhooks/GitHubSignatureValidator.php`
- HMAC SHA-256 signature verification

#### Webhook Profile
- **File:** `app/Webhooks/GitHubWebhookProfile.php`
- Filters to process: push, pull_request, ping events

#### Processing Job
- **File:** `app/Jobs/ProcessGitHubWebhookJob.php`
- Handles webhook events with structured logging
- Ready for future commit/PR storage implementation

#### Configuration
- **File:** `config/webhook-client.php`
- **File:** `routes/webhooks.php`
- Webhook route excluded from CSRF protection

### 9. Modular Route Architecture

Routes are organized by module for maintainability:

```
routes/
├── web.php           # Main web routes (loads modules)
├── webhooks.php      # Webhook routes (no CSRF)
├── web/
│   ├── auth.php      # GitHub OAuth routes
│   ├── dashboard.php # Dashboard routes
│   └── settings.php  # User settings routes
```

### 10. Exception Handling with i18n

#### Handler Configuration
- **File:** `app/Exceptions/Handler.php`
- API exception handling with consistent JSON responses
- Sensitive data filtering (passwords, tokens, file paths)
- Environment-aware error messages

#### Error Translations
- **File:** `lang/en/errors.php`
- Comprehensive error messages for:
  - HTTP status codes
  - Authentication errors
  - GitHub API errors
  - Database errors
  - Validation errors

### 11. Frontend Updates

#### SocialAuthButton Component
- **File:** `resources/js/components/SocialAuthButton.vue`
- Reusable component for OAuth buttons
- Clean, professional design

#### Auth Pages Updated
- **Files:** `resources/js/pages/auth/Login.vue`, `Register.vue`
- Added "Continue with GitHub" button
- Divider between OAuth and email/password forms

#### Custom Scrollbars
- **File:** `resources/css/app.css`
- Custom scrollbar styling for better UX
- Utility classes: `.scrollbar-hide`, `.scrollbar-thin`

#### TypeScript Type Check
- Added `type-check` script to `package.json`
- Fixed TypeScript errors in TwoFactorSetupModal

### 12. CI/CD Pipeline

#### GitHub Actions Workflow
- **File:** `.github/workflows/ci.yml`
- Four parallel jobs:
  1. Static Analysis (PHPStan Level 8)
  2. Code Style (Pint)
  3. Frontend (Build, Type Check, Lint)
  4. Tests (Pest with coverage)
- CI success gate requiring all jobs to pass

### 13. Architecture Tests

- **File:** `tests/Feature/ArchitectureTest.php`
- 27 architecture rules enforced:
  - Strict types in all files
  - Controller/Model/Request naming conventions
  - No debugging statements (dd, dump, ray)
  - env() only in config files
  - Proper trait/interface/enum conventions
  - CQRS: Actions implement Action contract and are final
  - CQRS: Queries implement Query contract and are final
  - DTOs are final readonly
  - Constants are enums

### 14. Unit Tests

- **File:** `tests/Unit/DTOs/GitHubUserDataTest.php` - 7 tests
- **File:** `tests/Unit/Constants/OAuthProviderTest.php` - 8 tests
- **File:** `tests/Feature/Concerns/DatabaseCompatibleTest.php` - 18 tests

### 15. Documentation

#### README.md
- **File:** `README.md`
- Setup and installation instructions
- Development commands
- Project structure overview
- Architecture documentation
- Contributing guidelines

#### CLAUDE.md Guidelines
- CQRS architecture documentation
- DTOs and Constants guidelines
- Database compatibility rules
- Webhook implementation guidelines (Spatie packages)
- UI/UX guidelines
- Inertia Link vs regular anchor usage

---

## Test Coverage

```
Total Tests: 111
Assertions: 249
Duration: ~2.0s

Test Categories:
- Feature Tests: 78 tests
- Architecture Tests: 27 tests
- Unit Tests: 16 tests
- Browser Tests: (available but not run in CI without Playwright setup)
```

---

## Quality Gates

All quality gates pass:

| Check | Status |
|-------|--------|
| PHPStan Level 8 | ✅ Pass |
| Pint Code Style | ✅ Pass |
| ESLint | ✅ Pass |
| TypeScript | ✅ Pass |
| Pest Tests | ✅ 111 passed |
| Vite Build | ✅ Pass |

---

## Environment Variables Required

Add these to your `.env` file:

```env
# GitHub OAuth
GITHUB_CLIENT_ID=your_client_id
GITHUB_CLIENT_SECRET=your_client_secret
GITHUB_REDIRECT_URI=http://localhost:8000/auth/github/callback

# GitHub Webhooks
GITHUB_WEBHOOK_SECRET=your_webhook_secret
```

---

## Files Created/Modified

### Created
- `.github/workflows/ci.yml`
- `README.md`
- `app/Actions/Auth/ConnectGitHubAction.php`
- `app/Actions/Auth/DisconnectGitHubAction.php`
- `app/Actions/Auth/RegisterViaGitHubAction.php`
- `app/Concerns/DatabaseCompatible.php`
- `app/Constants/OAuthProvider.php`
- `app/Contracts/Action.php`
- `app/Contracts/Query.php`
- `app/DTOs/GitHubUserData.php`
- `app/Http/Controllers/Auth/GitHubController.php`
- `app/Jobs/ProcessGitHubWebhookJob.php`
- `app/Queries/User/FindUserByEmailQuery.php`
- `app/Queries/User/FindUserByGitHubIdQuery.php`
- `app/Webhooks/GitHubSignatureValidator.php`
- `app/Webhooks/GitHubWebhookProfile.php`
- `config/webhook-client.php`
- `database/migrations/2026_01_17_173434_add_github_fields_to_users_table.php`
- `database/migrations/2026_01_17_182759_create_webhook_calls_table.php`
- `lang/en/errors.php`
- `phpstan.neon`
- `rector.php`
- `resources/js/components/SocialAuthButton.vue`
- `routes/web/auth.php`
- `routes/web/dashboard.php`
- `routes/web/settings.php`
- `routes/webhooks.php`
- `tests/Feature/ArchitectureTest.php`
- `tests/Feature/Auth/GitHubAuthenticationTest.php`
- `tests/Feature/Concerns/DatabaseCompatibleTest.php`
- `tests/Unit/Constants/OAuthProviderTest.php`
- `tests/Unit/DTOs/GitHubUserDataTest.php`
- `.husky/pre-commit`
- `.husky/commit-msg`
- `.husky/pre-push`

### Modified
- `app/Exceptions/Handler.php`
- `app/Models/User.php`
- `bootstrap/app.php`
- `CLAUDE.md`
- `config/services.php`
- `database/factories/UserFactory.php`
- `eslint.config.js`
- `package.json`
- `pint.json`
- `resources/css/app.css`
- `resources/js/pages/auth/Login.vue`
- `resources/js/pages/auth/Register.vue`
- `routes/web.php`
- `tests/TestCase.php`

---

## Next Steps (Sprint 2)

1. **F1: Commit Storage & Processing**
   - Store commits from webhooks in database
   - Parse commit messages for metadata
   - Calculate productivity metrics

2. **F2: Dashboard Analytics**
   - Commit activity charts
   - Productivity trends
   - Weekly summaries

3. **F3: Repository Management**
   - Repository listing and selection
   - Webhook registration automation
   - Repository sync status

---

## Lessons Learned

1. **CQRS Pattern**: Separating Actions (mutations) from Queries (reads) keeps controllers thin and logic testable
2. **Package Selection**: Always check Laravel first-party and Spatie packages before custom implementation
3. **Type Safety**: PHPStan Level 8 catches many potential bugs early
4. **Modular Routes**: Separating routes by module improves maintainability
5. **Exception Handling**: i18n for error messages provides better user experience
6. **Git Hooks**: Pre-commit hooks prevent bad code from entering the repository
7. **Database Compatibility**: Using a trait for cross-database functions ensures SQLite/MySQL portability
8. **Webhook Handling**: Spatie webhook-client provides robust signature verification and job processing
