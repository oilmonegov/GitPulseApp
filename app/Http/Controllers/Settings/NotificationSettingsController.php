<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Actions\Settings\UpdateUserPreferencesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePreferencesRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationSettingsController extends Controller
{
    /**
     * Display the notification settings page.
     */
    public function edit(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $preferences = $user->preferences ?? [];
        $notifications = $preferences['notifications'] ?? [];

        return Inertia::render('settings/Notifications', [
            'notifications' => [
                'weekly_digest' => $notifications['weekly_digest'] ?? false,
                'commit_summary' => $notifications['commit_summary'] ?? true,
                'repository_alerts' => $notifications['repository_alerts'] ?? true,
            ],
        ]);
    }

    /**
     * Update the user's notification preferences.
     */
    public function update(UpdatePreferencesRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        (new UpdateUserPreferencesAction(
            $user,
            $request->validated(),
        ))->execute();

        return back();
    }
}
