# F5: Comparative Analytics

## Feature Summary

Day-over-day, week-over-week, and month-over-month comparisons with trend visualization. Enables users to understand their productivity patterns over time.

## Priority

**P0 (MVP)** - Core feature required for launch

## Goals

1. Provide trend lines for commits, impact score, active hours
2. Detect anomalies (significantly above/below average)
3. Identify best/worst periods with date ranges
4. Support configurable comparison periods
5. Visualize data with charts and heatmaps

## Visualizations

| Component | Type | Description |
|-----------|------|-------------|
| ImpactTrend | Line Chart | Dual-axis showing commits + impact over time |
| HeatmapCalendar | Heatmap | GitHub contribution graph style calendar |
| Sparklines | Mini Charts | Quick trend indicators for metric cards |
| TypeBreakdown | Pie Chart | Distribution of commit types |
| RepoBreakdown | Pie Chart | Distribution across repositories |

## Acceptance Criteria

- [ ] Users can view trends for last 7, 30, 90, 365 days
- [ ] Week-over-week changes are calculated correctly
- [ ] Anomaly detection flags unusual activity (>2 std dev)
- [ ] Heatmap shows accurate daily commit counts
- [ ] Date range picker works correctly
- [ ] Charts are responsive and performant

## Stepwise Refinement

### Level 0: High-Level Flow

```
Date Range Selection → Data Aggregation → Trend Calculation → Visualization
```

### Level 1: Component Breakdown

```
┌─────────────────────────────────────────────────────────────┐
│                  Comparative Analytics                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  ComparisonService                                  │    │
│  │  - getWeekOverWeek(user, date)                      │    │
│  │  - getMonthOverMonth(user, date)                    │    │
│  │  - getTrendData(user, startDate, endDate)           │    │
│  │  - detectAnomalies(user, period)                    │    │
│  │  - findBestWorstPeriods(user, metric)               │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Analytics Page                                     │    │
│  │  - DateRangePicker                                  │    │
│  │  - ComparisonCards (WoW, MoM changes)               │    │
│  │  - TrendChart (Chart.js Line)                       │    │
│  │  - HeatmapCalendar (custom SVG)                     │    │
│  │  - AnomalyAlerts                                    │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Level 2: Calculations

```
Week-over-Week (WoW):
  current_week = SUM(commits) for days [today - 6, today]
  previous_week = SUM(commits) for days [today - 13, today - 7]
  wow_change = ((current - previous) / previous) * 100

Month-over-Month (MoM):
  current_month = SUM(commits) for current calendar month
  previous_month = SUM(commits) for previous calendar month
  mom_change = ((current - previous) / previous) * 100

Anomaly Detection:
  mean = AVG(daily_commits) over 30 days
  std_dev = STDDEV(daily_commits) over 30 days
  anomaly = |today - mean| > (2 * std_dev)

Best/Worst Periods:
  Find 7-day sliding window with MAX/MIN total commits
```

## Data Requirements

| Query | Source | Caching |
|-------|--------|---------|
| Daily commits (1 year) | DailyMetric | Redis 1hr |
| Hourly distribution | Commit timestamps | Redis 15min |
| Type distribution | Commit types | Redis 15min |
| Anomaly thresholds | Calculated stats | Redis 24hr |

## Dependencies

### Internal
- F3: Daily Dashboard (DailyMetric model, MetricsService)
- F2: Commit Documentation Engine (commit types)

### External
- Chart.js + vue-chartjs
- date-fns (date manipulation)

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Services/Analytics/ComparisonService.php` | Comparison calculations |
| `app/Http/Controllers/AnalyticsController.php` | Analytics page controller |
| `resources/js/Pages/Analytics.vue` | Main analytics page |
| `resources/js/Components/Charts/HeatmapCalendar.vue` | Calendar heatmap |
| `resources/js/Components/Charts/ImpactTrend.vue` | Trend line chart |
| `resources/js/Components/Analytics/DateRangePicker.vue` | Date selection |
| `resources/js/Components/Analytics/AnomalyAlert.vue` | Anomaly notifications |
