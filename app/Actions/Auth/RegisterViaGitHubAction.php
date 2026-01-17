<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Contracts\Action;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Registers a new user via GitHub OAuth.
 */
final class RegisterViaGitHubAction implements Action
{
    public function __construct(
        private readonly SocialiteUser $githubUser,
    ) {}

    public function execute(): User
    {
        return User::create([
            'name' => $this->githubUser->getName() ?? $this->githubUser->getNickname(),
            'email' => $this->githubUser->getEmail(),
            'password' => bcrypt(Str::random(32)),
            'github_id' => $this->githubUser->getId(),
            'github_username' => $this->githubUser->getNickname(),
            'github_token' => $this->githubUser->token,
            'avatar_url' => $this->githubUser->getAvatar(),
            'email_verified_at' => now(),
        ]);
    }
}
