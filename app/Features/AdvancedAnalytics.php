<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;
use Illuminate\Support\Lottery;

/**
 * Advanced Analytics Feature Flag
 *
 * Enables advanced analytics features including:
 * - Commit impact scoring
 * - Repository health metrics
 * - Team performance insights
 *
 * Rollout: Gradual (10% of users initially)
 */
final class AdvancedAnalytics
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): bool
    {
        // Always enabled for users with admin role (if you have one)
        // Enable for 10% of users via lottery
        return Lottery::odds(1, 10)->choose();
    }
}
