# F3: Daily Dashboard

## Feature Summary

Real-time view of today's activity with historical comparison. The dashboard serves as the primary interface for tracking daily productivity.

## Priority

**P0 (MVP)** - Core feature required for launch

## Goals

1. Display today's commit count with hour-by-hour breakdown
2. Show commit list with message, repo, timestamp, impact score
3. Provide comparison widgets (Today vs Yesterday, Today vs Last Week)
4. Display project breakdown (pie chart of repos)
5. Show streak indicator (consecutive days with commits)
6. Real-time updates via Laravel Reverb WebSockets

## UI Components

| Component | Description |
|-----------|-------------|
| MetricCard | Total commits, Impact score, Active repos, Hours active |
| CommitTimeline | Hour-by-hour visualization of commit distribution |
| TodayVsYesterday | Side-by-side comparison widget |
| CommitList | Scrollable list with filters |
| LiveCommitFeed | Real-time feed with animated entry |
| StreakBadge | Consecutive days indicator |
| TypeBreakdown | Pie chart of commit types |
| RepoBreakdown | Pie chart of repository activity |

## Acceptance Criteria

- [ ] Dashboard loads within 2 seconds
- [ ] Real-time updates appear within 1 second of commit
- [ ] All metrics are accurate to the current day
- [ ] Comparison percentages are calculated correctly
- [ ] Streak count is accurate
- [ ] Filters work correctly (by repo, type, time)
- [ ] Mobile-responsive layout

## Stepwise Refinement

### Level 0: High-Level Flow

```
User Login → Dashboard Request → Data Aggregation → Render + WebSocket Subscribe
```

### Level 1: Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                     Dashboard Controller                     │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────┐        ┌─────────────────────────┐     │
│  │  MetricsService │───────▶│  Inertia::render()      │     │
│  └─────────────────┘        │  Props:                  │     │
│          │                  │  - todayMetrics          │     │
│          ▼                  │  - yesterdayMetrics      │     │
│  ┌─────────────────┐        │  - recentCommits         │     │
│  │  Redis Cache    │        │  - weeklyTrend           │     │
│  │  (hot metrics)  │        │  - streak                │     │
│  └─────────────────┘        │  - repositories          │     │
│                             └─────────────────────────┘     │
│                                        │                     │
│                                        ▼                     │
│                             ┌─────────────────────────┐     │
│                             │  Dashboard.vue          │     │
│                             │  + Laravel Echo         │     │
│                             │  + Reverb WebSocket     │     │
│                             └─────────────────────────┘     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Level 2: Component Architecture

```
Dashboard.vue
├── MetricCard.vue (x4)
│   ├── Today's Commits (with % change)
│   ├── Impact Score
│   ├── Active Repos
│   └── Current Streak
├── CommitTimeline.vue
│   └── Chart.js Line/Bar chart
├── TodayVsYesterday.vue
│   ├── Today stats
│   ├── Yesterday stats
│   └── Change indicators
├── LiveCommitFeed.vue
│   ├── useRealtime() composable
│   └── Animated commit cards
├── TypeBreakdown.vue (Pie)
└── RepoBreakdown.vue (Pie)
```

### Level 3: Real-time Updates

```
┌─────────────────────────────────────────────────────────────┐
│                    Real-time Data Flow                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Commit Processed                                            │
│        │                                                     │
│        ▼                                                     │
│  ┌─────────────────┐                                        │
│  │  CommitProcessed │   (Laravel Event)                     │
│  │  Event           │                                        │
│  └────────┬────────┘                                        │
│           │                                                  │
│           ▼                                                  │
│  ┌─────────────────┐                                        │
│  │  Laravel Reverb  │   (WebSocket Server)                  │
│  │  Broadcast       │                                        │
│  └────────┬────────┘                                        │
│           │                                                  │
│           ▼                                                  │
│  ┌─────────────────┐                                        │
│  │  Laravel Echo    │   (Frontend Client)                   │
│  │  private channel │                                        │
│  └────────┬────────┘                                        │
│           │                                                  │
│           ▼                                                  │
│  ┌─────────────────┐                                        │
│  │  useRealtime()   │   (Vue Composable)                    │
│  │  - todayCommits  │   reactive refs                       │
│  │  - todayImpact   │                                        │
│  │  - liveCommits   │                                        │
│  └─────────────────┘                                        │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Data Requirements

### Initial Page Load

| Data | Source | Caching |
|------|--------|---------|
| todayMetrics | DailyMetric model | Redis 5min |
| yesterdayMetrics | DailyMetric model | Redis 1hr |
| recentCommits | Commit model (20 latest) | None |
| weeklyTrend | DailyMetric (7 days) | Redis 1hr |
| streak | Redis counter | Real-time |
| repositories | Repository model | Session |

### Real-time Updates

| Event | Data | Action |
|-------|------|--------|
| CommitProcessed | commit details, impact | Increment counters, prepend to feed |
| DailyMetricsUpdated | aggregated stats | Refresh metric cards |

## Dependencies

### Internal
- F1: GitHub Webhook Integration (data source)
- F2: Commit Documentation Engine (impact scores)
- MetricsService
- DailyMetric model

### External
- Laravel Reverb (WebSockets)
- Laravel Echo (frontend)
- Chart.js + vue-chartjs
- Inertia.js

## Configuration

### Reverb Channel Authorization

```php
// routes/channels.php
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

### Echo Configuration

```typescript
// resources/js/bootstrap.ts
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: true,
    enabledTransports: ['ws', 'wss'],
});
```

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Http/Controllers/DashboardController.php` | Page controller |
| `app/Services/Analytics/MetricsService.php` | Data aggregation |
| `resources/js/Pages/Dashboard.vue` | Main page component |
| `resources/js/Components/Dashboard/*.vue` | Dashboard widgets |
| `resources/js/Composables/useRealtime.ts` | WebSocket logic |
| `resources/js/Composables/useMetrics.ts` | Metrics calculations |
