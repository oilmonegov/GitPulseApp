<?php

declare(strict_types=1);

namespace App\Features;

/**
 * Export Features Feature Flag
 *
 * Enables data export functionality:
 * - PDF reports
 * - CSV exports
 * - API access
 *
 * Rollout: Globally enabled (can be disabled for maintenance)
 */
final class ExportFeatures
{
    /**
     * Resolve the feature's initial value.
     */
    public function resolve(): bool
    {
        // Globally enabled - can be toggled via config for maintenance
        return config('features.exports_enabled', true);
    }
}
