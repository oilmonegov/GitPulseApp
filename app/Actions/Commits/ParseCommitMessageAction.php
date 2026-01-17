<?php

declare(strict_types=1);

namespace App\Actions\Commits;

use App\Constants\CommitType;
use App\Contracts\Action;
use App\DTOs\Commits\ParsedCommitData;

/**
 * Parses commit messages to extract type, scope, description, and references.
 *
 * Supports the Conventional Commits specification (https://www.conventionalcommits.org/).
 * For non-conventional commits, delegates to CategorizeCommitAction for NLP-based inference.
 *
 * Format: <type>[(scope)][!]: <description>
 * Examples:
 *   - feat(auth): add OAuth login
 *   - fix: resolve null pointer exception
 *   - feat!: breaking change to API
 */
final class ParseCommitMessageAction implements Action
{
    /**
     * Conventional commit regex pattern.
     *
     * Matches: type(scope)!: description OR type!: description OR type: description
     */
    private const CONVENTIONAL_PATTERN = '/^(?<type>\w+)(?:\((?<scope>[^)]+)\))?(?<breaking>!)?:\s*(?<description>.+)$/';

    public function __construct(
        private readonly string $message,
    ) {}

    /**
     * Parse the commit message and return structured data.
     */
    public function execute(): ParsedCommitData
    {
        $title = $this->getTitle();
        $externalRefs = $this->extractExternalRefs();

        // Check for merge commits first
        if ($this->isMergeCommit()) {
            return ParsedCommitData::merge(
                description: $title,
                externalRefs: $externalRefs,
            );
        }

        // Try to parse as conventional commit
        if (preg_match(self::CONVENTIONAL_PATTERN, $title, $matches)) {
            return ParsedCommitData::conventional(
                type: CommitType::fromString($matches['type']),
                scope: ! empty($matches['scope']) ? $matches['scope'] : null,
                description: trim($matches['description']),
                externalRefs: $externalRefs,
                isBreakingChange: ! empty($matches['breaking']),
            );
        }

        // Fall back to NLP-based categorization
        $type = (new CategorizeCommitAction($this->message))->execute();

        return ParsedCommitData::inferred(
            type: $type,
            description: $title,
            externalRefs: $externalRefs,
        );
    }

    /**
     * Get the first line (title) of the commit message.
     */
    private function getTitle(): string
    {
        $lines = explode("\n", $this->message);

        return trim($lines[0]);
    }

    /**
     * Check if this is a merge commit.
     */
    private function isMergeCommit(): bool
    {
        $lowerMessage = strtolower($this->message);

        return str_starts_with($lowerMessage, 'merge ')
            || str_starts_with($lowerMessage, 'merge branch')
            || str_starts_with($lowerMessage, 'merge pull request');
    }

    /**
     * Extract external references from the commit message.
     *
     * Supports:
     *   - GitHub issues/PRs: #123
     *   - JIRA tickets: ABC-123, PROJ-456
     *   - Linear tickets: ABC-123 (lowercase)
     *
     * @return array<array{type: string, id: string}>
     */
    private function extractExternalRefs(): array
    {
        $refs = [];
        $seen = [];

        // GitHub issues/PRs: #123
        if (preg_match_all('/#(\d+)\b/', $this->message, $matches)) {
            foreach ($matches[1] as $id) {
                $key = 'github:#' . $id;

                if (! isset($seen[$key])) {
                    $refs[] = ['type' => 'github', 'id' => '#' . $id];
                    $seen[$key] = true;
                }
            }
        }

        // JIRA tickets: ABC-123 (uppercase project key, 2+ chars)
        if (preg_match_all('/\b([A-Z]{2,}-\d+)\b/', $this->message, $matches)) {
            foreach ($matches[1] as $id) {
                $key = 'jira:' . $id;

                if (! isset($seen[$key])) {
                    $refs[] = ['type' => 'jira', 'id' => $id];
                    $seen[$key] = true;
                }
            }
        }

        // Linear tickets: lowercase project keys (eng-123, fe-456)
        if (preg_match_all('/\b([a-z]{2,4}-\d+)\b/', $this->message, $matches)) {
            foreach ($matches[1] as $id) {
                $key = 'linear:' . strtoupper($id);

                if (! isset($seen[$key])) {
                    $refs[] = ['type' => 'linear', 'id' => strtoupper($id)];
                    $seen[$key] = true;
                }
            }
        }

        return $refs;
    }
}
