<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * OAuth providers supported by the application.
 */
enum OAuthProvider: string
{
    case GitHub = 'github';

    /**
     * Get the display name for the provider.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
        };
    }

    /**
     * Get the icon name for the provider (for lucide icons).
     */
    public function iconName(): string
    {
        return match ($this) {
            self::GitHub => 'Github',
        };
    }

    /**
     * Get the required OAuth scopes for the provider.
     *
     * @return array<string>
     */
    public function scopes(): array
    {
        return match ($this) {
            self::GitHub => ['read:user', 'user:email', 'repo', 'admin:repo_hook'],
        };
    }
}
