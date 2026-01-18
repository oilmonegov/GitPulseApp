<?php

declare(strict_types=1);

namespace App\Features;

use App\Models\User;

/**
 * Beta Dashboard Feature Flag
 *
 * Enables the new beta dashboard UI with:
 * - Real-time updates
 * - Interactive charts
 * - Customizable widgets
 *
 * Rollout: Opt-in via user preferences
 */
final class BetaDashboard
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(User $user): bool
    {
        // Check if user has opted into beta features
        $preferences = $user->preferences ?? [];

        return $preferences['beta_features'] ?? false;
    }
}
