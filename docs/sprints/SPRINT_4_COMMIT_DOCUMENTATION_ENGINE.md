# Sprint 4: Commit Documentation Engine (F2)

**Status:** In Progress
**Branch:** `feature/sprint-4-commit-documentation`
**Started:** January 17, 2026
**Dependency:** Sprint 3 (Webhook Integration) for full integration

---

## Overview

Sprint 4 implements the Commit Documentation Engine, which parses commit messages, categorizes them, and calculates impact scores. This is a core component for GitPulse's productivity analytics.

---

## Deliverables

### 1. ParsedCommitData DTO
**File:** `app/DTOs/Commits/ParsedCommitData.php`

A readonly Data Transfer Object that holds parsed commit data:
- `type`: CommitType enum
- `scope`: Optional scope from conventional commit
- `description`: The commit description
- `externalRefs`: Array of issue/PR/ticket references
- `isBreakingChange`: Whether it's a breaking change (!)
- `isConventional`: Whether it follows conventional commit format
- `isMerge`: Whether it's a merge commit

**Factory Methods:**
- `conventional()` - For conventional commits
- `inferred()` - For NLP-inferred types
- `merge()` - For merge commits

---

### 2. ParseCommitMessageAction
**File:** `app/Actions/Commits/ParseCommitMessageAction.php`

Parses commit messages following the Conventional Commits specification.

**Features:**
- Parses conventional commit format: `type(scope)!: description`
- Detects merge commits
- Extracts external references (GitHub #123, JIRA ABC-123, Linear eng-123)
- Falls back to CategorizeCommitAction for non-conventional commits

**Usage:**
```php
$parsed = (new ParseCommitMessageAction('feat(auth): add OAuth login #123'))->execute();
// Returns ParsedCommitData with type=Feat, scope='auth', refs=[#123]
```

---

### 3. CategorizeCommitAction
**File:** `app/Actions/Commits/CategorizeCommitAction.php`

NLP-based categorization for non-conventional commits using keyword matching.

**Features:**
- Phrase pattern matching (high confidence)
- Keyword scoring with word boundary detection
- Position-based scoring (keywords at start get bonus)
- Confidence scoring (0.0 - 1.0)

**Supported Categories:**
| Type | Sample Keywords |
|------|----------------|
| feat | add, feature, implement, new, create |
| fix | fix, bug, patch, resolve, error |
| docs | doc, readme, changelog |
| test | test, spec, coverage, mock |
| refactor | refactor, restructure, simplify |
| perf | performance, optimize, speed, cache |
| style | format, lint, whitespace |
| ci | ci, pipeline, workflow, deploy |
| build | webpack, vite, bundle, compile |
| chore | update, upgrade, bump, dependency |
| revert | revert, rollback, undo |

---

### 4. CalculateImpactScoreAction
**File:** `app/Actions/Commits/CalculateImpactScoreAction.php`

Calculates weighted impact scores for commits.

**Formula:** `Score = Σ(weight × factor_score) × 10`

**Factors:**
| Factor | Weight | Calculation |
|--------|--------|-------------|
| Lines Changed | 20% | `min((additions + deletions) / repo_avg, 2.0)` |
| Files Touched | 15% | `min(files_changed / 5, 1.5)` |
| Commit Type | 25% | CommitType weight (feat=1.0, fix=0.8, etc.) |
| Merge Commit | 20% | merge=1.5, regular=0.5 |
| External Refs | 10% | has_refs=1.0, no_refs=0.5 |
| Focus Time | 10% | peak_hours=1.2, normal=1.0, late=0.8 |

**Peak Hours:** 9 AM - 5 PM (factor 1.2)
**Late Night:** 11 PM - 5 AM (factor 0.8)

**Usage:**
```php
$score = (new CalculateImpactScoreAction(
    parsedData: $parsed,
    additions: 100,
    deletions: 20,
    filesChanged: 5,
    committedAt: Carbon::now(),
    repositoryAvgLines: 50.0, // optional
))->execute();
// Returns float score (typically 0-10+)
```

---

### 5. EnrichCommitAction
**File:** `app/Actions/Commits/EnrichCommitAction.php`

Convenience action that combines parsing and impact score calculation.

**Features:**
- Parses commit message
- Calculates impact score
- Updates commit model with enriched data
- Optional repository average for context-aware scoring

**Usage:**
```php
$avg = EnrichCommitAction::getRepositoryAverage($repositoryId);
$enrichedCommit = (new EnrichCommitAction($commit, $avg))->execute();
```

---

## Integration with Sprint 3

TODO comments have been added to Sprint 3 files for integration:

### In `app/DTOs/GitHub/CommitData.php`:
```php
/**
 * TODO [Sprint 3]: Refactor to use Sprint 4 Actions for parsing:
 * - Use ParseCommitMessageAction instead of inline parseType(), parseScope(), etc.
 * - Use CalculateImpactScoreAction for impact_score calculation
 */
```

### In `app/Actions/GitHub/StoreCommitAction.php`:
```php
/**
 * TODO [Sprint 3]: After storing commit, calculate impact score using:
 * 1. Parse message with ParseCommitMessageAction
 * 2. Calculate score with CalculateImpactScoreAction
 * 3. Update commit's impact_score field
 */
```

### Recommended Integration Pattern:
```php
// In ProcessPushEventAction or StoreCommitAction
$commit = (new StoreCommitAction($repository, $user, $commitData))->execute();

if ($commit) {
    $avg = EnrichCommitAction::getRepositoryAverage($repository->id);
    (new EnrichCommitAction($commit, $avg))->execute();
}
```

---

## Test Coverage

### Unit Tests (110 tests)

**ParseCommitMessageActionTest:**
- Conventional commit parsing (all types)
- Scope extraction
- Breaking change detection
- Merge commit detection
- External reference extraction (GitHub, JIRA, Linear)
- Non-conventional commit fallback

**CategorizeCommitActionTest:**
- All commit type keyword detection
- Phrase pattern matching
- Confidence scoring
- Edge cases (empty, case-insensitive)

**CalculateImpactScoreActionTest:**
- Score calculation with various inputs
- Type weight ordering
- Focus time factors
- Repository average usage
- Factor breakdown

**ParsedCommitDataTest:**
- Factory methods
- toArray conversion
- Helper methods

### Feature Tests (9 tests)

**EnrichCommitActionTest:**
- Conventional commit enrichment
- Merge commit enrichment
- Non-conventional commit enrichment
- Repository average calculation
- External reference extraction

---

## Files Created

| File | Description |
|------|-------------|
| `app/DTOs/Commits/ParsedCommitData.php` | DTO for parsed commit data |
| `app/Actions/Commits/ParseCommitMessageAction.php` | Conventional commit parser |
| `app/Actions/Commits/CategorizeCommitAction.php` | NLP-based categorizer |
| `app/Actions/Commits/CalculateImpactScoreAction.php` | Impact score calculator |
| `app/Actions/Commits/EnrichCommitAction.php` | Combined enrichment action |
| `tests/Unit/Actions/Commits/ParseCommitMessageActionTest.php` | Unit tests |
| `tests/Unit/Actions/Commits/CategorizeCommitActionTest.php` | Unit tests |
| `tests/Unit/Actions/Commits/CalculateImpactScoreActionTest.php` | Unit tests |
| `tests/Unit/DTOs/Commits/ParsedCommitDataTest.php` | Unit tests |
| `tests/Feature/Actions/Commits/EnrichCommitActionTest.php` | Feature tests |

---

## Files Modified

| File | Change |
|------|--------|
| `app/DTOs/GitHub/CommitData.php` | Added TODO for Sprint 4 integration |
| `app/Actions/GitHub/StoreCommitAction.php` | Added TODO for Sprint 4 integration |

---

## Quality Checks

- [x] All Sprint 4 tests pass (119 tests, 212 assertions)
- [x] Pint code style clean
- [x] Follows CQRS architecture (Actions implement Action interface)
- [x] DTOs are `final readonly`
- [x] Actions are `final`

---

## Notes for Sprint 3 Integration

When Sprint 3 is ready to integrate:

1. **Option A: Use EnrichCommitAction** (Recommended)
   - Call after StoreCommitAction in ProcessPushEventAction
   - Automatically handles parsing and scoring

2. **Option B: Refactor CommitData**
   - Replace inline parseType/parseScope with ParseCommitMessageAction
   - Add impact_score calculation before storing

3. **API Integration Note:**
   - Sprint 3 should use **Saloon** for GitHub API calls
   - Not applicable to Sprint 4 (local parsing only)

---

## Architecture Alignment

This sprint follows the CQRS architecture established in Sprint 1:

- **Actions:** Single-purpose mutation handlers
- **DTOs:** Immutable data containers
- **Contracts:** Action interface compliance
- **Constants:** CommitType enum with weights

The Commit Documentation Engine is fully decoupled and can be used independently or integrated with webhook processing.
