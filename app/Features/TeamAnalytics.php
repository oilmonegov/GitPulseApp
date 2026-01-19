<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;

/**
 * Team Analytics Feature Flag
 *
 * Enables team-level analytics including:
 * - Team commit patterns
 * - Cross-repository insights
 * - Collaboration metrics
 *
 * Rollout: Users with 3+ repositories
 */
final class TeamAnalytics
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): bool
    {
        // Enable for users with multiple repositories
        return $user->repositories()->count() >= 3;
    }
}
