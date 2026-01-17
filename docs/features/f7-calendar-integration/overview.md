# F7: Calendar Integration (Google/Outlook)

## Feature Summary

Correlate meeting time with coding output by integrating with Google Calendar and Microsoft Outlook. Helps users understand how meetings impact their productivity.

## Priority

**P1 (Post-MVP)** - Enhanced feature for Phase 3

## Goals

1. OAuth integration with Google Calendar and Outlook
2. Calculate meeting hours vs coding hours ratio
3. Identify meeting-free blocks and their productivity
4. Correlate high-productivity periods with calendar data
5. Optional: Auto-block focus time based on patterns

## Metrics

| Metric | Calculation |
|--------|-------------|
| Meeting Hours | Total hours in calendar events |
| Coding Hours | Hours with commits |
| Meeting:Coding Ratio | meeting_hours / coding_hours |
| Focus Block Productivity | Commits during meeting-free 2+ hour blocks |
| Context Switch Count | Alternations between meetings and coding |

## Acceptance Criteria

- [ ] Users can connect Google Calendar via OAuth
- [ ] Users can connect Outlook via Microsoft Graph API
- [ ] Calendar events are synced daily
- [ ] Meeting hours are calculated accurately
- [ ] Correlation analytics are displayed on dashboard
- [ ] Users can disconnect calendars at any time

## Stepwise Refinement

### Level 0: High-Level Flow

```
OAuth Connect → Event Sync → Data Correlation → Insight Display
```

### Level 1: Component Breakdown

```
┌─────────────────────────────────────────────────────────────┐
│                 Calendar Integration System                  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  OAuth Flow                                         │    │
│  │  - Google: socialiteproviders/google                │    │
│  │  - Microsoft: socialiteproviders/microsoft          │    │
│  │  - Store tokens encrypted                           │    │
│  │  - Handle token refresh                             │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  SyncCalendarEventsJob (Daily Scheduled)            │    │
│  │  1. Fetch events for last 7 days + next 7 days      │    │
│  │  2. Store in calendar_events table                  │    │
│  │  3. Calculate daily meeting hours                   │    │
│  │  4. Update calendar_metrics                         │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  CalendarAnalyticsService                           │    │
│  │  - getMeetingHours(user, date)                      │    │
│  │  - getFocusBlocks(user, date)                       │    │
│  │  - correlateMeetingsWithProductivity(user)          │    │
│  │  - identifyOptimalMeetingTimes(user)                │    │
│  └─────────────────────────────────────────────────────┘    │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Level 2: OAuth Implementation

```
Google Calendar OAuth:
┌─────────────────────────────────────────────────────────────┐
│ Scopes:                                                     │
│ - https://www.googleapis.com/auth/calendar.readonly         │
│                                                             │
│ Package: socialiteproviders/google                          │
│                                                             │
│ Token Storage:                                              │
│ - google_access_token (encrypted)                           │
│ - google_refresh_token (encrypted)                          │
│ - google_token_expires_at                                   │
└─────────────────────────────────────────────────────────────┘

Microsoft Outlook OAuth:
┌─────────────────────────────────────────────────────────────┐
│ Scopes:                                                     │
│ - Calendars.Read                                            │
│ - offline_access                                            │
│                                                             │
│ Package: socialiteproviders/microsoft                       │
│ API: Microsoft Graph API                                    │
│                                                             │
│ Token Storage:                                              │
│ - microsoft_access_token (encrypted)                        │
│ - microsoft_refresh_token (encrypted)                       │
│ - microsoft_token_expires_at                                │
└─────────────────────────────────────────────────────────────┘
```

## Data Models

### CalendarEvent Model

```php
Schema::create('calendar_events', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('provider'); // google, microsoft
    $table->string('external_id');
    $table->string('title');
    $table->timestamp('start_time');
    $table->timestamp('end_time');
    $table->integer('duration_minutes');
    $table->boolean('is_all_day')->default(false);
    $table->boolean('is_recurring')->default(false);
    $table->timestamps();

    $table->unique(['user_id', 'provider', 'external_id']);
    $table->index(['user_id', 'start_time']);
});
```

### CalendarMetric Model

```php
Schema::create('calendar_metrics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('date');
    $table->decimal('meeting_hours', 4, 2)->default(0);
    $table->integer('meeting_count')->default(0);
    $table->integer('focus_blocks')->default(0); // 2+ hour gaps
    $table->integer('focus_block_minutes')->default(0);
    $table->timestamps();

    $table->unique(['user_id', 'date']);
});
```

## Dependencies

### External
- `socialiteproviders/google`
- `socialiteproviders/microsoft`
- Google Calendar API
- Microsoft Graph API

## Security Considerations

- OAuth tokens stored encrypted
- Minimal scope requested (read-only)
- Users can revoke access at any time
- Calendar data synced locally (not stored in cloud)
- No event content stored (only title for display)

## Implementation Files

| File | Purpose |
|------|---------|
| `app/Services/Calendar/GoogleCalendarService.php` | Google API integration |
| `app/Services/Calendar/MicrosoftCalendarService.php` | Microsoft API integration |
| `app/Services/Calendar/CalendarAnalyticsService.php` | Correlation analytics |
| `app/Jobs/SyncCalendarEventsJob.php` | Daily sync job |
| `app/Http/Controllers/CalendarController.php` | OAuth and settings |
| `app/Models/CalendarEvent.php` | Event model |
| `app/Models/CalendarMetric.php` | Metrics model |
| `resources/js/Pages/Settings/Calendar.vue` | Calendar settings page |
