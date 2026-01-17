<?php

declare(strict_types=1);

namespace App\Constants;

/**
 * Conventional commit types for categorizing commits.
 *
 * @see https://www.conventionalcommits.org/
 */
enum CommitType: string
{
    case Feat = 'feat';
    case Fix = 'fix';
    case Docs = 'docs';
    case Style = 'style';
    case Refactor = 'refactor';
    case Perf = 'perf';
    case Test = 'test';
    case Build = 'build';
    case Ci = 'ci';
    case Chore = 'chore';
    case Revert = 'revert';
    case Other = 'other';

    /**
     * Get the display name for the commit type.
     */
    public function displayName(): string
    {
        return match ($this) {
            self::Feat => 'Feature',
            self::Fix => 'Bug Fix',
            self::Docs => 'Documentation',
            self::Style => 'Code Style',
            self::Refactor => 'Refactor',
            self::Perf => 'Performance',
            self::Test => 'Test',
            self::Build => 'Build',
            self::Ci => 'CI/CD',
            self::Chore => 'Chore',
            self::Revert => 'Revert',
            self::Other => 'Other',
        };
    }

    /**
     * Get the emoji for the commit type.
     */
    public function emoji(): string
    {
        return match ($this) {
            self::Feat => "\u{2728}",     // sparkles
            self::Fix => "\u{1F41B}",     // bug
            self::Docs => "\u{1F4DD}",    // memo
            self::Style => "\u{1F484}",   // lipstick
            self::Refactor => "\u{267B}", // recycle
            self::Perf => "\u{26A1}",     // zap
            self::Test => "\u{1F9EA}",    // test tube
            self::Build => "\u{1F4E6}",   // package
            self::Ci => "\u{1F477}",      // construction worker
            self::Chore => "\u{1F9F9}",   // broom
            self::Revert => "\u{23EA}",   // rewind
            self::Other => "\u{1F4AC}",   // speech bubble
        };
    }

    /**
     * Get the weight for impact score calculation.
     *
     * Higher weights indicate more impactful commit types.
     */
    public function weight(): float
    {
        return match ($this) {
            self::Feat => 1.0,
            self::Fix => 0.8,
            self::Refactor => 0.7,
            self::Perf => 0.7,
            self::Test => 0.6,
            self::Docs => 0.5,
            self::Build => 0.4,
            self::Ci => 0.4,
            self::Style => 0.3,
            self::Chore => 0.3,
            self::Revert => 0.5,
            self::Other => 0.5,
        };
    }

    /**
     * Get the color for UI display (Tailwind CSS classes).
     */
    public function color(): string
    {
        return match ($this) {
            self::Feat => 'text-green-600 bg-green-100 dark:text-green-400 dark:bg-green-900/30',
            self::Fix => 'text-red-600 bg-red-100 dark:text-red-400 dark:bg-red-900/30',
            self::Docs => 'text-blue-600 bg-blue-100 dark:text-blue-400 dark:bg-blue-900/30',
            self::Style => 'text-pink-600 bg-pink-100 dark:text-pink-400 dark:bg-pink-900/30',
            self::Refactor => 'text-yellow-600 bg-yellow-100 dark:text-yellow-400 dark:bg-yellow-900/30',
            self::Perf => 'text-purple-600 bg-purple-100 dark:text-purple-400 dark:bg-purple-900/30',
            self::Test => 'text-cyan-600 bg-cyan-100 dark:text-cyan-400 dark:bg-cyan-900/30',
            self::Build => 'text-orange-600 bg-orange-100 dark:text-orange-400 dark:bg-orange-900/30',
            self::Ci => 'text-indigo-600 bg-indigo-100 dark:text-indigo-400 dark:bg-indigo-900/30',
            self::Chore => 'text-gray-600 bg-gray-100 dark:text-gray-400 dark:bg-gray-800',
            self::Revert => 'text-amber-600 bg-amber-100 dark:text-amber-400 dark:bg-amber-900/30',
            self::Other => 'text-slate-600 bg-slate-100 dark:text-slate-400 dark:bg-slate-800',
        };
    }

    /**
     * Get all commit types as an array for select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_combine(
            array_column(self::cases(), 'value'),
            array_map(fn (self $type) => $type->displayName(), self::cases()),
        );
    }

    /**
     * Try to parse a commit type from a string.
     */
    public static function fromString(string $value): self
    {
        return self::tryFrom(strtolower($value)) ?? self::Other;
    }
}
