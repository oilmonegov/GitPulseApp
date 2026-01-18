<?php

declare(strict_types=1);

namespace App\Queries\Dashboard;

use App\Concerns\DatabaseCompatible;
use App\Contracts\Query;
use App\Models\Commit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Retrieves daily commit counts for the specified date range.
 * Fills gaps with zero counts for days without commits.
 *
 * @return Collection<int, array{date: string, count: int}>
 */
final class CommitsOverTimeQuery implements Query
{
    use DatabaseCompatible;

    public function __construct(
        private readonly User $user,
        private readonly Carbon $startDate,
        private readonly Carbon $endDate,
    ) {}

    /**
     * @return Collection<int, array{date: string, count: int}>
     */
    public function get(): Collection
    {
        $dateExpression = $this->dateFormat('committed_at', 'Y-m-d');

        /** @var Collection<string, int> $commitCounts */
        $commitCounts = Commit::query()
            ->where('user_id', $this->user->id)
            ->betweenDates($this->startDate, $this->endDate)
            ->selectRaw("{$dateExpression} as date, COUNT(*) as count")
            ->groupByRaw($dateExpression)
            ->orderByRaw($dateExpression)
            ->pluck('count', 'date');

        return $this->fillGaps($commitCounts);
    }

    /**
     * Fill date gaps with zero counts.
     *
     * @param  Collection<string, int>  $commitCounts
     *
     * @return Collection<int, array{date: string, count: int}>
     */
    private function fillGaps(Collection $commitCounts): Collection
    {
        $result = collect();
        $current = $this->startDate->copy()->startOfDay();
        $end = $this->endDate->copy()->endOfDay();

        while ($current <= $end) {
            $dateKey = $current->format('Y-m-d');
            $result->push([
                'date' => $dateKey,
                'count' => (int) ($commitCounts->get($dateKey, 0)),
            ]);
            $current->addDay();
        }

        return $result;
    }
}
