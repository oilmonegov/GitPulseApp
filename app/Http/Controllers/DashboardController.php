<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Queries\Dashboard\CommitsOverTimeQuery;
use App\Queries\Dashboard\CommitTypeDistributionQuery;
use App\Queries\Dashboard\DashboardSummaryQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the analytics dashboard.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        // Default date range: last 30 days
        $startDate = Carbon::now()->subDays(29)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        return Inertia::render('Dashboard', [
            'summary' => Inertia::defer(
                fn () => (new DashboardSummaryQuery($user, $startDate, $endDate))->get(),
                'dashboard',
            ),
            'commitsOverTime' => Inertia::defer(
                fn () => (new CommitsOverTimeQuery($user, $startDate, $endDate))->get(),
                'dashboard',
            ),
            'commitTypeDistribution' => Inertia::defer(
                fn () => (new CommitTypeDistributionQuery($user, $startDate, $endDate))->get(),
                'dashboard',
            ),
        ]);
    }
}
