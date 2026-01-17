<?php

declare(strict_types=1);

namespace App\Actions\GitHub;

use App\Contracts\Action;
use App\DTOs\GitHub\PushEventData;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use App\Queries\User\FindUserByGitHubIdQuery;
use App\Services\GitHubService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Processes a GitHub push event and stores all commits.
 */
final class ProcessPushEventAction implements Action
{
    public function __construct(
        private readonly PushEventData $pushEvent,
        private readonly bool $fetchFullCommitData = false,
    ) {}

    /**
     * Execute the action.
     *
     * @return Collection<int, Commit> Collection of stored commits
     */
    public function execute(): Collection
    {
        // Find the user who owns this repository
        $user = (new FindUserByGitHubIdQuery($this->pushEvent->senderId))->get();

        if (! $user) {
            Log::warning('Push event received for unknown user', [
                'sender_id' => $this->pushEvent->senderId,
                'sender_login' => $this->pushEvent->senderLogin,
                'repository' => $this->pushEvent->repository->fullName,
            ]);

            return collect();
        }

        // Find or create the repository
        $repository = (new FindOrCreateRepositoryAction(
            $user,
            $this->pushEvent->repository,
        ))->execute();

        // Skip if repository is inactive
        if (! $repository->is_active) {
            Log::info('Skipping push event for inactive repository', [
                'repository' => $repository->full_name,
            ]);

            return collect();
        }

        // Skip if this is a branch deletion
        if ($this->pushEvent->deleted || ! $this->pushEvent->hasCommits()) {
            return collect();
        }

        // Process distinct commits only
        $commits = $this->pushEvent->getDistinctCommits();
        $storedCommits = collect();

        foreach ($commits as $commitData) {
            // Optionally fetch full commit data from API
            $finalCommitData = $commitData;

            if ($this->fetchFullCommitData && $user->github_token) {
                $fullData = $this->fetchCommitFromApi($user, $repository, $commitData->sha);

                if ($fullData) {
                    $finalCommitData = $fullData;
                }
            }

            $commit = (new StoreCommitAction(
                $repository,
                $user,
                $finalCommitData,
            ))->execute();

            if ($commit) {
                $storedCommits->push($commit);
            }
        }

        // Update repository last sync timestamp
        $repository->update(['last_sync_at' => now()]);

        Log::info('Processed push event', [
            'repository' => $repository->full_name,
            'branch' => $this->pushEvent->getBranch(),
            'total_commits' => count($commits),
            'stored_commits' => $storedCommits->count(),
        ]);

        return $storedCommits;
    }

    /**
     * Fetch full commit data from GitHub API.
     */
    private function fetchCommitFromApi(User $user, Repository $repository, string $sha): ?\App\DTOs\GitHub\CommitData
    {
        $service = GitHubService::forUser($user);
        [$owner, $repo] = explode('/', $repository->full_name);

        return $service->getCommit($owner, $repo, $sha);
    }
}
