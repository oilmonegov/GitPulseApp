<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Action;
use App\Models\User;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Links a GitHub account to an existing authenticated user.
 */
final class ConnectGitHubAction implements Action
{
    public function __construct(
        private readonly User $user,
        private readonly SocialiteUser $githubUser,
    ) {}

    public function execute(): bool
    {
        $this->user->update([
            'github_id' => $this->githubUser->getId(),
            'github_username' => $this->githubUser->getNickname(),
            'github_token' => $this->githubUser->token,
            'avatar_url' => $this->githubUser->getAvatar(),
        ]);

        return true;
    }
}
