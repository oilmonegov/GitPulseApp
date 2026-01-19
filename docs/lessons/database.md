---
tags: [migrations, eloquent, sqlite, mysql, models, factories, seeders, queries]
updated: 2026-01-18
---

# Database Lessons

> **Quick summary for agents**: Never use `enum()` columns - use `string()` with sensible length instead (SQLite doesn't support MySQL enums). Use the `DatabaseCompatible` trait for date functions. Use factory states for expressive test setup. Cast dates to `immutable_datetime`. Hide sensitive fields from JSON serialization.

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

### Code Pattern
```php
// Migration: Use string instead of enum
$table->string('commit_type', 20)->default('other');

// Model: Cast to PHP enum
protected function casts(): array
{
    return [
        'commit_type' => CommitType::class,
        'committed_at' => 'immutable_datetime',
    ];
}

// Enum with helper methods
enum CommitType: string
{
    case Feature = 'feat';
    case Fix = 'fix';
    case Docs = 'docs';
    // ...

    public function weight(): int
    {
        return match ($this) {
            self::Feature => 3,
            self::Fix => 2,
            self::Docs => 1,
            // ...
        };
    }
}

// Factory state
public function conventionalFeat(): static
{
    return $this->state(fn () => [
        'message' => 'feat(auth): add login functionality',
        'commit_type' => CommitType::Feature,
    ]);
}
```

---

## Sprint 5: Analytics Queries

### What went wrong?
- Unit tests for queries initially placed in `tests/Unit/` failed with "Facade root not set" - RefreshDatabase trait only works in `tests/Feature/` per Pest.php config
- CommitType enum is cast at model level, so `selectRaw('commit_type')` returns the enum, not a string - had to handle both cases in CommitTypeDistributionQuery
- Test for "unknown commit_type" was invalid - model casting prevents invalid enum values, so changed to test the valid "other" type instead

### What went well?
- DatabaseCompatible trait made date grouping work on both SQLite and MySQL without changes
- CommitFactory states (`today()`, `feature()`, `fix()`) made test setup expressive and date-range-aware
- Gap-filling in CommitsOverTimeQuery ensures charts have continuous x-axis data

### Why we chose this direction
- **Queries in Feature tests**: Even though they're "unit" testing query logic, they need the database. Pest's RefreshDatabase only bootstraps in Feature/Browser directories.
- **Gap-filling in CommitsOverTimeQuery**: Charts need continuous x-axis. Missing dates would create misleading visualizations. PHP fills gaps server-side rather than client-side for consistency.
- **Hex colors in query response**: Chart.js needs hex colors, not Tailwind classes. CommitTypeDistributionQuery returns ready-to-use `#16a34a` format.

### Code Pattern
```php
// Using DatabaseCompatible trait for cross-DB compatibility
use App\Concerns\DatabaseCompatible;

final class CommitsOverTimeQuery implements Query
{
    use DatabaseCompatible;

    public function get(): Collection
    {
        return Commit::query()
            ->selectRaw($this->dateFormat('committed_at', '%Y-%m-%d') . ' as date')
            ->selectRaw('COUNT(*) as count')
            ->whereBetween('committed_at', [$this->startDate, $this->endDate])
            ->groupByRaw($this->dateFormat('committed_at', '%Y-%m-%d'))
            ->orderBy('date')
            ->get();
    }
}
```

---

## Enhanced User Settings: Preferences Storage

### What went wrong?
- Pre-push hooks ran PHPStan which found type issues with `$user->preferences` returning mixed - needed explicit type checks and casts

### What went well?
- Weekly digest job uses cursor() for memory-efficient iteration - handles large user bases without loading all into memory

### Why we chose this direction
- **User preferences as JSON column**: Flexible schema for notification prefs, theme, etc. Avoids migration for each new preference. `array_replace_recursive` handles nested merges.
- **Cursor over chunk for user iteration**: `cursor()` uses a generator, keeping memory constant. Better than `chunk()` for simple iteration without batch processing needs.

### Code Pattern
```php
// JSON preferences with array_replace_recursive
public function updatePreferences(array $preferences): void
{
    $this->update([
        'preferences' => array_replace_recursive(
            $this->preferences ?? [],
            $preferences,
        ),
    ]);
}

// Memory-efficient iteration
User::whereJsonContains('preferences->notifications->weekly_digest', true)
    ->cursor()
    ->each(function (User $user) {
        // Process user...
    });
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
