<?php

declare(strict_types=1);

namespace App\Queries\Settings;

use App\Contracts\Query;
use App\Models\User;

/**
 * Aggregates status overview for all settings categories.
 *
 * @return array{
 *     profile: array{verified: bool},
 *     security: array{two_factor_enabled: bool},
 *     github: array{connected: bool, username: string|null},
 *     notifications: array{configured: bool},
 *     appearance: array{theme: string},
 * }
 */
final class GetUserSettingsOverviewQuery implements Query
{
    public function __construct(
        private readonly User $user,
    ) {}

    /**
     * @return array{
     *     profile: array{verified: bool},
     *     security: array{two_factor_enabled: bool},
     *     github: array{connected: bool, username: string|null},
     *     notifications: array{configured: bool},
     *     appearance: array{theme: string},
     * }
     */
    public function get(): array
    {
        /** @var array<string, mixed> $preferences */
        $preferences = is_array($this->user->preferences) ? $this->user->preferences : [];

        return [
            'profile' => [
                'verified' => ! is_null($this->user->email_verified_at),
            ],
            'security' => [
                'two_factor_enabled' => ! is_null($this->user->two_factor_confirmed_at),
            ],
            'github' => [
                'connected' => $this->user->hasGitHubConnected(),
                'username' => $this->user->github_username,
            ],
            'notifications' => [
                'configured' => $this->hasNotificationsConfigured($preferences),
            ],
            'appearance' => [
                'theme' => is_string($preferences['theme'] ?? null) ? $preferences['theme'] : 'system',
            ],
        ];
    }

    /**
     * Check if user has customized notification settings.
     *
     * @param  array<string, mixed>  $preferences
     */
    private function hasNotificationsConfigured(array $preferences): bool
    {
        return isset($preferences['notifications']) && is_array($preferences['notifications']);
    }
}
