# Lessons Learned

A living document capturing decisions, reasoning, and lessons from GitPulse development.

---

## Sprint 4: Commit Documentation Engine

### What went wrong?
- Started Sprint 4 before Sprint 3 (webhook integration) was complete - created TODO markers for integration points rather than blocking
- This means the enrichment pipeline can't be fully tested end-to-end until Sprint 3 is merged

### What went well?
- Comprehensive test coverage: 110 unit tests + 9 feature tests covering happy paths, edge cases, and weird inputs
- Clean separation of concerns: each Action does one thing well (parse, categorize, score, enrich)
- DTO with factory methods (`conventional()`, `inferred()`, `merge()`) made code readable and intention-clear
- NLP categorization handles non-conventional commits gracefully with confidence scoring
- Impact score formula is transparent and debuggable via `getBreakdown()` method

### Why we chose this direction
- **CQRS Actions pattern**: Each step (parse, categorize, score) is a separate Action for testability and reuse. EnrichCommitAction orchestrates them.
- **NLP fallback over strict parsing**: Real-world commits don't always follow Conventional Commits. Keyword matching with confidence scores handles legacy repos.
- **Weighted scoring formula**: 6 factors with explicit weights make the impact score explainable. Users can understand why a commit scored high/low.
- **Word boundary matching**: Used regex `\b` patterns for keyword detection to avoid false positives (e.g., "testing" shouldn't match "test" category alone).
- **Phrase patterns first**: Checked multi-word phrases before single keywords for higher accuracy ("bug fix" is more confident than just "fix").
- **Peak hours factor**: Controversial choice - assumes 9-5 is peak productivity. May need user-configurable thresholds later.
- **Repository average for line scoring**: Normalizes against repo's own patterns rather than absolute numbers. A 50-line change means different things in different repos.

---

## Sprint 2: Repository and Commit Models

### What went wrong?
- Initially considered using `enum()` column type for `commit_type` but SQLite doesn't support it - switched to `string('commit_type', 20)` for cross-database compatibility
- Had to be careful with factory states to not create orphaned commits without repositories

### What went well?
- CommitType enum with `weight()` method prepared for Sprint 4 impact scoring - forward-thinking API design
- Comprehensive factory states (`withMergeCommit()`, `conventionalFeat()`, etc.) made testing expressive
- Model scopes (`active()`, `withWebhook()`, `byAuthor()`) encapsulate common queries cleanly
- 105 tests covering models and enum - caught edge cases early
- Seeders create realistic development data patterns

### Why we chose this direction
- **String over enum columns**: SQLite (dev/test) doesn't support MySQL enums. Using `string('commit_type', 20)` with PHP enum casting gives type safety without database lock-in.
- **Enum with helper methods**: `CommitType` has `displayName()`, `emoji()`, `weight()`, `color()` - all presentation logic in one place. Components just call `$commit->commit_type->emoji()`.
- **Immutable datetime casts**: All date fields use `immutable_datetime` for safety. Prevents accidental mutation of Carbon instances.
- **Hidden webhook_secret**: Sensitive fields hidden from JSON serialization by default. Defense in depth.
- **Repository-centric model**: Commits belong to Repository, not just User. Supports team analytics later without schema changes.
- **Soft scope naming**: Used `scopeActive()` not `scopeEnabled()` - matches domain language ("active repositories").

---

## Sprint 1: Infrastructure Setup

### What went wrong?
- OAuth redirect requires regular `<a>` tags, not Inertia `<Link>` - external OAuth providers need full page redirects. Took debugging to realize this.
- PHPStan Level 8 initially flagged Socialite return types as mixed - had to add proper type annotations and casts
- Husky pre-commit hooks can slow down commits significantly - had to optimize lint-staged config

### What went well?
- CQRS pattern paid off immediately - Actions are highly testable in isolation
- DatabaseCompatible trait saved hours of debugging cross-DB issues
- Architecture tests catch structural violations automatically - no manual review needed
- Spatie webhook-client handled all signature validation complexity
- 111 tests from day one established quality baseline
- Modular route files (`routes/web/auth.php`, etc.) keep routing organized

### Why we chose this direction
- **CQRS over fat controllers**: Actions (mutations) and Queries (reads) are separate. Controllers become thin orchestrators. Each piece is unit-testable.
- **Spatie webhook-client over DIY**: Webhook signature validation is security-critical. Spatie's package is battle-tested, handles retries, stores webhook calls for debugging.
- **SQLite dev + MySQL prod**: Fast local tests, production-grade DB in staging/prod. DatabaseCompatible trait abstracts the differences.
- **PHPStan Level 8**: Strictest level catches type errors early. Worth the initial setup cost.
- **Architecture tests**: Pest `arch()` tests enforce conventions automatically. New devs can't accidentally violate patterns.
- **Modular routes**: As app grows, `routes/web.php` would become unmaintainable. Splitting by domain (auth, settings, dashboard) keeps files focused.
- **Encrypted token storage**: GitHub OAuth tokens are sensitive. Using Laravel's `encrypted` cast ensures they're encrypted at rest.
- **i18n error messages**: `lang/en/errors.php` centralizes error text. Future localization is just adding translation files.

---

## Template for New Entries

```markdown
## [Sprint/Feature Name]

### What went wrong?
- Issue description
- Root cause analysis

### What went well?
- Success description
- Contributing factors

### Why we chose this direction
- Decision: [what we decided]
- Alternatives considered: [other options]
- Reasoning: [why this choice was best]
```
