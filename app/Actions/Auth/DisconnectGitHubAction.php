<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Action;
use App\Models\User;

/**
 * Disconnects a GitHub account from a user.
 */
final class DisconnectGitHubAction implements Action
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function execute(): bool
    {
        $this->user->update([
            'github_id' => null,
            'github_username' => null,
            'github_token' => null,
        ]);

        return true;
    }
}
