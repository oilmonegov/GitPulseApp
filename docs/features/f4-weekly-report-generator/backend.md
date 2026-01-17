# F4: Backend Implementation

## Models

### WeeklyReport Model

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'week_start',
        'week_end',
        'summary_stats',
        'accomplishments',
        'trends',
        'insights',
        'user_notes',
        'pdf_path',
        'status',
        'generated_at',
        'sent_at',
    ];

    protected $casts = [
        'week_start' => 'date',
        'week_end' => 'date',
        'summary_stats' => 'array',
        'accomplishments' => 'array',
        'trends' => 'array',
        'insights' => 'array',
        'status' => ReportStatus::class,
        'generated_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForWeek($query, string $weekStart)
    {
        return $query->where('week_start', $weekStart);
    }

    public function markAsGenerated(): void
    {
        $this->update([
            'status' => ReportStatus::GENERATED,
            'generated_at' => now(),
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => ReportStatus::SENT,
            'sent_at' => now(),
        ]);
    }
}
```

### ReportStatus Enum

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStatus: string
{
    case DRAFT = 'draft';
    case GENERATED = 'generated';
    case SENT = 'sent';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::GENERATED => 'Ready',
            self::SENT => 'Sent',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'yellow',
            self::GENERATED => 'green',
            self::SENT => 'blue',
        };
    }
}
```

---

## Actions

### GenerateWeeklyReport Action

```php
<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Enums\CommitType;
use App\Models\Commit;
use App\Models\DailyMetric;
use App\Models\User;
use App\Models\WeeklyReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class GenerateWeeklyReport
{
    public function execute(User $user, ?Carbon $weekStart = null): WeeklyReport
    {
        $weekStart = $weekStart ?? now()->startOfWeek();
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Aggregate metrics
        $summaryStats = $this->calculateSummaryStats($user, $weekStart, $weekEnd);
        $accomplishments = $this->extractAccomplishments($user, $weekStart, $weekEnd);
        $trends = $this->calculateTrends($user, $weekStart, $weekEnd);

        return WeeklyReport::updateOrCreate(
            [
                'user_id' => $user->id,
                'week_start' => $weekStart->toDateString(),
            ],
            [
                'week_end' => $weekEnd->toDateString(),
                'summary_stats' => $summaryStats,
                'accomplishments' => $accomplishments,
                'trends' => $trends,
                'status' => 'draft',
            ]
        );
    }

    private function calculateSummaryStats(User $user, Carbon $start, Carbon $end): array
    {
        $metrics = DailyMetric::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $commits = Commit::where('user_id', $user->id)
            ->whereBetween('committed_at', [$start, $end])
            ->get();

        // Previous week for comparison
        $prevStart = $start->copy()->subWeek();
        $prevEnd = $end->copy()->subWeek();
        $prevMetrics = DailyMetric::where('user_id', $user->id)
            ->whereBetween('date', [$prevStart->toDateString(), $prevEnd->toDateString()])
            ->get();

        $totalCommits = $metrics->sum('total_commits');
        $prevTotalCommits = $prevMetrics->sum('total_commits');
        $changePercent = $prevTotalCommits > 0
            ? round((($totalCommits - $prevTotalCommits) / $prevTotalCommits) * 100)
            : 0;

        return [
            'total_commits' => $totalCommits,
            'total_impact' => round($metrics->sum('total_impact'), 2),
            'features_shipped' => $commits->where('commit_type', CommitType::FEAT)->count(),
            'bugs_fixed' => $commits->where('commit_type', CommitType::FIX)->count(),
            'repos_active' => $commits->pluck('repository_id')->unique()->count(),
            'additions' => $metrics->sum('additions'),
            'deletions' => $metrics->sum('deletions'),
            'change_percent' => $changePercent,
            'previous_commits' => $prevTotalCommits,
        ];
    }

    private function extractAccomplishments(User $user, Carbon $start, Carbon $end): array
    {
        // Get high-impact commits
        $topCommits = Commit::where('user_id', $user->id)
            ->whereBetween('committed_at', [$start, $end])
            ->with('repository:id,name')
            ->orderByDesc('impact_score')
            ->take(10)
            ->get();

        // Group by repository
        $byRepo = $topCommits->groupBy('repository.name')->map(function ($commits) {
            return $commits->map(fn ($c) => [
                'message' => $c->message,
                'type' => $c->commit_type->value,
                'impact' => $c->impact_score,
                'sha' => substr($c->sha, 0, 7),
            ])->values()->toArray();
        });

        return $byRepo->toArray();
    }

    private function calculateTrends(User $user, Carbon $start, Carbon $end): array
    {
        $metrics = DailyMetric::where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        // Most productive day
        $mostProductiveDay = $metrics->sortByDesc('total_commits')->first();

        // Commit type distribution
        $typeDistribution = $metrics->pluck('commit_types')
            ->filter()
            ->reduce(function ($carry, $types) {
                foreach ($types as $type => $count) {
                    $carry[$type] = ($carry[$type] ?? 0) + $count;
                }
                return $carry;
            }, []);

        // Top repos
        $commits = Commit::where('user_id', $user->id)
            ->whereBetween('committed_at', [$start, $end])
            ->with('repository:id,name')
            ->get();

        $topRepos = $commits->groupBy('repository.name')
            ->map->count()
            ->sortDesc()
            ->take(3)
            ->toArray();

        return [
            'most_productive_day' => $mostProductiveDay?->date?->format('l'),
            'most_productive_count' => $mostProductiveDay?->total_commits ?? 0,
            'type_distribution' => $typeDistribution,
            'top_repos' => $topRepos,
            'daily_average' => round($metrics->avg('total_commits'), 1),
        ];
    }
}
```

### ExportReportToPdf Action

```php
<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Models\WeeklyReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ExportReportToPdf
{
    public function execute(WeeklyReport $report): string
    {
        $pdf = Pdf::loadView('reports.pdf', [
            'report' => $report,
            'user' => $report->user,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('isRemoteEnabled', true);

        $filename = sprintf(
            'reports/%d/weekly-%s.pdf',
            $report->user_id,
            $report->week_start->format('Y-m-d')
        );

        Storage::disk('s3')->put($filename, $pdf->output());

        $report->update(['pdf_path' => $filename]);

        return Storage::disk('s3')->url($filename);
    }
}
```

---

## Jobs

### GenerateWeeklyReportJob

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\Reports\ExportReportToPdf;
use App\Actions\Reports\GenerateWeeklyReport;
use App\Events\ReportGenerated;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateWeeklyReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
    ) {}

    public function handle(
        GenerateWeeklyReport $generateReport,
        ExportReportToPdf $exportPdf,
    ): void {
        $report = $generateReport->execute($this->user);

        // Generate PDF
        $exportPdf->execute($report);

        // Mark as generated
        $report->markAsGenerated();

        // Broadcast for real-time notification
        broadcast(new ReportGenerated($report));

        // Auto-send if configured
        if ($this->user->preferences['report_auto_send'] ?? false) {
            SendWeeklyReportEmail::dispatch($report);
        }
    }
}
```

### ScheduleWeeklyReports Job

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScheduleWeeklyReports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Get users who should receive reports now
        User::whereNotNull('preferences->report_schedule')
            ->each(function (User $user) {
                $schedule = $user->preferences['report_schedule'] ?? 'friday';
                $time = $user->preferences['report_time'] ?? '17:00';
                $timezone = $user->preferences['report_timezone'] ?? 'UTC';

                $now = now($timezone);

                if ($this->shouldGenerateReport($now, $schedule, $time)) {
                    GenerateWeeklyReportJob::dispatch($user)
                        ->onQueue('reports');
                }
            });
    }

    private function shouldGenerateReport($now, string $schedule, string $time): bool
    {
        $dayMatch = match ($schedule) {
            'friday' => $now->isFriday(),
            'monday' => $now->isMonday(),
            'sunday' => $now->isSunday(),
            default => false,
        };

        if (! $dayMatch) {
            return false;
        }

        [$hour, $minute] = explode(':', $time);

        return $now->hour === (int) $hour && $now->minute === (int) $minute;
    }
}
```

---

## Controllers

### ReportController

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Reports\ExportReportToPdf;
use App\Actions\Reports\GenerateWeeklyReport;
use App\Http\Requests\UpdateReportRequest;
use App\Jobs\SendWeeklyReportEmail;
use App\Models\WeeklyReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $reports = $request->user()
            ->weeklyReports()
            ->latest('week_start')
            ->paginate(10);

        return Inertia::render('Reports/Index', [
            'reports' => $reports,
        ]);
    }

    public function show(WeeklyReport $report): Response
    {
        $this->authorize('view', $report);

        return Inertia::render('Reports/Show', [
            'report' => $report->load('user'),
        ]);
    }

    public function edit(WeeklyReport $report): Response
    {
        $this->authorize('update', $report);

        return Inertia::render('Reports/Edit', [
            'report' => $report,
        ]);
    }

    public function update(UpdateReportRequest $request, WeeklyReport $report): RedirectResponse
    {
        $this->authorize('update', $report);

        $report->update($request->validated());

        return redirect()->route('reports.show', $report)
            ->with('success', 'Report updated successfully.');
    }

    public function generate(Request $request, GenerateWeeklyReport $action): RedirectResponse
    {
        $report = $action->execute($request->user());

        return redirect()->route('reports.edit', $report)
            ->with('success', 'Report generated. You can now review and edit it.');
    }

    public function downloadPdf(WeeklyReport $report, ExportReportToPdf $export): StreamedResponse
    {
        $this->authorize('view', $report);

        if (! $report->pdf_path) {
            $export->execute($report);
        }

        return Storage::disk('s3')->download($report->pdf_path);
    }

    public function downloadMarkdown(WeeklyReport $report): StreamedResponse
    {
        $this->authorize('view', $report);

        $markdown = view('reports.markdown', ['report' => $report])->render();

        return response()->streamDownload(function () use ($markdown) {
            echo $markdown;
        }, "weekly-report-{$report->week_start->format('Y-m-d')}.md");
    }

    public function send(WeeklyReport $report): RedirectResponse
    {
        $this->authorize('update', $report);

        SendWeeklyReportEmail::dispatch($report);
        $report->markAsSent();

        return redirect()->route('reports.show', $report)
            ->with('success', 'Report sent successfully.');
    }
}
```

---

## Views

### PDF Template (reports/pdf.blade.php)

```blade
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Weekly Report - {{ $report->week_start->format('M d, Y') }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #4f46e5;
            margin: 0;
        }
        .header p {
            color: #666;
            margin: 5px 0 0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h2 {
            color: #4f46e5;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .stats-grid {
            display: table;
            width: 100%;
        }
        .stat-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
        }
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #4f46e5;
        }
        .stat-label {
            color: #666;
            font-size: 10px;
        }
        .accomplishment {
            margin-bottom: 10px;
            padding: 10px;
            background: #f9fafb;
            border-radius: 4px;
        }
        .accomplishment-type {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 3px;
            margin-right: 5px;
        }
        .type-feat { background: #d1fae5; color: #065f46; }
        .type-fix { background: #fee2e2; color: #991b1b; }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #666;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Weekly Engineering Report</h1>
        <p>{{ $report->week_start->format('M d') }} - {{ $report->week_end->format('M d, Y') }}</p>
        <p>{{ $user->name }}</p>
    </div>

    <div class="section">
        <h2>Summary</h2>
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-value">{{ $report->summary_stats['total_commits'] }}</div>
                <div class="stat-label">Total Commits</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $report->summary_stats['features_shipped'] }}</div>
                <div class="stat-label">Features Shipped</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $report->summary_stats['bugs_fixed'] }}</div>
                <div class="stat-label">Bugs Fixed</div>
            </div>
            <div class="stat-item">
                <div class="stat-value">{{ $report->summary_stats['repos_active'] }}</div>
                <div class="stat-label">Repos Active</div>
            </div>
        </div>
        @if($report->summary_stats['change_percent'] != 0)
        <p style="text-align: center; margin-top: 10px;">
            <strong>{{ $report->summary_stats['change_percent'] > 0 ? '+' : '' }}{{ $report->summary_stats['change_percent'] }}%</strong>
            compared to last week
        </p>
        @endif
    </div>

    <div class="section">
        <h2>Key Accomplishments</h2>
        @foreach($report->accomplishments as $repo => $commits)
        <div class="accomplishment">
            <strong>{{ $repo }}</strong>
            <ul style="margin: 5px 0; padding-left: 20px;">
                @foreach($commits as $commit)
                <li>
                    <span class="accomplishment-type type-{{ $commit['type'] }}">{{ $commit['type'] }}</span>
                    {{ Str::limit($commit['message'], 80) }}
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>

    <div class="section">
        <h2>Trends</h2>
        <p><strong>Most Productive Day:</strong> {{ $report->trends['most_productive_day'] ?? 'N/A' }} ({{ $report->trends['most_productive_count'] ?? 0 }} commits)</p>
        <p><strong>Daily Average:</strong> {{ $report->trends['daily_average'] ?? 0 }} commits</p>
        <p><strong>Top Repositories:</strong></p>
        <ul>
            @foreach($report->trends['top_repos'] ?? [] as $repo => $count)
            <li>{{ $repo }}: {{ $count }} commits</li>
            @endforeach
        </ul>
    </div>

    @if($report->user_notes)
    <div class="section">
        <h2>Notes</h2>
        <p>{{ $report->user_notes }}</p>
    </div>
    @endif

    <div class="footer">
        Generated by GitPulse | {{ now()->format('M d, Y H:i') }}
    </div>
</body>
</html>
```

---

## Scheduling

### Kernel.php

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Check every minute for users who need reports generated
    $schedule->job(new ScheduleWeeklyReports())
        ->everyMinute()
        ->withoutOverlapping();
}
```
