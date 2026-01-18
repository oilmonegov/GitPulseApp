<?php

declare(strict_types=1);

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseSizeCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\HorizonCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureHealthChecks();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureHealthChecks(): void
    {
        Health::checks([
            // Core checks - always run
            DatabaseCheck::new(),
            CacheCheck::new(),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(70)
                ->failWhenUsedSpaceIsAbovePercentage(90),

            // Production-only checks
            DebugModeCheck::new()
                ->if(app()->isProduction()),
            EnvironmentCheck::new()
                ->expectEnvironment('production')
                ->if(app()->isProduction()),
            OptimizedAppCheck::new()
                ->if(app()->isProduction()),

            // Queue checks - only if Horizon is configured
            HorizonCheck::new()
                ->if(config('queue.default') === 'redis'),

            // Database size check (MySQL only)
            DatabaseSizeCheck::new()
                ->failWhenSizeAboveGb(errorThresholdGb: 5.0)
                ->if(config('database.default') === 'mysql'),
        ]);
    }
}
