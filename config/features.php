<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Flags Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for feature flags that can be toggled
    | globally via environment variables. For user-specific feature flags,
    | see the Feature classes in app/Features/.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Export Features
    |--------------------------------------------------------------------------
    |
    | Toggle the export functionality. Set to false during maintenance
    | or when export services are unavailable.
    |
    */
    'exports_enabled' => env('FEATURE_EXPORTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Beta Features
    |--------------------------------------------------------------------------
    |
    | Master toggle for beta features. When disabled, no users will
    | see beta features regardless of their preferences.
    |
    */
    'beta_enabled' => env('FEATURE_BETA_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Advanced Analytics
    |--------------------------------------------------------------------------
    |
    | Toggle advanced analytics features. Useful for gradual rollout
    | or when analytics services need maintenance.
    |
    */
    'analytics_enabled' => env('FEATURE_ANALYTICS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Team Features
    |--------------------------------------------------------------------------
    |
    | Toggle team-level features. Can be disabled if team functionality
    | is not ready for production.
    |
    */
    'teams_enabled' => env('FEATURE_TEAMS_ENABLED', true),

];
