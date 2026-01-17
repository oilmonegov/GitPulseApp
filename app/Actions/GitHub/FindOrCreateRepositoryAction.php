<?php

declare(strict_types=1);

namespace App\Actions\GitHub;

use App\Contracts\Action;
use App\DTOs\GitHub\RepositoryData;
use App\Models\Repository;
use App\Models\User;

/**
 * Finds an existing repository or creates a new one from GitHub data.
 */
final class FindOrCreateRepositoryAction implements Action
{
    public function __construct(
        private readonly User $user,
        private readonly RepositoryData $repositoryData,
    ) {}

    /**
     * Execute the action.
     *
     * @return Repository The found or created repository
     */
    public function execute(): Repository
    {
        return Repository::query()
            ->updateOrCreate(
                [
                    'user_id' => $this->user->id,
                    'github_id' => $this->repositoryData->id,
                ],
                $this->repositoryData->toArray(),
            );
    }
}
