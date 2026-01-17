# F9: Goal Setting & Tracking

## Feature Summary

User-defined productivity targets with progress tracking. Enables developers to set personal goals and monitor their achievement over time.

## Priority

**P1 (Post-MVP)** - Enhanced feature for Phase 3

## Goals

1. Set weekly/monthly commit targets
2. Define focus areas (e.g., "Ship 2 features this sprint")
3. Visual progress bars toward goals
4. Notifications when falling behind pace
5. Goal history and achievement tracking

## Goal Types

| Type | Example | Measurement |
|------|---------|-------------|
| Commit Count | "20 commits this week" | total_commits >= target |
| Impact Score | "50 impact points this week" | total_impact >= target |
| Feature Count | "Ship 2 features" | feat commits >= target |
| Bug Fixes | "Fix 5 bugs" | fix commits >= target |
| Streak | "Maintain 7-day streak" | streak_days >= target |
| Focus Area | "80% commits in project X" | project_percent >= target |

## Acceptance Criteria

- [ ] Users can create goals with custom targets
- [ ] Progress is calculated in real-time
- [ ] Goals can be weekly, monthly, or custom duration
- [ ] Email/push notifications for falling behind (optional)
- [ ] Goal history is preserved for review
- [ ] Goals can be edited or deleted

## Stepwise Refinement

### Level 0: High-Level Flow

```
Create Goal → Track Progress → Update on Commit → Notify if Behind → Archive on Complete
```

### Level 1: Component Breakdown

```
┌─────────────────────────────────────────────────────────────┐
│                  Goal Tracking System                        │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Goal Management                                    │    │
│  │  - Create goal (type, target, period)               │    │
│  │  - Edit goal                                        │    │
│  │  - Delete/archive goal                              │    │
│  │  - View goal history                                │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  GoalProgressService                                │    │
│  │  - calculateProgress(goal)                          │    │
│  │  - checkPaceStatus(goal)                            │    │
│  │  - getProjectedCompletion(goal)                     │    │
│  │  - updateAllGoals(user)                             │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Notification System                                │    │
│  │  - Daily pace check job                             │    │
│  │  - Behind pace notification                         │    │
│  │  - Goal achieved notification                       │    │
│  │  - Goal expiring notification                       │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  Goal Dashboard UI                                  │    │
│  │  - Active goals with progress bars                  │    │
│  │  - Pace indicator (ahead/on track/behind)           │    │
│  │  - Goal creation modal                              │    │
│  │  - Goal history timeline                            │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Level 2: Progress Calculation

```
Progress Calculation:
┌─────────────────────────────────────────────────────────────┐
│ progress_percent = (current_value / target_value) * 100     │
│                                                             │
│ Pace Calculation:                                           │
│ days_elapsed = today - goal.start_date                      │
│ days_total = goal.end_date - goal.start_date                │
│ expected_progress = (days_elapsed / days_total) * target    │
│                                                             │
│ Pace Status:                                                │
│ - AHEAD: current >= expected * 1.1                          │
│ - ON_TRACK: current >= expected * 0.9                       │
│ - BEHIND: current < expected * 0.9                          │
│ - AT_RISK: current < expected * 0.7                         │
└─────────────────────────────────────────────────────────────┘
```

## Data Model

### Goal Model

```php
Schema::create('goals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->enum('type', ['commits', 'impact', 'features', 'fixes', 'streak', 'focus']);
    $table->integer('target_value');
    $table->integer('current_value')->default(0);
    $table->foreignId('repository_id')->nullable()->constrained(); // For focus goals
    $table->date('start_date');
    $table->date('end_date');
    $table->enum('status', ['active', 'completed', 'failed', 'cancelled'])->default('active');
    $table->boolean('notify_behind')->default(true);
    $table->timestamp('completed_at')->nullable();
    $table->timestamps();

    $table->index(['user_id', 'status', 'end_date']);
});
```

### GoalType Enum

```php
enum GoalType: string
{
    case COMMITS = 'commits';
    case IMPACT = 'impact';
    case FEATURES = 'features';
    case FIXES = 'fixes';
    case STREAK = 'streak';
    case FOCUS = 'focus';

    public function label(): string
    {
        return match ($this) {
            self::COMMITS => 'Total Commits',
            self::IMPACT => 'Impact Score',
            self::FEATURES => 'Features Shipped',
            self::FIXES => 'Bugs Fixed',
            self::STREAK => 'Streak Days',
            self::FOCUS => 'Focus Area %',
        };
    }

    public function unit(): string
    {
        return match ($this) {
            self::COMMITS, self::FEATURES, self::FIXES => 'commits',
            self::IMPACT => 'points',
            self::STREAK => 'days',
            self::FOCUS => '%',
        };
    }
}
```

## Dependencies

### Internal
- F3: Daily Dashboard (metrics for progress)
- F2: Commit Documentation Engine (commit type counts)

### External
- Laravel Notifications (email/push)

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Models/Goal.php` | Goal model |
| `app/Enums/GoalType.php` | Goal type enum |
| `app/Enums/GoalStatus.php` | Status enum |
| `app/Services/Goals/GoalProgressService.php` | Progress calculation |
| `app/Jobs/CheckGoalPaceJob.php` | Daily pace check |
| `app/Notifications/GoalBehindPace.php` | Behind notification |
| `app/Notifications/GoalCompleted.php` | Completion notification |
| `app/Http/Controllers/GoalController.php` | Goal CRUD |
| `resources/js/Pages/Goals/*.vue` | Goal pages |
| `resources/js/Components/Goals/ProgressBar.vue` | Progress display |
