# F3: Backend Implementation

## Controller

### DashboardController

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Analytics\MetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, MetricsService $metrics): Response
    {
        $user = $request->user();
        $today = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        return Inertia::render('Dashboard', [
            // Lazy props for parallel loading
            'todayMetrics' => fn () => $metrics->getForDate($user, $today),
            'yesterdayMetrics' => fn () => $metrics->getForDate($user, $yesterday),
            'recentCommits' => fn () => $user->commits()
                ->with('repository:id,name,full_name')
                ->whereDate('committed_at', $today)
                ->latest('committed_at')
                ->take(20)
                ->get(),
            'weeklyTrend' => fn () => $metrics->getWeeklyTrend($user),
            'streak' => fn () => $metrics->getCurrentStreak($user),
            'repositories' => fn () => $user->repositories()
                ->where('is_active', true)
                ->get(['id', 'name', 'full_name']),
            'hourlyDistribution' => fn () => $metrics->getHourlyDistribution($user, $today),
            'typeBreakdown' => fn () => $metrics->getTypeBreakdown($user, $today),
        ]);
    }
}
```

---

## Services

### MetricsService

```php
<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\Commit;
use App\Models\DailyMetric;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class MetricsService
{
    public function getForDate(User $user, string $date): array
    {
        $cacheKey = "metrics:{$user->id}:{$date}";
        $ttl = $date === now()->toDateString() ? 300 : 3600; // 5min today, 1hr historical

        return Cache::remember($cacheKey, $ttl, function () use ($user, $date) {
            $metric = DailyMetric::where('user_id', $user->id)
                ->where('date', $date)
                ->first();

            if ($metric) {
                return $metric->toArray();
            }

            // Calculate on-the-fly if no aggregated metric exists
            return $this->calculateMetricsForDate($user, $date);
        });
    }

    public function calculateMetricsForDate(User $user, string $date): array
    {
        $commits = Commit::where('user_id', $user->id)
            ->whereDate('committed_at', $date)
            ->get();

        return [
            'date' => $date,
            'total_commits' => $commits->count(),
            'total_impact' => round($commits->sum('impact_score'), 2),
            'repos_active' => $commits->pluck('repository_id')->unique()->count(),
            'hours_active' => $this->calculateHoursActive($commits),
            'commit_types' => $commits->groupBy('commit_type')
                ->map->count()
                ->toArray(),
            'additions' => $commits->sum('additions'),
            'deletions' => $commits->sum('deletions'),
        ];
    }

    public function getWeeklyTrend(User $user): Collection
    {
        $cacheKey = "weekly_trend:{$user->id}";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $startDate = now()->subDays(6)->startOfDay();
            $endDate = now()->endOfDay();

            return DailyMetric::where('user_id', $user->id)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->orderBy('date')
                ->get(['date', 'total_commits', 'total_impact'])
                ->keyBy('date');
        });
    }

    public function getCurrentStreak(User $user): int
    {
        $streakKey = "user:{$user->id}:streak";

        // Check Redis first for real-time value
        $cachedStreak = Redis::get($streakKey);

        if ($cachedStreak !== null) {
            return (int) $cachedStreak;
        }

        // Calculate from database
        $streak = $this->calculateStreak($user);
        Redis::setex($streakKey, 86400, $streak);

        return $streak;
    }

    private function calculateStreak(User $user): int
    {
        $streak = 0;
        $date = now()->toDateString();

        // Check if there's a commit today
        $hasCommitToday = Commit::where('user_id', $user->id)
            ->whereDate('committed_at', $date)
            ->exists();

        if (! $hasCommitToday) {
            // Start checking from yesterday
            $date = now()->subDay()->toDateString();
        }

        // Count consecutive days
        while (true) {
            $hasCommit = Commit::where('user_id', $user->id)
                ->whereDate('committed_at', $date)
                ->exists();

            if (! $hasCommit) {
                break;
            }

            $streak++;
            $date = date('Y-m-d', strtotime($date . ' -1 day'));

            // Safety limit
            if ($streak > 365) {
                break;
            }
        }

        return $streak;
    }

    public function getHourlyDistribution(User $user, string $date): array
    {
        $commits = Commit::where('user_id', $user->id)
            ->whereDate('committed_at', $date)
            ->get(['committed_at']);

        $distribution = array_fill(0, 24, 0);

        foreach ($commits as $commit) {
            $hour = $commit->committed_at->hour;
            $distribution[$hour]++;
        }

        return $distribution;
    }

    public function getTypeBreakdown(User $user, string $date): array
    {
        return Commit::where('user_id', $user->id)
            ->whereDate('committed_at', $date)
            ->groupBy('commit_type')
            ->select('commit_type', DB::raw('count(*) as count'))
            ->pluck('count', 'commit_type')
            ->toArray();
    }

    private function calculateHoursActive(Collection $commits): float
    {
        if ($commits->isEmpty()) {
            return 0;
        }

        $hours = $commits->pluck('committed_at')
            ->map(fn ($dt) => $dt->format('Y-m-d H'))
            ->unique()
            ->count();

        return round($hours, 2);
    }

    public function invalidateCache(User $user, string $date): void
    {
        Cache::forget("metrics:{$user->id}:{$date}");
        Cache::forget("weekly_trend:{$user->id}");
    }
}
```

---

## Jobs

### CalculateDailyMetrics Job

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\DailyMetricsUpdated;
use App\Models\Commit;
use App\Models\DailyMetric;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CalculateDailyMetrics implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $date,
    ) {}

    public function handle(): void
    {
        $commits = Commit::where('user_id', $this->userId)
            ->whereDate('committed_at', $this->date)
            ->get();

        $hourlyDistribution = array_fill(0, 24, 0);
        foreach ($commits as $commit) {
            $hourlyDistribution[$commit->committed_at->hour]++;
        }

        $metric = DailyMetric::updateOrCreate(
            [
                'user_id' => $this->userId,
                'date' => $this->date,
            ],
            [
                'total_commits' => $commits->count(),
                'total_impact' => round($commits->sum('impact_score'), 2),
                'repos_active' => $commits->pluck('repository_id')->unique()->count(),
                'hours_active' => $commits->pluck('committed_at')
                    ->map(fn ($dt) => $dt->format('Y-m-d H'))
                    ->unique()
                    ->count(),
                'commit_types' => $commits->groupBy('commit_type')
                    ->map->count()
                    ->toArray(),
                'hourly_distribution' => $hourlyDistribution,
                'additions' => $commits->sum('additions'),
                'deletions' => $commits->sum('deletions'),
            ]
        );

        // Broadcast for real-time dashboard updates
        broadcast(new DailyMetricsUpdated($metric))->toOthers();
    }
}
```

---

## Broadcasting Events

### DailyMetricsUpdated

```php
<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\DailyMetric;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DailyMetricsUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public DailyMetric $metric,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->metric->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'date' => $this->metric->date,
            'total_commits' => $this->metric->total_commits,
            'total_impact' => $this->metric->total_impact,
            'repos_active' => $this->metric->repos_active,
            'hours_active' => $this->metric->hours_active,
        ];
    }

    public function broadcastAs(): string
    {
        return 'DailyMetricsUpdated';
    }
}
```

---

## Models

### DailyMetric Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'total_commits',
        'total_impact',
        'repos_active',
        'hours_active',
        'commit_types',
        'hourly_distribution',
        'additions',
        'deletions',
    ];

    protected $casts = [
        'date' => 'date',
        'total_impact' => 'decimal:2',
        'hours_active' => 'decimal:2',
        'commit_types' => 'array',
        'hourly_distribution' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeForDateRange($query, string $start, string $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }
}
```

---

## Routes

```php
// routes/web.php
use App\Http\Controllers\DashboardController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
```

---

## Caching Strategy

### Redis Keys

```php
// Real-time counters (updated on each commit)
"user:{userId}:today:commits"     // Integer counter
"user:{userId}:today:impact"      // Float counter
"user:{userId}:streak"            // Integer (streak count)
"user:{userId}:last_commit_date"  // Date string

// Cached aggregations
"metrics:{userId}:{date}"         // Full metrics array
"weekly_trend:{userId}"           // 7-day trend data
```

### Cache Invalidation

```php
// Called when commit is processed
$metricsService->invalidateCache($user, $date);

// Clear Redis counters at midnight (scheduled)
Redis::del("user:{$userId}:today:commits");
Redis::del("user:{$userId}:today:impact");
```
