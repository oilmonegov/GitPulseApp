# F6: AI-Powered Performance Insights

## Feature Summary

Intelligent analysis of productivity patterns with actionable recommendations using Claude API. Identifies patterns humans would miss and generates natural language insights.

## Priority

**P1 (Post-MVP)** - Enhanced feature for Phase 2

## Goals

1. Identify high-productivity conditions (day of week, time of day, repo focus)
2. Detect potential burnout indicators (declining trend, erratic patterns)
3. Generate natural language insights
4. Provide actionable recommendations
5. Allow user feedback on insight quality

## Insight Categories

| Type | Description | Example |
|------|-------------|---------|
| Productivity | Optimal working conditions | "You ship 40% more features on Tuesdays" |
| Pattern | Behavioral observations | "Context switching between repos correlates with lower output" |
| Recommendation | Actionable suggestions | "Consider protecting Tuesday mornings for deep work" |
| Warning | Burnout/concern indicators | "Your productivity has declined 30% this month" |

## Acceptance Criteria

- [ ] Insights are generated weekly (scheduled)
- [ ] Insights are relevant and actionable
- [ ] Users can mark insights as helpful/not helpful
- [ ] Dismissed insights don't reappear
- [ ] Confidence scores are displayed
- [ ] Insights are personalized to user patterns

## Stepwise Refinement

### Level 0: High-Level Flow

```
Scheduled Job → Data Analysis → Claude API → Insight Generation → User Notification
```

### Level 1: Component Breakdown

```
┌─────────────────────────────────────────────────────────────┐
│                AI-Powered Insights System                    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  GenerateInsightsJob (Weekly Scheduled)             │    │
│  │  1. Collect user metrics (30+ days)                 │    │
│  │  2. Analyze patterns locally                        │    │
│  │  3. Send to Claude API for insight generation       │    │
│  │  4. Parse and store insights                        │    │
│  │  5. Broadcast notification                          │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  ClaudeInsightService                               │    │
│  │  - Builds prompt with user metrics                  │    │
│  │  - Calls Anthropic API                              │    │
│  │  - Parses structured response                       │    │
│  │  - Validates insights                               │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Pattern Detection (Local Analysis)                 │    │
│  │  - Day-of-week productivity                         │    │
│  │  - Time-of-day patterns                             │    │
│  │  - Repo focus vs context switching                  │    │
│  │  - Trend direction (improving/declining)            │    │
│  │  - Streak patterns                                  │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Level 2: Claude API Integration

```
Prompt Structure:
┌─────────────────────────────────────────────────────────────┐
│ System: You are a developer productivity analyst.          │
│                                                             │
│ User Data:                                                  │
│ - 30-day commit history by day                              │
│ - Hourly distribution patterns                              │
│ - Commit type breakdown                                     │
│ - Repository focus metrics                                  │
│ - Week-over-week trends                                     │
│ - Streak information                                        │
│                                                             │
│ Instructions:                                               │
│ Generate 3-5 actionable insights about this developer's    │
│ productivity patterns. Each insight should:                │
│ 1. Identify a specific pattern                             │
│ 2. Explain why it matters                                  │
│ 3. Suggest a concrete action                               │
│                                                             │
│ Return JSON array of insights with:                        │
│ - type: productivity|pattern|recommendation|warning         │
│ - title: Short headline (max 60 chars)                     │
│ - description: Full insight (max 200 chars)                │
│ - confidence: 0.0-1.0                                      │
│ - data_points: Supporting metrics                          │
└─────────────────────────────────────────────────────────────┘
```

## Data Model

### Insight Model

```php
Schema::create('insights', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('type', ['productivity', 'pattern', 'recommendation', 'warning']);
    $table->enum('period', ['daily', 'weekly', 'monthly']);
    $table->string('title');
    $table->text('description');
    $table->json('data_points')->nullable();
    $table->decimal('confidence', 3, 2)->default(0.80);
    $table->boolean('is_read')->default(false);
    $table->boolean('is_dismissed')->default(false);
    $table->boolean('is_helpful')->nullable();
    $table->date('valid_from');
    $table->date('valid_until')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'is_read', 'created_at']);
});
```

## Dependencies

### Internal
- F3: Daily Dashboard (metrics data)
- F5: Comparative Analytics (trend data)

### External
- Anthropic PHP SDK (`anthropic/anthropic-php`)
- Laravel Queue (background processing)

## Configuration

### Environment Variables

```env
ANTHROPIC_API_KEY=sk-ant-...
ANTHROPIC_MODEL=claude-3-5-sonnet-20241022
```

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Services/AI/ClaudeInsightService.php` | Claude API integration |
| `app/Jobs/GenerateInsightsJob.php` | Weekly insight generation |
| `app/Models/Insight.php` | Insight model |
| `app/Http/Controllers/InsightController.php` | Insight CRUD |
| `resources/js/Pages/Insights.vue` | Insights page |
| `resources/js/Components/InsightCard.vue` | Insight display |

## Error Handling

| Scenario | Handling |
|----------|----------|
| API rate limit | Retry with exponential backoff |
| API error | Log, skip generation, notify admin |
| Invalid response | Discard, use fallback insights |
| Low confidence (<0.5) | Filter out, don't show to user |

## Privacy Considerations

- Only aggregate metrics sent to Claude (no raw commit messages)
- No personally identifiable information in prompts
- User can opt-out of AI insights
- Data retention: insights expire after 90 days
