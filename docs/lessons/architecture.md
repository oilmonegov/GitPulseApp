---
tags: [cqrs, actions, queries, dtos, enums, services, contracts, patterns]
updated: 2026-01-18
---

# Architecture Lessons

> **Quick summary for agents**: GitPulse uses a simplified CQRS pattern with Actions (mutations) and Queries (reads). Actions implement `App\Contracts\Action` with `execute()`, Queries implement `App\Contracts\Query` with `get()`. Both must be `final` classes. DTOs are `final readonly` with factory methods. Enums live in `app/Constants` with TitleCase names.

---

## Sprint 1: CQRS Pattern Adoption

### What went wrong?
- OAuth redirect requires regular `<a>` tags, not Inertia `<Link>` - external OAuth providers need full page redirects. Took debugging to realize this.
- PHPStan Level 8 initially flagged Socialite return types as mixed - had to add proper type annotations and casts
- Husky pre-commit hooks can slow down commits significantly - had to optimize lint-staged config

### What went well?
- CQRS pattern paid off immediately - Actions are highly testable in isolation
- DatabaseCompatible trait saved hours of debugging cross-DB issues
- Architecture tests catch structural violations automatically - no manual review needed
- 111 tests from day one established quality baseline
- Modular route files (`routes/web/auth.php`, etc.) keep routing organized

### Why we chose this direction
- **CQRS over fat controllers**: Actions (mutations) and Queries (reads) are separate. Controllers become thin orchestrators. Each piece is unit-testable.
- **PHPStan Level 8**: Strictest level catches type errors early. Worth the initial setup cost.
- **Architecture tests**: Pest `arch()` tests enforce conventions automatically. New devs can't accidentally violate patterns.
- **Modular routes**: As app grows, `routes/web.php` would become unmaintainable. Splitting by domain (auth, settings, dashboard) keeps files focused.
- **Encrypted token storage**: GitHub OAuth tokens are sensitive. Using Laravel's `encrypted` cast ensures they're encrypted at rest.
- **i18n error messages**: `lang/en/errors.php` centralizes error text. Future localization is just adding translation files.

### Code Pattern
```php
// Action pattern
final class ConnectGitHubAction implements Action
{
    public function __construct(
        private readonly User $user,
        private readonly SocialiteUser $githubUser,
    ) {}

    public function execute(): bool
    {
        $this->user->update([...]);
        return true;
    }
}

// Query pattern
final class FindUserByGitHubIdQuery implements Query
{
    public function __construct(
        private readonly string $githubId,
    ) {}

    public function get(): ?User
    {
        return User::where('github_id', $this->githubId)->first();
    }
}
```

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

### Code Pattern
```php
// DTO with factory methods
final readonly class ParsedCommitData
{
    public static function conventional(
        string $type,
        ?string $scope,
        string $description,
        bool $breaking = false,
    ): self {
        return new self(
            type: $type,
            scope: $scope,
            description: $description,
            breaking: $breaking,
            confidence: 1.0, // Conventional commits have full confidence
        );
    }

    public static function inferred(
        string $type,
        string $description,
        float $confidence,
    ): self {
        return new self(
            type: $type,
            scope: null,
            description: $description,
            breaking: false,
            confidence: $confidence,
        );
    }
}
```

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
