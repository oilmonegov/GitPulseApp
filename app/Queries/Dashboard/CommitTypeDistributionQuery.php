<?php

declare(strict_types=1);

namespace App\Queries\Dashboard;

use App\Constants\CommitType;
use App\Contracts\Query;
use App\Models\Commit;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Retrieves commit counts grouped by commit type with associated colors.
 *
 * @return Collection<int, array{type: string, label: string, count: int, color: string}>
 */
final class CommitTypeDistributionQuery implements Query
{
    /**
     * Chart colors for each commit type (hex values for Chart.js).
     *
     * @var array<string, string>
     */
    private const array CHART_COLORS = [
        'feat' => '#16a34a',     // green-600
        'fix' => '#dc2626',      // red-600
        'docs' => '#2563eb',     // blue-600
        'style' => '#db2777',    // pink-600
        'refactor' => '#ca8a04', // yellow-600
        'perf' => '#9333ea',     // purple-600
        'test' => '#0891b2',     // cyan-600
        'build' => '#ea580c',    // orange-600
        'ci' => '#4f46e5',       // indigo-600
        'chore' => '#4b5563',    // gray-600
        'revert' => '#d97706',   // amber-600
        'other' => '#64748b',    // slate-600
    ];

    public function __construct(
        private readonly User $user,
        private readonly ?Carbon $startDate = null,
        private readonly ?Carbon $endDate = null,
    ) {}

    /**
     * @return Collection<int, array{type: string, label: string, count: int, color: string}>
     */
    public function get(): Collection
    {
        $query = Commit::query()
            ->where('user_id', $this->user->id);

        if ($this->startDate !== null && $this->endDate !== null) {
            $query->betweenDates($this->startDate, $this->endDate);
        }

        $distribution = $query
            ->selectRaw('commit_type, COUNT(*) as count')
            ->groupBy('commit_type')
            ->orderByDesc('count')
            ->get();

        return $distribution->map(function (Commit $item): array {
            // Handle both enum instances (from model cast) and string values
            $type = $item->commit_type instanceof CommitType
                ? $item->commit_type
                : (CommitType::tryFrom((string) $item->commit_type) ?? CommitType::Other);

            /** @var int|string $count */
            $count = $item->getAttribute('count');

            return [
                'type' => $type->value,
                'label' => $type->displayName(),
                'count' => (int) $count,
                'color' => self::CHART_COLORS[$type->value] ?? self::CHART_COLORS['other'],
            ];
        });
    }
}
