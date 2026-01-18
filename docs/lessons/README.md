---
description: Index and guide for GitPulse lessons learned documentation
updated: 2026-01-18
---

# Lessons Learned

A modular documentation system capturing decisions, reasoning, and lessons from GitPulse development.

## Quick Reference

| Topic | File | Key Patterns |
|-------|------|--------------|
| CQRS Actions/Queries | [architecture.md](architecture.md) | `Action` interface, `execute()` method, `Query` interface, `get()` method |
| DTOs & Enums | [architecture.md](architecture.md) | `final readonly` classes, factory methods, `app/Constants` |
| SQLite/MySQL compat | [database.md](database.md) | `DatabaseCompatible` trait, never use `enum()` columns |
| Eloquent patterns | [database.md](database.md) | Model scopes, factory states, relationship methods |
| Chart.js dark mode | [frontend.md](frontend.md) | `useChartColors` composable, MutationObserver |
| Vue-sonner toasts | [frontend.md](frontend.md) | `h()` wrapper in app.ts, group selectors |
| Design system | [frontend.md](frontend.md) | Semantic CSS variables, no hardcoded neutrals |
| Pest mutations | [testing.md](testing.md) | `--everything --covered-only` flags |
| Feature tests | [testing.md](testing.md) | RefreshDatabase trait, factory states |
| Sentry/Health checks | [infrastructure.md](infrastructure.md) | Conditional checks with `->if()` |
| Feature flags | [infrastructure.md](infrastructure.md) | Pennant class-based features |
| Docker multi-stage | [infrastructure.md](infrastructure.md) | Build stages, supervisord |
| Saloon HTTP client | [integrations.md](integrations.md) | Request classes, MockClient testing |
| OAuth/Socialite | [integrations.md](integrations.md) | Regular anchors for OAuth, SocialiteUser cast |
| Webhooks | [integrations.md](integrations.md) | Spatie webhook-client, signature validation |
| Git hooks | [git-workflow.md](git-workflow.md) | Husky pre-push, quality gates |
| Conventional commits | [git-workflow.md](git-workflow.md) | `type(scope): description` format |

## File Structure

```
docs/lessons/
├── README.md           # This file - index and guide
├── architecture.md     # CQRS, Actions, Queries, DTOs, Enums
├── database.md         # Migrations, SQLite/MySQL compat, Eloquent
├── frontend.md         # Vue, Inertia, Chart.js, UI, design system
├── testing.md          # Pest, mutation, browser tests, factories
├── infrastructure.md   # CI/CD, Docker, monitoring, Sentry, health
├── integrations.md     # GitHub, OAuth, Socialite, Saloon, webhooks
└── git-workflow.md     # Hooks, conventional commits, releases
```

## Usage Guide

### For Agents (Claude Code)

**Before documenting a new lesson:**

1. Check the relevant themed file for existing lessons on the topic
2. Use grep/search to find similar patterns: `grep -i "pattern" docs/lessons/*.md`
3. If a similar lesson exists, reference it instead of duplicating
4. Only add new entries when encountering genuinely new learnings
5. Update existing entries if new insights are gained

**Which file to update:**

| Type of Work | Update File |
|--------------|-------------|
| CQRS Actions/Queries, DTOs, Enums, Services | `architecture.md` |
| Database migrations, Eloquent, queries | `database.md` |
| Vue components, Inertia, CSS, UI/UX | `frontend.md` |
| Pest tests, mutations, browser tests | `testing.md` |
| CI/CD, Docker, monitoring, deployment | `infrastructure.md` |
| OAuth, APIs, webhooks, external services | `integrations.md` |
| Git hooks, commits, releases, workflow | `git-workflow.md` |

### For Developers

**When to check lessons:**

- Before choosing between implementation approaches
- Before creating migrations (column types, constraints)
- Before adding new packages or patterns
- When facing a decision that could go multiple ways

**When to update lessons:**

After completing significant work (`feat`, `fix`, `refactor`, `perf`), document:
- What went wrong? (mistakes to avoid)
- What went well? (patterns to follow)
- Why you chose this direction (reasoning)

### Pre-Push Hook Behavior

The pre-push hook checks for lessons documentation on significant commits:

1. Extracts scope from commit messages (e.g., `feat(frontend):` -> `frontend`)
2. Checks if existing lessons cover that scope
3. **Skip** if existing lessons found for the scope
4. **Require** documentation if working in a new area

## Entry Template

```markdown
## [Feature/Sprint Name]

### What went wrong?
- Issue description and root cause
- How it was discovered
- Time/effort lost

### What went well?
- Success description
- Contributing factors
- Reusable patterns identified

### Why we chose this direction
- **Decision**: What we decided
- **Alternatives considered**: Other options evaluated
- **Reasoning**: Why this choice was best for GitPulse

### Code Pattern (optional)
```language
// Example code demonstrating the pattern
```
```

## Tags by File

For semantic search and agent context injection:

- **architecture.md**: `cqrs`, `actions`, `queries`, `dtos`, `enums`, `services`, `contracts`
- **database.md**: `migrations`, `eloquent`, `sqlite`, `mysql`, `models`, `factories`, `seeders`
- **frontend.md**: `vue`, `inertia`, `chartjs`, `ui-components`, `design-system`, `dark-mode`, `css`
- **testing.md**: `pest`, `mutations`, `browser-tests`, `factories`, `coverage`, `phpunit`
- **infrastructure.md**: `docker`, `ci-cd`, `sentry`, `health-checks`, `pennant`, `deployment`
- **integrations.md**: `oauth`, `socialite`, `saloon`, `webhooks`, `github-api`
- **git-workflow.md**: `husky`, `hooks`, `conventional-commits`, `dependabot`, `releases`
