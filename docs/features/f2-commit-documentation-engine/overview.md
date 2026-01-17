# F2: Commit Documentation Engine

## Feature Summary

Parse commit messages and transform them into human-readable work documentation. This engine extracts meaning from commits to power analytics and reporting.

## Priority

**P0 (MVP)** - Core feature required for launch

## Goals

1. Support Conventional Commits format (feat:, fix:, chore:, docs:, refactor:, test:, style:, perf:)
2. Auto-categorize commits without conventional format using NLP heuristics
3. Extract ticket/issue references (#123, JIRA-456, LINEAR-789)
4. Calculate impact scores based on weighted factors
5. Link commits to projects/repositories

## Acceptance Criteria

- [ ] Conventional commit messages are parsed with 100% accuracy
- [ ] Non-conventional commits are categorized with 80%+ accuracy
- [ ] Issue references (GitHub #, JIRA, Linear) are extracted
- [ ] Impact scores range from 0-10+ based on weighted factors
- [ ] All commit types have corresponding labels and weights

## Stepwise Refinement

### Level 0: High-Level Flow

```
Raw Commit Message → Parser → Categorized & Scored Commit
```

### Level 1: Component Breakdown

```
┌─────────────────────────────────────────────────────────────┐
│                  Commit Documentation Engine                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────┐     ┌─────────────────────┐        │
│  │  ParseCommitMessage │────▶│   CategorizeCommit  │        │
│  │       Action        │     │      (Fallback)     │        │
│  └─────────────────────┘     └─────────────────────┘        │
│           │                           │                      │
│           ▼                           ▼                      │
│  ┌─────────────────────────────────────────────────┐        │
│  │              Parsed Commit Data                  │        │
│  │  - type: CommitType enum                        │        │
│  │  - scope: optional string                       │        │
│  │  - description: string                          │        │
│  │  - external_refs: array                         │        │
│  │  - is_breaking: boolean                         │        │
│  └─────────────────────────────────────────────────┘        │
│                          │                                   │
│                          ▼                                   │
│  ┌─────────────────────────────────────────────────┐        │
│  │           CalculateImpactScore Action           │        │
│  └─────────────────────────────────────────────────┘        │
│                          │                                   │
│                          ▼                                   │
│  ┌─────────────────────────────────────────────────┐        │
│  │         Final Impact Score (0-10+)              │        │
│  └─────────────────────────────────────────────────┘        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Level 2: Detailed Components

#### 2.1 ParseCommitMessage Action

**Input**: Raw commit message string
**Output**: ParsedCommit DTO

```
"feat(auth): add OAuth2 login flow #123"
       │
       ▼
┌─────────────────────────┐
│ Conventional Commit     │
│ Regex Pattern Match     │
└─────────────────────────┘
       │
       ▼
┌─────────────────────────┐
│ ParsedCommit {          │
│   type: FEAT            │
│   scope: "auth"         │
│   description: "add..." │
│   refs: ["#123"]        │
│   is_breaking: false    │
│ }                       │
└─────────────────────────┘
```

#### 2.2 CategorizeCommit Action (NLP Fallback)

For non-conventional commits:

```
"Fixed the login bug"
       │
       ▼
┌─────────────────────────┐
│ Keyword Analysis        │
│ - "fixed" → FIX         │
│ - "bug" → FIX (confirm) │
└─────────────────────────┘
       │
       ▼
CommitType::FIX
```

#### 2.3 CalculateImpactScore Action

**Scoring Formula**:

| Factor | Weight | Calculation |
|--------|--------|-------------|
| Lines changed | 20% | `min((additions + deletions) / repo_avg, 2.0)` |
| Files touched | 15% | `min(files_changed / 5, 1.5)` |
| Commit type | 25% | Type weight (feat=1.0, fix=0.8, etc.) |
| Merge commit | 20% | Merge=1.5, Regular=0.5 |
| External refs | 10% | Has refs=1.0, No refs=0.5 |
| Focus time | 10% | Peak hours=1.2, Normal=1.0, Late=0.8 |

**Final Score**: `Σ(weight × factor_score) × 10`

## Dependencies

### Internal
- Commit model
- CommitType enum
- Repository model (for averages)

### External
- None (pure PHP implementation)

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Actions/Commits/ParseCommitMessage.php` | Conventional commit parser |
| `app/Actions/Commits/CategorizeCommit.php` | NLP-based categorization |
| `app/Actions/Commits/CalculateImpactScore.php` | Impact score calculation |
| `app/Data/ParsedCommitData.php` | Parser output DTO |
| `app/Enums/CommitType.php` | Commit type definitions |
