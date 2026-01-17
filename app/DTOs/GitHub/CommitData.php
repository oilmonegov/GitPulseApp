<?php

declare(strict_types=1);

namespace App\DTOs\GitHub;

use App\Constants\CommitType;
use Illuminate\Support\Carbon;

/**
 * Data Transfer Object for GitHub commit information from webhooks.
 *
 * TODO [Sprint 3]: Refactor to use Sprint 4 Actions for parsing:
 * - Use ParseCommitMessageAction instead of inline parseType(), parseScope(), etc.
 * - Use CalculateImpactScoreAction for impact_score calculation
 *
 * @see \App\Actions\Commits\ParseCommitMessageAction
 * @see \App\Actions\Commits\CalculateImpactScoreAction
 */
final readonly class CommitData
{
    public function __construct(
        public string $sha,
        public string $message,
        public string $authorName,
        public string $authorEmail,
        public Carbon $timestamp,
        public string $url,
        public bool $distinct,
        public int $additions,
        public int $deletions,
        /** @var array<array{filename: string, status: string, additions: int, deletions: int, changes: int}>|null */
        public ?array $files,
    ) {}

    /**
     * Create from webhook payload commit data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromWebhook(array $data): self
    {
        $author = $data['author'] ?? [];

        return new self(
            sha: $data['id'] ?? $data['sha'] ?? '',
            message: $data['message'] ?? '',
            authorName: $author['name'] ?? $author['login'] ?? 'Unknown',
            authorEmail: $author['email'] ?? '',
            timestamp: Carbon::parse($data['timestamp'] ?? now()),
            url: $data['url'] ?? '',
            distinct: $data['distinct'] ?? true,
            additions: 0,
            deletions: 0,
            files: null,
        );
    }

    /**
     * Create with additional stats from API response.
     *
     * @param  array<string, mixed>  $apiData
     */
    public static function fromApi(array $apiData): self
    {
        $commit = $apiData['commit'] ?? [];
        $author = $commit['author'] ?? [];
        $stats = $apiData['stats'] ?? [];

        $files = null;

        if (isset($apiData['files']) && is_array($apiData['files'])) {
            $files = array_map(fn (array $file) => [
                'filename' => $file['filename'] ?? '',
                'status' => $file['status'] ?? 'modified',
                'additions' => $file['additions'] ?? 0,
                'deletions' => $file['deletions'] ?? 0,
                'changes' => $file['changes'] ?? 0,
            ], $apiData['files']);
        }

        return new self(
            sha: $apiData['sha'] ?? '',
            message: $commit['message'] ?? '',
            authorName: $author['name'] ?? 'Unknown',
            authorEmail: $author['email'] ?? '',
            timestamp: Carbon::parse($author['date'] ?? now()),
            url: $apiData['html_url'] ?? '',
            distinct: true,
            additions: $stats['additions'] ?? 0,
            deletions: $stats['deletions'] ?? 0,
            files: $files,
        );
    }

    /**
     * Parse commit type from the message using conventional commits format.
     */
    public function parseType(): CommitType
    {
        // Match conventional commit pattern: type(scope): description
        // or type: description
        if (preg_match('/^(\w+)(?:\([^)]*\))?[:!]/', $this->message, $matches)) {
            return CommitType::fromString($matches[1]);
        }

        // Check for merge commits
        if ($this->isMergeCommit()) {
            return CommitType::Other;
        }

        // Try to infer type from keywords in message
        return $this->inferTypeFromMessage();
    }

    /**
     * Parse scope from the message using conventional commits format.
     */
    public function parseScope(): ?string
    {
        if (preg_match('/^\w+\(([^)]+)\)[:!]/', $this->message, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Check if this is a merge commit.
     */
    public function isMergeCommit(): bool
    {
        return str_starts_with(strtolower($this->message), 'merge ');
    }

    /**
     * Extract external references (issue numbers, PR numbers, JIRA tickets).
     *
     * @return array<array{type: string, id: string}>
     */
    public function extractExternalRefs(): array
    {
        $refs = [];

        // GitHub issues/PRs: #123
        if (preg_match_all('/#(\d+)/', $this->message, $matches)) {
            foreach ($matches[1] as $id) {
                $refs[] = ['type' => 'github', 'id' => '#' . $id];
            }
        }

        // JIRA tickets: ABC-123
        if (preg_match_all('/([A-Z]{2,}-\d+)/', $this->message, $matches)) {
            foreach ($matches[1] as $id) {
                $refs[] = ['type' => 'jira', 'id' => $id];
            }
        }

        // Linear tickets: ABC-123 or abc-123
        if (preg_match_all('/\b([a-zA-Z]{2,3}-\d+)\b/', $this->message, $matches)) {
            foreach ($matches[1] as $id) {
                if (! preg_match('/^[A-Z]{2,}-\d+$/', $id)) {
                    $refs[] = ['type' => 'linear', 'id' => strtoupper($id)];
                }
            }
        }

        return array_unique($refs, SORT_REGULAR);
    }

    /**
     * Get the first line of the commit message (title).
     */
    public function getTitle(): string
    {
        $lines = explode("\n", $this->message);

        return trim($lines[0]);
    }

    /**
     * Get file count.
     */
    public function getFilesChanged(): int
    {
        return $this->files !== null ? count($this->files) : 0;
    }

    /**
     * Convert to array for model creation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'sha' => $this->sha,
            'message' => $this->message,
            'author_name' => $this->authorName,
            'author_email' => $this->authorEmail,
            'committed_at' => $this->timestamp,
            'url' => $this->url,
            'additions' => $this->additions,
            'deletions' => $this->deletions,
            'files_changed' => $this->getFilesChanged(),
            'files' => $this->files,
            'commit_type' => $this->parseType(),
            'scope' => $this->parseScope(),
            'external_refs' => $this->extractExternalRefs() ?: null,
            'is_merge' => $this->isMergeCommit(),
        ];
    }

    /**
     * Infer commit type from message content.
     */
    private function inferTypeFromMessage(): CommitType
    {
        $lowerMessage = strtolower($this->message);

        $keywords = [
            [CommitType::Fix, ['fix', 'bug', 'patch', 'resolve', 'issue', 'error']],
            [CommitType::Feat, ['add', 'feature', 'implement', 'new']],
            [CommitType::Docs, ['doc', 'readme', 'comment', 'documentation']],
            [CommitType::Test, ['test', 'spec', 'coverage']],
            [CommitType::Refactor, ['refactor', 'restructure', 'reorganize', 'clean']],
            [CommitType::Style, ['style', 'format', 'lint', 'whitespace']],
            [CommitType::Perf, ['perf', 'performance', 'optim', 'speed']],
            [CommitType::Chore, ['chore', 'update', 'upgrade', 'bump', 'dependency']],
            [CommitType::Ci, ['ci', 'pipeline', 'workflow', 'github action']],
            [CommitType::Build, ['build', 'compile', 'webpack', 'vite']],
        ];

        foreach ($keywords as [$type, $words]) {
            foreach ($words as $word) {
                if (str_contains($lowerMessage, $word)) {
                    return $type;
                }
            }
        }

        return CommitType::Other;
    }
}
