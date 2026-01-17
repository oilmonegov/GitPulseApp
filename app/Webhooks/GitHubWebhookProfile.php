<?php

declare(strict_types=1);

namespace App\Webhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookProfile\WebhookProfile;

/**
 * Determines which GitHub webhook events should be processed.
 *
 * Only allows events that GitPulse needs for productivity tracking:
 * - push: For tracking commits
 * - pull_request: For tracking PR activity
 * - ping: For webhook setup verification
 */
final class GitHubWebhookProfile implements WebhookProfile
{
    /**
     * Supported GitHub webhook event types.
     *
     * @var array<string>
     */
    private const SUPPORTED_EVENTS = [
        'push',
        'pull_request',
        'ping',
    ];

    public function shouldProcess(Request $request): bool
    {
        $event = $request->header('X-GitHub-Event');

        if (! $event) {
            return false;
        }

        return in_array($event, self::SUPPORTED_EVENTS, true);
    }
}
