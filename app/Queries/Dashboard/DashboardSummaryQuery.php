<?php

declare(strict_types=1);

namespace App\Queries\Dashboard;

use App\Contracts\Query;
use App\Models\Commit;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Aggregates dashboard summary statistics for a user's commits.
 *
 * @return array{total_commits: int, average_impact: float, lines_changed: int}
 */
final class DashboardSummaryQuery implements Query
{
    public function __construct(
        private readonly User $user,
        private readonly ?Carbon $startDate = null,
        private readonly ?Carbon $endDate = null,
    ) {}

    /**
     * @return array{total_commits: int, average_impact: float, lines_changed: int}
     */
    public function get(): array
    {
        $query = Commit::query()
            ->where('user_id', $this->user->id);

        if ($this->startDate !== null && $this->endDate !== null) {
            $query->betweenDates($this->startDate, $this->endDate);
        }

        $stats = $query->selectRaw('
            COUNT(*) as total_commits,
            COALESCE(AVG(impact_score), 0) as average_impact,
            COALESCE(SUM(additions + deletions), 0) as lines_changed
        ')->first();

        return [
            'total_commits' => (int) ($stats->total_commits ?? 0),
            'average_impact' => round((float) ($stats->average_impact ?? 0), 2),
            'lines_changed' => (int) ($stats->lines_changed ?? 0),
        ];
    }
}
