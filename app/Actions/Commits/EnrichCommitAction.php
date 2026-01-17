<?php

declare(strict_types=1);

namespace App\Actions\Commits;

use App\Contracts\Action;
use App\Models\Commit;

/**
 * Enriches a commit with parsed data and impact score.
 *
 * This action is designed to be called after a commit is stored,
 * to parse its message and calculate the impact score.
 *
 * Use this in Sprint 3's webhook processing after StoreCommitAction.
 */
final class EnrichCommitAction implements Action
{
    public function __construct(
        private readonly Commit $commit,
        private readonly ?float $repositoryAvgLines = null,
    ) {}

    /**
     * Parse the commit message and calculate impact score.
     */
    public function execute(): Commit
    {
        // Parse the commit message
        $parsedData = (new ParseCommitMessageAction($this->commit->message))->execute();

        // Calculate impact score
        $impactScore = (new CalculateImpactScoreAction(
            parsedData: $parsedData,
            additions: $this->commit->additions,
            deletions: $this->commit->deletions,
            filesChanged: $this->commit->files_changed,
            committedAt: $this->commit->committed_at,
            repositoryAvgLines: $this->repositoryAvgLines,
        ))->execute();

        // Update the commit with enriched data
        $this->commit->update([
            'commit_type' => $parsedData->type,
            'scope' => $parsedData->scope,
            'external_refs' => $parsedData->externalRefs ?: null,
            'is_merge' => $parsedData->isMerge,
            'impact_score' => $impactScore,
        ]);

        return $this->commit->refresh();
    }

    /**
     * Get repository average lines changed for context.
     *
     * Call this to get the average before creating the action.
     */
    public static function getRepositoryAverage(int $repositoryId): ?float
    {
        $avg = Commit::query()
            ->where('repository_id', $repositoryId)
            ->selectRaw('AVG(additions + deletions) as avg_lines')
            ->value('avg_lines');

        return $avg !== null ? (float) $avg : null;
    }
}
