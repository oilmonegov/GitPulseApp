<?php

declare(strict_types=1);

use App\Actions\Commits\CategorizeCommitAction;
use App\Constants\CommitType;

describe('CategorizeCommitAction', function () {
    describe('feature detection', function () {
        it('detects feature keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Feat);
        })->with([
            'Add user authentication',
            'Implement new login flow',
            'New dashboard feature',
            'Create user profile page',
            'Introduce caching layer',
            'Support for dark mode',
            'Enable two-factor auth',
        ]);
    });

    describe('bug fix detection', function () {
        it('detects fix keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Fix);
        })->with([
            'Fix login bug',
            'Bug in user registration',
            'Patch security vulnerability',
            'Resolve issue with database',
            'Hotfix for production',
            'Error in calculation fixed',
        ]);
    });

    describe('documentation detection', function () {
        it('detects docs keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Docs);
        })->with([
            'Docs for API endpoints',
            'Documentation improvements',
            'README changes',
            'Changelog entries',
            'Contributing guide',
        ]);
    });

    describe('test detection', function () {
        it('detects test keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Test);
        })->with([
            'Test for user service',
            'Test coverage improvement',
            'Integration test',
            'Mock for external API',
            'E2E tests for checkout',
        ]);
    });

    describe('refactor detection', function () {
        it('detects refactor keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Refactor);
        })->with([
            'Refactor user service',
            'Restructure project folders',
            'Simplify authentication logic',
            'Extract helper functions',
            'Rename variables for clarity',
            'Clean up old code',
        ]);
    });

    describe('style detection', function () {
        it('detects style keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Style);
        })->with([
            'Format code with prettier',
            'Lint configuration',
            'Whitespace cleanup',
            'Apply PSR-12 coding standard',
        ]);
    });

    describe('performance detection', function () {
        it('detects perf keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Perf);
        })->with([
            'Optimize database queries',
            'Performance improvement',
            'Speed up page load',
            'Cache API responses',
            'Lazy loading for images',
        ]);
    });

    describe('CI/CD detection', function () {
        it('detects ci keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Ci);
        })->with([
            'CI pipeline configuration',
            'GitHub Action workflow',
            'Deployment script',
            'CircleCI setup',
        ]);
    });

    describe('build detection', function () {
        it('detects build keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Build);
        })->with([
            'Webpack configuration',
            'Vite build settings',
            'Bundle optimization',
            'Compile assets',
        ]);
    });

    describe('chore detection', function () {
        it('detects chore keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Chore);
        })->with([
            'Update dependencies',
            'Bump version to 2.0',
            'Remove unused files',
            'Upgrade packages',
        ]);
    });

    describe('revert detection', function () {
        it('detects revert keywords', function (string $message) {
            $action = new CategorizeCommitAction($message);
            $result = $action->execute();

            expect($result)->toBe(CommitType::Revert);
        })->with([
            'Revert previous commit',
            'Rollback changes',
            'Undo last merge',
            'Restore old behavior',
        ]);
    });

    describe('phrase patterns', function () {
        it('matches phrase patterns with high confidence', function () {
            $action = new CategorizeCommitAction('Bug fix for login issue');
            $result = $action->execute();
            $confidence = $action->getConfidence();

            expect($result)->toBe(CommitType::Fix)
                ->and($confidence)->toBeGreaterThanOrEqual(0.9);
        });
    });

    describe('confidence scoring', function () {
        it('returns high confidence for phrase matches', function () {
            $action = new CategorizeCommitAction('Fix for issue with database');
            $confidence = $action->getConfidence();

            expect($confidence)->toBeGreaterThanOrEqual(0.9);
        });

        it('returns lower confidence for keyword matches', function () {
            $action = new CategorizeCommitAction('Updated some code');
            $confidence = $action->getConfidence();

            expect($confidence)->toBeLessThan(0.9);
        });

        it('returns minimum confidence for no matches', function () {
            $action = new CategorizeCommitAction('xyz abc 123');
            $confidence = $action->getConfidence();

            expect($confidence)->toBe(0.4);
        });
    });

    describe('edge cases', function () {
        it('returns Other for unrecognized messages', function () {
            $action = new CategorizeCommitAction('xyz abc 123');
            $result = $action->execute();

            expect($result)->toBe(CommitType::Other);
        });

        it('handles empty messages', function () {
            $action = new CategorizeCommitAction('');
            $result = $action->execute();

            expect($result)->toBe(CommitType::Other);
        });

        it('handles case insensitivity', function () {
            $action = new CategorizeCommitAction('FIX: Bug in UPPERCASE');
            $result = $action->execute();

            expect($result)->toBe(CommitType::Fix);
        });

        it('prioritizes by keyword score when multiple types match', function () {
            // "fix" has higher weight when it appears more prominently
            $action = new CategorizeCommitAction('fix fix fix and add');
            $result = $action->execute();

            expect($result)->toBe(CommitType::Fix);
        });
    });
});
