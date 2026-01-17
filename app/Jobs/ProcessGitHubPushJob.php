<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\GitHub\ProcessPushEventAction;
use App\DTOs\GitHub\PushEventData;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Processes GitHub push events to store commits.
 *
 * This job parses the push event payload, extracts commit data,
 * and stores it in the database for productivity tracking.
 */
class ProcessGitHubPushJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload,
        public readonly bool $fetchFullCommitData = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pushEvent = PushEventData::fromWebhook($this->payload);

        Log::debug('Processing push event', [
            'repository' => $pushEvent->repository->fullName,
            'branch' => $pushEvent->getBranch(),
            'commit_count' => count($pushEvent->commits),
        ]);

        $storedCommits = (new ProcessPushEventAction(
            $pushEvent,
            $this->fetchFullCommitData,
        ))->execute();

        Log::info('Push event processed successfully', [
            'repository' => $pushEvent->repository->fullName,
            'branch' => $pushEvent->getBranch(),
            'stored_commits' => $storedCommits->count(),
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        $repository = $this->payload['repository']['full_name'] ?? 'unknown';

        Log::error('Failed to process GitHub push event', [
            'repository' => $repository,
            'error' => $exception?->getMessage(),
        ]);
    }
}
