---
tags: [husky, hooks, conventional-commits, dependabot, releases, ci-cd, quality-gates]
updated: 2026-01-18
---

# Git Workflow Lessons

> **Quick summary for agents**: Husky manages Git hooks in `.husky/` directory. Pre-push runs 5 quality gates (branch protection, lessons, PHPStan, tests, security audit). Commits must follow Conventional Commits format (`type(scope): description`). Lessons documentation is required for significant commits (`feat|fix|refactor|perf`) unless existing lessons already cover that scope.

---

## Development Lifecycle & Quality Gates

### What went wrong?
- Post-commit hooks can only remind, not block - the commit has already happened. Had to move lessons enforcement to pre-push instead.
- Git hooks in `.git/hooks/` are not the same as Husky hooks - Husky uses `core.hooksPath` to redirect to `.husky/_`
- Browser tests existed but weren't running in CI - discovered during the audit that 13 browser tests were being skipped
- No dependency vulnerability scanning was automated - `composer audit` and `npm audit` were available but not in CI pipeline
- No Dependabot configuration meant dependencies could become stale with known vulnerabilities

### What went well?
- Comprehensive hook coverage now: pre-commit (lint), commit-msg (conventional), post-commit (remind), post-merge (deps), post-checkout (migrations), pre-push (all quality gates)
- Pre-push now runs 5 checks: branch protection, lessons learned, PHPStan, tests, security audit
- Dependabot configured with grouped updates - Laravel ecosystem updates together, dev dependencies together
- CODEOWNERS ensures automatic review assignments by file path
- Issue templates standardize bug reports and feature requests
- PR template includes comprehensive checklist preventing common oversights

### Why we chose this direction
- **Pre-push over pre-commit for heavy checks**: Running PHPStan + full test suite on every commit would slow development. Pre-push is the right balance - catches issues before they reach remote.
- **Lessons learned blocking**: Documentation debt accumulates silently. Making it blocking for `feat|fix|refactor|perf` commits ensures knowledge capture happens when context is fresh.
- **Security audit as warning, not blocking**: Dependencies may have vulnerabilities without available patches. Blocking would prevent all work. Warning raises awareness.
- **Grouped Dependabot updates**: Individual PRs for each dependency creates noise. Grouping (Laravel, Vue, dev-tooling) reduces PR volume while maintaining update frequency.
- **Post-checkout over manual reminders**: Developers forget to run `composer install` after switching branches. Automated detection and prompting prevents "it works on my machine" issues.
- **CODEOWNERS by path**: Different parts of the codebase have different owners/experts. Path-based assignment ensures the right people review the right code.
- **Issue templates with required fields**: Free-form issues lack crucial debugging info. Templates guide reporters to provide environment, steps to reproduce, expected vs actual behavior.

### Code Pattern
```bash
# Pre-push quality gate flow
1. Branch protection check (blocking)
2. Lessons learned check (blocking for significant commits)
3. PHPStan static analysis (blocking)
4. Full test suite (blocking)
5. Security audit (warning only)
```

---

## Hook Configuration Summary

| Hook | Purpose | Blocking |
|------|---------|----------|
| `pre-commit` | Runs lint-staged (Pint, ESLint, Prettier) | Yes |
| `commit-msg` | Enforces Conventional Commits format | Yes |
| `post-commit` | Reminds to document lessons learned | No |
| `post-merge` | Reminds to run dependency updates | No |
| `post-checkout` | Detects dependency/migration changes between branches | No |
| `pre-push` | Runs all quality gates before push | Yes |

---

## Conventional Commits

All commits must follow the format: `type(scope): description`

**Valid types:**
- `feat` - New features
- `fix` - Bug fixes
- `docs` - Documentation only
- `style` - Code style (formatting, whitespace)
- `refactor` - Code refactoring (no functional change)
- `perf` - Performance improvements
- `test` - Adding/updating tests
- `build` - Build system changes
- `ci` - CI configuration
- `chore` - Maintenance tasks
- `revert` - Revert previous commit

**Significant types** (require lessons documentation): `feat`, `fix`, `refactor`, `perf`

---

## Lessons Documentation Requirements

### When Required
- Commits with types: `feat`, `fix`, `refactor`, `perf`
- Unless existing lessons already cover the scope

### Smart Lesson Checking
The pre-push hook performs smart checking:
1. Extracts scope from commit message (e.g., `feat(frontend):` -> `frontend`)
2. Searches existing lesson files for coverage
3. Skips requirement if lessons exist for that scope
4. Requires documentation only for genuinely new areas

### Which File to Update

| Type of Work | Update File |
|--------------|-------------|
| CQRS Actions/Queries, DTOs, Enums | `architecture.md` |
| Database migrations, Eloquent | `database.md` |
| Vue components, Inertia, CSS | `frontend.md` |
| Pest tests, mutations, browser tests | `testing.md` |
| CI/CD, Docker, monitoring | `infrastructure.md` |
| OAuth, APIs, webhooks | `integrations.md` |
| Git hooks, commits, workflow | `git-workflow.md` |

---

## CI/CD Pipeline

GitHub Actions runs on push/PR to main/develop:

| Job | Purpose | Blocking |
|-----|---------|----------|
| `security` | `composer audit` + `npm audit` | Yes |
| `static-analysis` | PHPStan level 8 | Yes |
| `code-style` | Pint test mode | Yes |
| `frontend` | TypeScript, ESLint, Vite build | Yes |
| `tests` | Parallel tests with 70% coverage minimum | Yes |

All jobs must pass before merge.

---

## Dependabot Configuration

Dependabot runs weekly (Mondays 09:00 UTC):

**Groupings:**
- **Laravel**: Framework, first-party packages together
- **Vue**: Vue ecosystem packages together
- **Dev tooling**: Testing, linting packages together
- **GitHub Actions**: Action version updates

This reduces PR volume while keeping dependencies current.

---

## Emergency Bypass

When you absolutely must bypass hooks:

```bash
# Skip pre-push hooks (emergency only)
git push --no-verify
```

**Document why** in the PR description. This should be rare and reviewed.

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
