<?php

declare(strict_types=1);

namespace App\Queries\User;

use App\Contracts\Query;
use App\Models\User;

/**
 * Finds a user by their GitHub ID.
 */
final class FindUserByGitHubIdQuery implements Query
{
    public function __construct(
        private readonly string $githubId,
    ) {}

    public function get(): ?User
    {
        return User::where('github_id', $this->githubId)->first();
    }
}
