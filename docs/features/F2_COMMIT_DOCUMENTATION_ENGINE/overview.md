# F2: Commit Documentation Engine

## Summary

The Commit Documentation Engine parses, categorizes, and scores commits to provide meaningful analytics for developer productivity tracking.

## Core Features

### 1. Conventional Commit Parsing
- Full support for [Conventional Commits](https://www.conventionalcommits.org/) specification
- Extracts type, scope, description, and breaking change indicators
- Format: `<type>[(scope)][!]: <description>`

### 2. NLP-Based Categorization
- Keyword and phrase matching for non-conventional commits
- Confidence scoring for categorization accuracy
- Fallback to "other" for unrecognized patterns

### 3. External Reference Extraction
- GitHub issues/PRs: `#123`
- JIRA tickets: `PROJ-123`
- Linear tickets: `eng-123`

### 4. Impact Score Calculation
- Weighted formula considering multiple factors
- Context-aware scoring using repository averages
- Score range: 0-10+ (higher = more impactful)

## Impact Score Factors

| Factor | Weight | Description |
|--------|--------|-------------|
| Lines Changed | 20% | Code volume relative to repo average |
| Files Touched | 15% | Breadth of changes |
| Commit Type | 25% | Feature > Fix > Refactor > Chore |
| Merge Commit | 20% | Merges weighted higher |
| External Refs | 10% | Linked to issues/PRs |
| Focus Time | 10% | Peak hours vs late night |

## Supported Commit Types

| Type | Weight | Use Case |
|------|--------|----------|
| feat | 1.0 | New features |
| fix | 0.8 | Bug fixes |
| refactor | 0.7 | Code restructuring |
| perf | 0.7 | Performance improvements |
| test | 0.6 | Test additions |
| docs | 0.5 | Documentation |
| build | 0.4 | Build system |
| ci | 0.4 | CI/CD changes |
| style | 0.3 | Code style |
| chore | 0.3 | Maintenance |
| revert | 0.5 | Reverting changes |
| other | 0.5 | Uncategorized |

## Integration Points

- **Webhook Processing:** Called after commits are stored from GitHub webhooks
- **Dashboard:** Impact scores displayed in commit lists
- **Weekly Reports:** Aggregate scores for productivity insights
- **Analytics:** Type distribution and trend analysis

## Implementation

See Sprint 4 documentation: `docs/sprints/SPRINT_4_COMMIT_DOCUMENTATION_ENGINE.md`
