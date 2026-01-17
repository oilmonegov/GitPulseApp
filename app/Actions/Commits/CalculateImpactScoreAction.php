<?php

declare(strict_types=1);

namespace App\Actions\Commits;

use App\Constants\CommitType;
use App\Contracts\Action;
use App\DTOs\Commits\ParsedCommitData;
use Carbon\CarbonInterface;

/**
 * Calculates the impact score for a commit.
 *
 * The impact score is a weighted measure of how significant a commit is,
 * based on multiple factors including code changes, commit type, and timing.
 *
 * Formula: Score = Σ(weight × factor_score) × 10
 *
 * Factors:
 * - Lines changed (20%): min((additions + deletions) / repo_avg, 2.0)
 * - Files touched (15%): min(files_changed / 5, 1.5)
 * - Commit type (25%): feat=1.0, fix=0.8, refactor=0.7, etc.
 * - Merge commit (20%): merge=1.5, regular=0.5
 * - External refs (10%): has_refs=1.0, no_refs=0.5
 * - Focus time (10%): peak_hours=1.2, normal=1.0, late=0.8
 */
final class CalculateImpactScoreAction implements Action
{
    /**
     * Factor weights for impact score calculation.
     */
    private const WEIGHTS = [
        'lines_changed' => 0.20,
        'files_touched' => 0.15,
        'commit_type' => 0.25,
        'merge_commit' => 0.20,
        'external_refs' => 0.10,
        'focus_time' => 0.10,
    ];

    /**
     * Default average lines changed per commit (used when no repository average available).
     */
    private const DEFAULT_AVG_LINES = 50;

    /**
     * Peak productivity hours (9 AM - 5 PM).
     */
    private const PEAK_HOURS_START = 9;

    private const PEAK_HOURS_END = 17;

    /**
     * Late night hours (11 PM - 5 AM) - reduced productivity assumed.
     */
    private const LATE_NIGHT_START = 23;

    private const LATE_NIGHT_END = 5;

    public function __construct(
        private readonly ParsedCommitData $parsedData,
        private readonly int $additions,
        private readonly int $deletions,
        private readonly int $filesChanged,
        private readonly CarbonInterface $committedAt,
        private readonly ?float $repositoryAvgLines = null,
    ) {}

    /**
     * Calculate the impact score.
     *
     * @return float Score typically between 0 and 10+
     */
    public function execute(): float
    {
        $factors = [
            'lines_changed' => $this->calculateLinesChangedFactor(),
            'files_touched' => $this->calculateFilesTouchedFactor(),
            'commit_type' => $this->calculateCommitTypeFactor(),
            'merge_commit' => $this->calculateMergeCommitFactor(),
            'external_refs' => $this->calculateExternalRefsFactor(),
            'focus_time' => $this->calculateFocusTimeFactor(),
        ];

        $score = 0.0;

        foreach ($factors as $factor => $value) {
            $score += self::WEIGHTS[$factor] * $value;
        }

        // Multiply by 10 to get a 0-10+ scale
        return round($score * 10, 2);
    }

    /**
     * Get a breakdown of all factor scores for debugging/display.
     *
     * @return array<string, array{weight: float, score: float, weighted: float}>
     */
    public function getBreakdown(): array
    {
        $breakdown = [];

        $factors = [
            'lines_changed' => $this->calculateLinesChangedFactor(),
            'files_touched' => $this->calculateFilesTouchedFactor(),
            'commit_type' => $this->calculateCommitTypeFactor(),
            'merge_commit' => $this->calculateMergeCommitFactor(),
            'external_refs' => $this->calculateExternalRefsFactor(),
            'focus_time' => $this->calculateFocusTimeFactor(),
        ];

        foreach ($factors as $factor => $value) {
            $breakdown[$factor] = [
                'weight' => self::WEIGHTS[$factor],
                'score' => round($value, 2),
                'weighted' => round(self::WEIGHTS[$factor] * $value, 4),
            ];
        }

        return $breakdown;
    }

    /**
     * Calculate the lines changed factor.
     *
     * min((additions + deletions) / repo_avg, 2.0)
     */
    private function calculateLinesChangedFactor(): float
    {
        $totalLines = $this->additions + $this->deletions;
        $avgLines = $this->repositoryAvgLines ?? self::DEFAULT_AVG_LINES;

        if ($avgLines <= 0) {
            $avgLines = self::DEFAULT_AVG_LINES;
        }

        return min($totalLines / $avgLines, 2.0);
    }

    /**
     * Calculate the files touched factor.
     *
     * min(files_changed / 5, 1.5)
     */
    private function calculateFilesTouchedFactor(): float
    {
        return min($this->filesChanged / 5, 1.5);
    }

    /**
     * Calculate the commit type factor.
     *
     * Uses the weight from the CommitType enum.
     */
    private function calculateCommitTypeFactor(): float
    {
        return $this->parsedData->type->weight();
    }

    /**
     * Calculate the merge commit factor.
     *
     * merge=1.5, regular=0.5
     */
    private function calculateMergeCommitFactor(): float
    {
        return $this->parsedData->isMerge ? 1.5 : 0.5;
    }

    /**
     * Calculate the external references factor.
     *
     * has_refs=1.0, no_refs=0.5
     */
    private function calculateExternalRefsFactor(): float
    {
        return $this->parsedData->hasExternalRefs() ? 1.0 : 0.5;
    }

    /**
     * Calculate the focus time factor based on commit hour.
     *
     * peak_hours=1.2, normal=1.0, late=0.8
     */
    private function calculateFocusTimeFactor(): float
    {
        $hour = (int) $this->committedAt->format('G');

        // Peak hours: 9 AM - 5 PM
        if ($hour >= self::PEAK_HOURS_START && $hour < self::PEAK_HOURS_END) {
            return 1.2;
        }

        // Late night: 11 PM - 5 AM
        if ($hour >= self::LATE_NIGHT_START || $hour < self::LATE_NIGHT_END) {
            return 0.8;
        }

        // Normal hours
        return 1.0;
    }
}
