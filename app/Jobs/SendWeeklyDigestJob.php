<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\WeeklyDigestMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDigestJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $startDate = Carbon::now()->subWeek()->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        User::query()
            ->whereNotNull('email_verified_at')
            ->whereNotNull('github_id')
            ->cursor()
            ->each(function (User $user) use ($startDate, $endDate): void {
                // Check if user has weekly digest enabled
                $preferences = $user->preferences ?? [];
                $notifications = $preferences['notifications'] ?? [];

                if (! ($notifications['weekly_digest'] ?? false)) {
                    return;
                }

                // Get commit statistics for the week
                $weeklyStats = $this->getWeeklyStats($user, $startDate, $endDate);

                // Only send if there's activity to report
                if ($weeklyStats['total_commits'] === 0) {
                    return;
                }

                Mail::to($user)->queue(new WeeklyDigestMail($user, $weeklyStats));
            });
    }

    /**
     * Get weekly commit statistics for a user.
     *
     * @return array{total_commits: int, total_additions: int, total_deletions: int, average_impact: float, top_repositories: array<int, array{name: string, commits: int}>}
     */
    private function getWeeklyStats(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $commits = $user->commits()
            ->whereBetween('committed_at', [$startDate, $endDate])
            ->with('repository:id,name,full_name')
            ->get();

        $topRepositories = $commits->groupBy('repository_id')
            ->map(fn ($repoCommits) => [
                'name' => $repoCommits->first()?->repository->full_name ?? 'Unknown',
                'commits' => $repoCommits->count(),
            ])
            ->sortByDesc('commits')
            ->take(5)
            ->values()
            ->toArray();

        return [
            'total_commits' => $commits->count(),
            'total_additions' => (int) $commits->sum('additions'),
            'total_deletions' => (int) $commits->sum('deletions'),
            'average_impact' => round($commits->avg('impact_score') ?? 0, 2),
            'top_repositories' => $topRepositories,
        ];
    }
}
