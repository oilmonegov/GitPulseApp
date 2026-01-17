# F8: Team Dashboard (for Leads)

## Feature Summary

Aggregate view across team members with opt-in sharing. Designed for tech leads and engineering managers to get visibility without micromanaging.

## Priority

**P1 (Post-MVP)** - Enhanced feature for Phase 3

## Goals

1. Team-level metrics aggregation (total commits, PRs merged, avg impact)
2. Individual contribution breakdown (anonymizable)
3. Trend comparison across team members
4. Focus on team health, not individual ranking
5. Privacy-first: All sharing is opt-in

## Design Principles

- **Not a surveillance tool**: Focus on team health, not individual monitoring
- **Opt-in only**: Team members must explicitly share their data
- **Anonymizable**: Option to show anonymous contributions
- **No rankings**: Avoid creating toxic competition

## Team Metrics

| Metric | Description |
|--------|-------------|
| Total Team Commits | Sum of all members' commits |
| Average Impact Score | Mean impact across team |
| Active Contributors | Members with commits this period |
| Top Projects | Most active repositories |
| Team Velocity Trend | WoW change in team output |
| Collaboration Index | Cross-repo contributions |

## Acceptance Criteria

- [ ] Users can create teams and invite members
- [ ] Team membership requires opt-in acceptance
- [ ] Team leads can view aggregated metrics
- [ ] Individual data is only shown with permission
- [ ] Members can leave teams at any time
- [ ] Anonymous mode hides individual identities

## Stepwise Refinement

### Level 0: High-Level Flow

```
Create Team → Invite Members → Opt-in Accept → Aggregate Metrics → Display Dashboard
```

### Level 1: Component Breakdown

```
┌─────────────────────────────────────────────────────────────┐
│                   Team Dashboard System                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Team Management                                    │    │
│  │  - Create team (owner)                              │    │
│  │  - Invite members (email)                           │    │
│  │  - Accept/decline invitation                        │    │
│  │  - Set visibility preferences                       │    │
│  │  - Leave team                                       │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  TeamMetricsService                                 │    │
│  │  - aggregateTeamMetrics(team, dateRange)            │    │
│  │  - getMemberContributions(team, anonymous)          │    │
│  │  - getTeamTrends(team)                              │    │
│  │  - getTeamProjects(team)                            │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Team Dashboard UI                                  │    │
│  │  - Team overview cards                              │    │
│  │  - Member contribution chart                        │    │
│  │  - Team velocity trend                              │    │
│  │  - Project breakdown                                │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Level 2: Privacy Model

```
Visibility Levels:
┌─────────────────────────────────────────────────────────────┐
│ Level 0: Hidden                                             │
│ - User's data not included in team aggregates               │
│ - Appears as "Opted Out" in member list                     │
├─────────────────────────────────────────────────────────────┤
│ Level 1: Anonymous                                          │
│ - Data included in aggregates                               │
│ - Individual contributions shown as "Team Member A/B/C"     │
├─────────────────────────────────────────────────────────────┤
│ Level 2: Summary Only                                       │
│ - Name visible                                              │
│ - Only totals shown (commits, impact)                       │
│ - No breakdown by day/repo                                  │
├─────────────────────────────────────────────────────────────┤
│ Level 3: Full Visibility                                    │
│ - Name visible                                              │
│ - Full breakdown available                                  │
│ - Activity patterns visible                                 │
└─────────────────────────────────────────────────────────────┘
```

## Data Models

### Team Model

```php
Schema::create('teams', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
    $table->text('description')->nullable();
    $table->json('settings')->nullable();
    $table->timestamps();
});
```

### TeamMember Pivot

```php
Schema::create('team_members', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('role', ['owner', 'admin', 'member'])->default('member');
    $table->enum('visibility', ['hidden', 'anonymous', 'summary', 'full'])->default('summary');
    $table->enum('status', ['pending', 'active', 'declined'])->default('pending');
    $table->timestamp('joined_at')->nullable();
    $table->timestamps();

    $table->unique(['team_id', 'user_id']);
});
```

### TeamInvitation Model

```php
Schema::create('team_invitations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->string('email');
    $table->string('token', 64)->unique();
    $table->foreignId('invited_by')->constrained('users');
    $table->timestamp('expires_at');
    $table->timestamp('accepted_at')->nullable();
    $table->timestamps();

    $table->unique(['team_id', 'email']);
});
```

## Dependencies

### Internal
- F3: Daily Dashboard (individual metrics)
- F5: Comparative Analytics (trend calculations)

### External
- None (uses existing infrastructure)

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Models/Team.php` | Team model |
| `app/Models/TeamMember.php` | Pivot model |
| `app/Services/Teams/TeamMetricsService.php` | Aggregation logic |
| `app/Http/Controllers/TeamController.php` | Team CRUD |
| `app/Http/Controllers/TeamDashboardController.php` | Dashboard |
| `app/Notifications/TeamInvitation.php` | Invitation email |
| `resources/js/Pages/Teams/*.vue` | Team pages |
