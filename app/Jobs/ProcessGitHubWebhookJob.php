<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;

/**
 * Processes incoming GitHub webhook events.
 *
 * Handles push events (commits) and pull_request events for
 * productivity tracking and analytics.
 */
class ProcessGitHubWebhookJob extends ProcessWebhookJob
{
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->webhookCall->payload ?? [];
        $event = $this->webhookCall->headers['x-github-event'][0] ?? null;

        match ($event) {
            'ping' => $this->handlePing($payload),
            'push' => $this->handlePush($payload),
            'pull_request' => $this->handlePullRequest($payload),
            default => $this->handleUnknownEvent($event),
        };
    }

    /**
     * Handle ping event (webhook setup verification).
     *
     * @param  array<string, mixed>  $payload
     */
    private function handlePing(array $payload): void
    {
        Log::info('GitHub webhook ping received', [
            'hook_id' => $payload['hook_id'] ?? null,
            'zen' => $payload['zen'] ?? null,
        ]);
    }

    /**
     * Handle push event (commits).
     *
     * @param  array<string, mixed>  $payload
     */
    private function handlePush(array $payload): void
    {
        $repository = $payload['repository']['full_name'] ?? 'unknown';
        $commits = $payload['commits'] ?? [];
        $pusher = $payload['pusher']['name'] ?? 'unknown';
        $ref = $payload['ref'] ?? 'unknown';

        Log::info('GitHub push event received', [
            'repository' => $repository,
            'pusher' => $pusher,
            'ref' => $ref,
            'commit_count' => count($commits),
        ]);

        // TODO: In future sprints, dispatch jobs to:
        // - Store commits in database
        // - Calculate productivity metrics
        // - Update user statistics
    }

    /**
     * Handle pull_request event.
     *
     * @param  array<string, mixed>  $payload
     */
    private function handlePullRequest(array $payload): void
    {
        $action = $payload['action'] ?? 'unknown';
        $repository = $payload['repository']['full_name'] ?? 'unknown';
        $prNumber = $payload['pull_request']['number'] ?? null;
        $prTitle = $payload['pull_request']['title'] ?? 'unknown';
        $author = $payload['pull_request']['user']['login'] ?? 'unknown';

        Log::info('GitHub pull_request event received', [
            'action' => $action,
            'repository' => $repository,
            'pr_number' => $prNumber,
            'pr_title' => $prTitle,
            'author' => $author,
        ]);

        // TODO: In future sprints, dispatch jobs to:
        // - Store PR events in database
        // - Track review times
        // - Calculate collaboration metrics
    }

    /**
     * Handle unknown/unsupported events.
     */
    private function handleUnknownEvent(?string $event): void
    {
        Log::warning('Unknown GitHub webhook event received', [
            'event' => $event,
        ]);
    }
}
