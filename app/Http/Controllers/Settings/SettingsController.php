<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Queries\Settings\GetUserSettingsOverviewQuery;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings hub page.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $overview = (new GetUserSettingsOverviewQuery($user))->get();

        return Inertia::render('settings/Index', [
            'overview' => $overview,
        ]);
    }
}
