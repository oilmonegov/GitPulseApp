# F4: Weekly Report Generator

## Feature Summary

Automated generation of stakeholder-ready weekly summaries. Reports compile commit activity into professional documents suitable for managers, stakeholders, and performance reviews.

## Priority

**P0 (MVP)** - Core feature required for launch

## Goals

1. Auto-generate reports every Friday at configurable time
2. Include sections: Summary stats, Key accomplishments, Projects touched, Trends
3. Support export formats: PDF, Markdown, HTML email
4. Allow editing before sending (add context, remove items)
5. Provide template customization (formal vs casual tone)

## Report Structure

```
Weekly Engineering Report: [Date Range]

SUMMARY
- Total commits: X (+Y% vs last week)
- Features shipped: X
- Bugs fixed: X
- Repos contributed to: X

KEY ACCOMPLISHMENTS
1. [Auto-generated from high-impact commits]
2. [Grouped by project/feature]
3. [Includes PR merges and issue closures]

TRENDS
- Most productive day: [Day]
- Focus areas: [Top 3 repos by activity]
- Week-over-week change: [+/-X%]

NEXT WEEK PREVIEW
[Optional: User-added goals]
```

## Acceptance Criteria

- [ ] Reports auto-generate on configured schedule
- [ ] Reports can be manually triggered at any time
- [ ] PDF export produces professional-quality documents
- [ ] Markdown export is properly formatted
- [ ] Reports can be edited before finalizing
- [ ] Week-over-week comparisons are accurate
- [ ] Reports can be shared via email

## Stepwise Refinement

### Level 0: High-Level Flow

```
Schedule/Manual Trigger → Data Aggregation → Report Generation → User Review → Export/Send
```

### Level 1: Component Breakdown

```
┌─────────────────────────────────────────────────────────────┐
│                 Weekly Report Generator                      │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  GenerateWeeklyReportJob (Scheduled)                │    │
│  │  - Runs Friday 5pm user timezone                    │    │
│  │  - Can be manually triggered                        │    │
│  └─────────────────────┬───────────────────────────────┘    │
│                        │                                     │
│                        ▼                                     │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  GenerateWeeklyReport Action                        │    │
│  │  1. Aggregate DailyMetrics for week                 │    │
│  │  2. Identify high-impact commits                    │    │
│  │  3. Calculate WoW changes                           │    │
│  │  4. Group accomplishments by project                │    │
│  │  5. Create WeeklyReport record                      │    │
│  └─────────────────────┬───────────────────────────────┘    │
│                        │                                     │
│                        ▼                                     │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  ExportReportToPdf Action                           │    │
│  │  - Uses Laravel DomPDF                              │    │
│  │  - Stores in S3/Spaces                              │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Level 2: Data Aggregation

```
Week Period: Monday 00:00 → Sunday 23:59 (user timezone)

Data Sources:
├── DailyMetric records (7 days)
├── Commit records (filtered by committed_at)
├── Previous WeeklyReport (for WoW comparison)
└── User preferences (tone, included sections)

Aggregations:
├── total_commits: SUM(daily_metrics.total_commits)
├── total_impact: SUM(daily_metrics.total_impact)
├── features_shipped: COUNT(commits WHERE type = 'feat')
├── bugs_fixed: COUNT(commits WHERE type = 'fix')
├── repos_active: DISTINCT(commits.repository_id)
├── most_productive_day: MAX(daily_metrics.total_commits)
└── accomplishments: TOP 10 commits BY impact_score
```

## Dependencies

### Internal
- F1: GitHub Webhook Integration (data source)
- F2: Commit Documentation Engine (impact scores)
- F3: Daily Dashboard (DailyMetric model)
- User preferences model

### External
- Laravel DomPDF (`barryvdh/laravel-dompdf`)
- Laravel Task Scheduling
- Mail driver (Postmark/Mailgun)

## Configuration

### User Preferences

```php
// User model preferences JSON
{
    "report_schedule": "friday",
    "report_time": "17:00",
    "report_timezone": "America/New_York",
    "report_tone": "professional",
    "report_sections": ["summary", "accomplishments", "trends"],
    "report_auto_send": false,
    "report_email_recipients": ["manager@example.com"]
}
```

### Scheduling

```php
// app/Console/Kernel.php
$schedule->job(new GenerateWeeklyReportJob($user))
    ->weeklyOn(5, '17:00')
    ->timezone($user->timezone);
```

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Jobs/GenerateWeeklyReportJob.php` | Scheduled job |
| `app/Actions/Reports/GenerateWeeklyReport.php` | Report generation |
| `app/Actions/Reports/ExportReportToPdf.php` | PDF export |
| `app/Models/WeeklyReport.php` | Report model |
| `app/Http/Controllers/ReportController.php` | CRUD controller |
| `resources/views/reports/pdf.blade.php` | PDF template |
| `resources/js/Pages/Reports/*.vue` | Report pages |
