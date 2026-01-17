# F2: Backend Implementation

## Action Classes

### ParseCommitMessage Action

```php
<?php

declare(strict_types=1);

namespace App\Actions\Commits;

use App\Data\ParsedCommitData;
use App\Enums\CommitType;

class ParseCommitMessage
{
    /**
     * Conventional Commits pattern:
     * type(scope)!: description
     *
     * Examples:
     * - feat: add login
     * - fix(auth): resolve token issue
     * - feat!: breaking change
     * - feat(api)!: breaking API change
     */
    private const CONVENTIONAL_PATTERN = '/^(?<type>feat|fix|chore|docs|refactor|test|style|perf|build|ci|revert)(?:\((?<scope>[^)]+)\))?(?<breaking>!)?\s*:\s*(?<description>.+)$/im';

    /**
     * External reference patterns
     */
    private const REF_PATTERNS = [
        'github' => '/#(\d+)/',
        'jira' => '/([A-Z][A-Z0-9]+-\d+)/',
        'linear' => '/([A-Z]+-\d+)/',
    ];

    public function execute(string $message): ParsedCommitData
    {
        $firstLine = $this->getFirstLine($message);
        $refs = $this->extractReferences($message);

        // Try conventional commit format first
        if (preg_match(self::CONVENTIONAL_PATTERN, $firstLine, $matches)) {
            return new ParsedCommitData(
                type: CommitType::from($matches['type']),
                scope: $matches['scope'] ?? null,
                description: trim($matches['description']),
                external_refs: $refs,
                is_breaking: ! empty($matches['breaking']),
                raw_message: $message,
            );
        }

        // Return with type=null for fallback categorization
        return new ParsedCommitData(
            type: null,
            scope: null,
            description: $firstLine,
            external_refs: $refs,
            is_breaking: $this->detectBreakingChange($message),
            raw_message: $message,
        );
    }

    private function getFirstLine(string $message): string
    {
        $lines = explode("\n", trim($message));

        return trim($lines[0]);
    }

    private function extractReferences(string $message): array
    {
        $refs = [];

        foreach (self::REF_PATTERNS as $type => $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                foreach ($matches[0] as $match) {
                    $refs[] = $match;
                }
            }
        }

        return array_unique($refs);
    }

    private function detectBreakingChange(string $message): bool
    {
        $breakingIndicators = [
            'BREAKING CHANGE:',
            'BREAKING-CHANGE:',
            'BREAKING:',
        ];

        foreach ($breakingIndicators as $indicator) {
            if (stripos($message, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }
}
```

### CategorizeCommit Action

```php
<?php

declare(strict_types=1);

namespace App\Actions\Commits;

use App\Enums\CommitType;

class CategorizeCommit
{
    /**
     * Keyword mappings for NLP-based categorization.
     * Order matters - first match wins.
     */
    private const KEYWORD_MAP = [
        CommitType::FEAT->value => [
            'add', 'added', 'adding', 'implement', 'implemented',
            'create', 'created', 'new', 'feature', 'introduce',
            'support', 'enable', 'allow',
        ],
        CommitType::FIX->value => [
            'fix', 'fixed', 'fixes', 'fixing', 'bug', 'bugfix',
            'resolve', 'resolved', 'repair', 'patch', 'correct',
            'issue', 'error', 'problem', 'crash', 'handle',
        ],
        CommitType::REFACTOR->value => [
            'refactor', 'refactored', 'refactoring', 'restructure',
            'reorganize', 'cleanup', 'clean up', 'simplify',
            'extract', 'move', 'rename', 'split',
        ],
        CommitType::PERF->value => [
            'perf', 'performance', 'optimize', 'optimized', 'optimization',
            'speed', 'faster', 'improve', 'cache', 'lazy',
        ],
        CommitType::TEST->value => [
            'test', 'tests', 'testing', 'spec', 'specs',
            'coverage', 'unit', 'integration', 'e2e',
        ],
        CommitType::DOCS->value => [
            'doc', 'docs', 'document', 'documentation', 'readme',
            'comment', 'comments', 'jsdoc', 'phpdoc', 'docblock',
        ],
        CommitType::STYLE->value => [
            'style', 'format', 'formatting', 'lint', 'linting',
            'prettier', 'eslint', 'whitespace', 'indent',
        ],
        CommitType::CHORE->value => [
            'chore', 'bump', 'update', 'upgrade', 'dependency',
            'dependencies', 'package', 'version', 'merge',
            'config', 'configuration', 'setup', 'init',
        ],
    ];

    public function execute(string $message): CommitType
    {
        $lowercaseMessage = strtolower($message);
        $words = preg_split('/[\s\-_\/]+/', $lowercaseMessage);

        // Check for merge commits first
        if ($this->isMergeCommit($message)) {
            return CommitType::CHORE;
        }

        // Score each type based on keyword matches
        $scores = [];
        foreach (self::KEYWORD_MAP as $type => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                // Check for word boundary match
                if (in_array($keyword, $words, true)) {
                    $score += 2; // Exact word match
                } elseif (str_contains($lowercaseMessage, $keyword)) {
                    $score += 1; // Substring match
                }
            }
            $scores[$type] = $score;
        }

        // Return highest scoring type, or OTHER if no matches
        arsort($scores);
        $topType = array_key_first($scores);

        if ($scores[$topType] > 0) {
            return CommitType::from($topType);
        }

        return CommitType::OTHER;
    }

    private function isMergeCommit(string $message): bool
    {
        $mergePatterns = [
            '/^Merge\s+(pull\s+request|branch|remote)/i',
            '/^Merged\s+in\s+/i',
            '/^Merge:/i',
        ];

        foreach ($mergePatterns as $pattern) {
            if (preg_match($pattern, $message)) {
                return true;
            }
        }

        return false;
    }
}
```

### CalculateImpactScore Action

```php
<?php

declare(strict_types=1);

namespace App\Actions\Commits;

use App\Models\Commit;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CalculateImpactScore
{
    private const WEIGHTS = [
        'lines_changed' => 0.20,
        'files_touched' => 0.15,
        'commit_type' => 0.25,
        'is_merge' => 0.20,
        'external_refs' => 0.10,
        'focus_time' => 0.10,
    ];

    private const TYPE_SCORES = [
        'feat' => 1.0,
        'fix' => 0.8,
        'refactor' => 0.7,
        'perf' => 0.7,
        'test' => 0.5,
        'docs' => 0.3,
        'style' => 0.2,
        'chore' => 0.2,
        'other' => 0.4,
    ];

    public function execute(Commit $commit): float
    {
        $scores = [
            'lines_changed' => $this->scoreLinesChanged($commit),
            'files_touched' => $this->scoreFilesTouched($commit),
            'commit_type' => $this->scoreCommitType($commit),
            'is_merge' => $this->scoreMerge($commit),
            'external_refs' => $this->scoreExternalRefs($commit),
            'focus_time' => $this->scoreFocusTime($commit),
        ];

        $weightedScore = 0;
        foreach (self::WEIGHTS as $factor => $weight) {
            $weightedScore += $weight * $scores[$factor];
        }

        // Scale to 0-10+ range
        return round($weightedScore * 10, 2);
    }

    private function scoreLinesChanged(Commit $commit): float
    {
        $linesChanged = $commit->additions + $commit->deletions;

        // Get repository average for normalization
        $avgLines = $commit->repository->commits()
            ->where('id', '!=', $commit->id)
            ->avg(DB::raw('additions + deletions')) ?? 100;

        // Normalize: 1.0 = average, cap at 2.0
        return min($linesChanged / max($avgLines, 1), 2.0);
    }

    private function scoreFilesTouched(Commit $commit): float
    {
        // 5 files = 1.0, more files = higher score, cap at 1.5
        return min($commit->files_changed / 5, 1.5);
    }

    private function scoreCommitType(Commit $commit): float
    {
        return self::TYPE_SCORES[$commit->commit_type->value] ?? 0.4;
    }

    private function scoreMerge(Commit $commit): float
    {
        // Merge commits indicate completed work
        return $commit->is_merge ? 1.5 : 0.5;
    }

    private function scoreExternalRefs(Commit $commit): float
    {
        // Linked issues add context and traceability
        return count($commit->external_refs ?? []) > 0 ? 1.0 : 0.5;
    }

    private function scoreFocusTime(Commit $commit): float
    {
        $hour = $commit->committed_at->hour;

        // Peak productivity hours (9-12, 14-17) get bonus
        return match (true) {
            $hour >= 9 && $hour <= 12 => 1.2,  // Morning focus
            $hour >= 14 && $hour <= 17 => 1.1, // Afternoon focus
            $hour >= 6 && $hour <= 21 => 1.0,  // Normal hours
            default => 0.8,                      // Late night/early morning
        };
    }
}
```

---

## Data Transfer Objects

### ParsedCommitData

```php
<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\CommitType;
use Spatie\LaravelData\Data;

class ParsedCommitData extends Data
{
    public function __construct(
        public ?CommitType $type,
        public ?string $scope,
        public string $description,
        public array $external_refs,
        public bool $is_breaking,
        public string $raw_message,
    ) {}

    public function isConventional(): bool
    {
        return $this->type !== null;
    }

    public function hasScope(): bool
    {
        return $this->scope !== null;
    }

    public function hasReferences(): bool
    {
        return count($this->external_refs) > 0;
    }
}
```

---

## Enums

### CommitType Enum (Complete)

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum CommitType: string
{
    case FEAT = 'feat';
    case FIX = 'fix';
    case CHORE = 'chore';
    case DOCS = 'docs';
    case REFACTOR = 'refactor';
    case TEST = 'test';
    case STYLE = 'style';
    case PERF = 'perf';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::FEAT => 'Feature',
            self::FIX => 'Bug Fix',
            self::CHORE => 'Chore',
            self::DOCS => 'Documentation',
            self::REFACTOR => 'Refactor',
            self::TEST => 'Test',
            self::STYLE => 'Style',
            self::PERF => 'Performance',
            self::OTHER => 'Other',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::FEAT => '✨',
            self::FIX => '🐛',
            self::CHORE => '🔧',
            self::DOCS => '📚',
            self::REFACTOR => '♻️',
            self::TEST => '🧪',
            self::STYLE => '💄',
            self::PERF => '⚡',
            self::OTHER => '📝',
        };
    }

    public function impactWeight(): float
    {
        return match ($this) {
            self::FEAT => 1.0,
            self::FIX => 0.8,
            self::REFACTOR, self::PERF => 0.7,
            self::TEST => 0.5,
            self::DOCS => 0.3,
            self::STYLE, self::CHORE => 0.2,
            self::OTHER => 0.4,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FEAT => 'green',
            self::FIX => 'red',
            self::CHORE => 'gray',
            self::DOCS => 'blue',
            self::REFACTOR => 'purple',
            self::TEST => 'yellow',
            self::STYLE => 'pink',
            self::PERF => 'orange',
            self::OTHER => 'gray',
        };
    }

    public static function fromMessage(string $message): self
    {
        // Try to extract type from message prefix
        if (preg_match('/^(\w+)[\(:]/', $message, $matches)) {
            $type = strtolower($matches[1]);

            return self::tryFrom($type) ?? self::OTHER;
        }

        return self::OTHER;
    }
}
```

---

## Integration with F1 (Webhook Processing)

The documentation engine is called during webhook processing:

```php
// In ProcessGitHubPush::handle()

// Parse commit message
$parsed = $parseMessage->execute($data->message);

// Use parsed type or fallback to NLP categorization
$commitType = $parsed->type ?? $categorize->execute($data->message);

// Calculate impact score after commit is created
$impactScore = $calculateScore->execute($commit);
```

---

## Service Integration

### MetricsService Usage

```php
<?php

namespace App\Services\Analytics;

use App\Actions\Commits\CalculateImpactScore;
use App\Models\Commit;

class MetricsService
{
    public function __construct(
        private CalculateImpactScore $calculateScore,
    ) {}

    public function recalculateImpactScores(int $userId): void
    {
        Commit::where('user_id', $userId)
            ->chunkById(100, function ($commits) {
                foreach ($commits as $commit) {
                    $score = $this->calculateScore->execute($commit);
                    $commit->update(['impact_score' => $score]);
                }
            });
    }
}
```
