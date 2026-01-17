<?php

declare(strict_types=1);

namespace App\Actions\Commits;

use App\Constants\CommitType;
use App\Contracts\Action;

/**
 * Categorizes non-conventional commits using NLP-based keyword matching.
 *
 * When a commit message doesn't follow the Conventional Commits format,
 * this action infers the commit type based on keywords found in the message.
 *
 * The keyword mapping is ordered by priority - more specific keywords
 * are checked before generic ones to improve accuracy.
 */
final class CategorizeCommitAction implements Action
{
    /**
     * Keyword mappings for each commit type.
     *
     * Order matters: more specific/unique keywords first.
     *
     * @var array<string, array<string>>
     */
    private const KEYWORD_MAP = [
        // Bug fixes - high priority patterns
        'fix' => [
            'fix',
            'bug',
            'patch',
            'hotfix',
            'resolve',
            'issue',
            'error',
            'crash',
            'broken',
            'repair',
            'correct',
            'defect',
        ],
        // New features
        'feat' => [
            'add',
            'feature',
            'implement',
            'new',
            'create',
            'introduce',
            'support',
            'enable',
            'allow',
        ],
        // Documentation
        'docs' => [
            'doc',
            'documentation',
            'readme',
            'comment',
            'changelog',
            'license',
            'contributing',
            'wiki',
            'guide',
            'tutorial',
        ],
        // Tests
        'test' => [
            'test',
            'spec',
            'coverage',
            'assertion',
            'mock',
            'stub',
            'fixture',
            'e2e',
            'integration test',
            'unit test',
        ],
        // Refactoring
        'refactor' => [
            'refactor',
            'restructure',
            'reorganize',
            'rewrite',
            'simplify',
            'extract',
            'rename',
            'move',
            'split',
            'clean up',
            'cleanup',
        ],
        // Performance
        'perf' => [
            'perf',
            'performance',
            'optim',
            'speed',
            'fast',
            'slow',
            'cache',
            'memory',
            'lazy',
            'eager',
        ],
        // Code style
        'style' => [
            'style',
            'format',
            'lint',
            'prettier',
            'whitespace',
            'indent',
            'spacing',
            'psr',
            'coding standard',
        ],
        // CI/CD
        'ci' => [
            'ci',
            'pipeline',
            'workflow',
            'github action',
            'travis',
            'jenkins',
            'circleci',
            'gitlab ci',
            'deploy',
            'deployment',
        ],
        // Build system
        'build' => [
            'build',
            'compile',
            'webpack',
            'vite',
            'bundle',
            'rollup',
            'esbuild',
            'npm',
            'yarn',
            'pnpm',
            'composer',
        ],
        // Chores
        'chore' => [
            'chore',
            'update',
            'upgrade',
            'bump',
            'dependency',
            'deps',
            'package',
            'version',
            'remove',
            'delete',
            'clean',
        ],
        // Reverts
        'revert' => [
            'revert',
            'rollback',
            'undo',
            'restore',
        ],
    ];

    /**
     * Phrase patterns that indicate specific commit types.
     *
     * These are checked as complete phrases for higher accuracy.
     *
     * @var array<string, array<string>>
     */
    private const PHRASE_PATTERNS = [
        'fix' => [
            'fix for',
            'fixes #',
            'fixed #',
            'bug fix',
            'quick fix',
        ],
        'feat' => [
            'add support',
            'added support',
            'new feature',
            'now supports',
        ],
        'docs' => [
            'update readme',
            'update docs',
            'add documentation',
            'update documentation',
        ],
        'refactor' => [
            'clean up',
            'code cleanup',
            'move to',
            'extract to',
        ],
        'chore' => [
            'update dependency',
            'update dependencies',
            'bump version',
            'version bump',
        ],
    ];

    public function __construct(
        private readonly string $message,
    ) {}

    /**
     * Categorize the commit message and return the inferred type.
     */
    public function execute(): CommitType
    {
        $lowerMessage = strtolower($this->message);

        // First, check for exact phrase patterns (higher confidence)
        $phraseType = $this->matchPhrasePatterns($lowerMessage);

        if ($phraseType !== null) {
            return $phraseType;
        }

        // Then check for keywords
        $keywordType = $this->matchKeywords($lowerMessage);

        if ($keywordType !== null) {
            return $keywordType;
        }

        // Default to Other if no patterns match
        return CommitType::Other;
    }

    /**
     * Calculate confidence score for the categorization.
     *
     * Returns a value between 0.0 and 1.0 indicating how confident
     * the categorization is based on keyword matches.
     */
    public function getConfidence(): float
    {
        $lowerMessage = strtolower($this->message);

        // Phrase matches have highest confidence
        if ($this->matchPhrasePatterns($lowerMessage) !== null) {
            return 0.9;
        }

        // Count keyword matches
        $matchCount = 0;

        foreach (self::KEYWORD_MAP as $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($lowerMessage, $keyword)) {
                    $matchCount++;
                }
            }
        }

        // More matches = higher confidence, cap at 0.8 for keyword-based
        return min(0.8, 0.4 + ($matchCount * 0.1));
    }

    /**
     * Match phrase patterns for higher-confidence categorization.
     */
    private function matchPhrasePatterns(string $lowerMessage): ?CommitType
    {
        foreach (self::PHRASE_PATTERNS as $type => $phrases) {
            foreach ($phrases as $phrase) {
                if (str_contains($lowerMessage, $phrase)) {
                    return CommitType::fromString($type);
                }
            }
        }

        return null;
    }

    /**
     * Match keywords for categorization.
     */
    private function matchKeywords(string $lowerMessage): ?CommitType
    {
        // Score each type by how many keywords match
        $scores = [];

        // First, check for word-boundary matches (more accurate)
        foreach (self::KEYWORD_MAP as $type => $keywords) {
            $score = 0;

            foreach ($keywords as $index => $keyword) {
                // Use word boundary matching for single words
                if (! str_contains($keyword, ' ')) {
                    // Match as whole word or at word boundaries
                    $pattern = '/\b' . preg_quote($keyword, '/') . '/';

                    if (preg_match($pattern, $lowerMessage)) {
                        // Earlier keywords in the list have higher weight
                        // Also boost score for matches at the start of the message
                        $positionBonus = str_starts_with($lowerMessage, $keyword) ? 3 : 0;
                        $score += (count($keywords) - $index) + $positionBonus;
                    }
                } else {
                    // Multi-word phrases use contains
                    if (str_contains($lowerMessage, $keyword)) {
                        $score += (count($keywords) - $index) * 2; // Phrases get double weight
                    }
                }
            }

            if ($score > 0) {
                $scores[$type] = $score;
            }
        }

        if (empty($scores)) {
            return null;
        }

        // Return the type with highest score
        arsort($scores);

        return CommitType::fromString(array_key_first($scores));
    }
}
