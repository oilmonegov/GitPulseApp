<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\ConnectGitHubAction;
use App\Actions\Auth\DisconnectGitHubAction;
use App\Actions\Auth\RegisterViaGitHubAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\User\FindUserByEmailQuery;
use App\Queries\User\FindUserByGitHubIdQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Throwable;

class GitHubController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('github')
            ->scopes(['read:user', 'user:email', 'repo', 'admin:repo_hook'])
            ->redirect();
    }

    /**
     * Handle the callback from GitHub OAuth.
     */
    public function callback(): RedirectResponse
    {
        try {
            /** @var SocialiteUser $githubUser */
            $githubUser = Socialite::driver('github')->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->with('error', __('errors.github.auth_failed'));
        }

        // If user is already authenticated, link GitHub account
        if (Auth::check()) {
            return $this->linkGitHubAccount($githubUser);
        }

        // Otherwise, login or register
        return $this->loginOrRegister($githubUser);
    }

    /**
     * Link GitHub account to existing authenticated user.
     */
    private function linkGitHubAccount(SocialiteUser $githubUser): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Check if this GitHub account is already linked to another user
        $existingUser = (new FindUserByGitHubIdQuery($githubUser->getId()))->get();

        if ($existingUser && $existingUser->id !== $user->id) {
            return redirect()->route('dashboard')
                ->with('error', __('errors.github.already_linked'));
        }

        (new ConnectGitHubAction($user, $githubUser))->execute();

        return redirect()->route('dashboard')
            ->with('success', __('errors.github.connected'));
    }

    /**
     * Login existing user or create a new account.
     */
    private function loginOrRegister(SocialiteUser $githubUser): RedirectResponse
    {
        // Try to find existing user by GitHub ID
        $user = (new FindUserByGitHubIdQuery($githubUser->getId()))->get();

        if ($user) {
            $this->updateGitHubToken($user, $githubUser);
            Auth::login($user, remember: true);

            return redirect()->intended(route('dashboard'));
        }

        // Check if email already exists
        $email = $githubUser->getEmail();
        $existingEmailUser = $email ? (new FindUserByEmailQuery($email))->get() : null;

        if ($existingEmailUser) {
            (new ConnectGitHubAction($existingEmailUser, $githubUser))->execute();
            Auth::login($existingEmailUser, remember: true);

            return redirect()->intended(route('dashboard'));
        }

        // Create new user
        $user = (new RegisterViaGitHubAction($githubUser))->execute();
        Auth::login($user, remember: true);

        return redirect()->route('dashboard');
    }

    /**
     * Update GitHub token for existing user.
     */
    private function updateGitHubToken(User $user, SocialiteUser $githubUser): void
    {
        $user->update([
            'github_token' => $githubUser->token,
            'avatar_url' => $githubUser->getAvatar(),
        ]);
    }

    /**
     * Disconnect GitHub account from the user.
     */
    public function disconnect(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        (new DisconnectGitHubAction($user))->execute();

        return redirect()->back()
            ->with('success', __('errors.github.disconnected'));
    }
}
