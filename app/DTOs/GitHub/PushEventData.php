<?php

declare(strict_types=1);

namespace App\DTOs\GitHub;

/**
 * Data Transfer Object for GitHub push event webhook payload.
 */
final readonly class PushEventData
{
    /**
     * @param  array<CommitData>  $commits
     */
    public function __construct(
        public string $ref,
        public string $before,
        public string $after,
        public RepositoryData $repository,
        public string $pusherName,
        public string $pusherEmail,
        public string $senderLogin,
        public string $senderId,
        public array $commits,
        public bool $created,
        public bool $deleted,
        public bool $forced,
    ) {}

    /**
     * Create from webhook payload.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromWebhook(array $payload): self
    {
        $pusher = $payload['pusher'] ?? [];
        $sender = $payload['sender'] ?? [];
        $commits = $payload['commits'] ?? [];

        return new self(
            ref: $payload['ref'] ?? '',
            before: $payload['before'] ?? '',
            after: $payload['after'] ?? '',
            repository: RepositoryData::fromWebhook($payload['repository'] ?? []),
            pusherName: $pusher['name'] ?? '',
            pusherEmail: $pusher['email'] ?? '',
            senderLogin: $sender['login'] ?? '',
            senderId: (string) ($sender['id'] ?? ''),
            commits: array_map(
                fn (array $commit) => CommitData::fromWebhook($commit),
                $commits,
            ),
            created: $payload['created'] ?? false,
            deleted: $payload['deleted'] ?? false,
            forced: $payload['forced'] ?? false,
        );
    }

    /**
     * Get the branch name from the ref.
     */
    public function getBranch(): string
    {
        // refs/heads/main -> main
        return str_replace('refs/heads/', '', $this->ref);
    }

    /**
     * Check if this is a push to the default branch.
     */
    public function isDefaultBranch(): bool
    {
        return $this->getBranch() === $this->repository->defaultBranch;
    }

    /**
     * Check if this push has any commits to process.
     */
    public function hasCommits(): bool
    {
        return count($this->commits) > 0 && ! $this->deleted;
    }

    /**
     * Get distinct commits only (not previously pushed).
     *
     * @return array<CommitData>
     */
    public function getDistinctCommits(): array
    {
        return array_filter($this->commits, fn (CommitData $commit) => $commit->distinct);
    }
}
