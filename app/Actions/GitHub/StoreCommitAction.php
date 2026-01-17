<?php

declare(strict_types=1);

namespace App\Actions\GitHub;

use App\Contracts\Action;
use App\DTOs\GitHub\CommitData;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;

/**
 * Stores a commit in the database from GitHub webhook/API data.
 *
 * TODO [Sprint 3]: After storing commit, calculate impact score using:
 * 1. Parse message with ParseCommitMessageAction
 * 2. Calculate score with CalculateImpactScoreAction
 * 3. Update commit's impact_score field
 *
 * @see \App\Actions\Commits\ParseCommitMessageAction
 * @see \App\Actions\Commits\CalculateImpactScoreAction
 */
final class StoreCommitAction implements Action
{
    public function __construct(
        private readonly Repository $repository,
        private readonly User $user,
        private readonly CommitData $commitData,
    ) {}

    /**
     * Execute the action.
     *
     * @return Commit|null The created commit or null if it already exists
     */
    public function execute(): ?Commit
    {
        // Skip if commit already exists (idempotency)
        if (Commit::query()->where('sha', $this->commitData->sha)->exists()) {
            return null;
        }

        return Commit::query()->create([
            'repository_id' => $this->repository->id,
            'user_id' => $this->user->id,
            ...$this->commitData->toArray(),
        ]);
    }
}
