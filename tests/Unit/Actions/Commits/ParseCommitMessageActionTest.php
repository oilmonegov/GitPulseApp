<?php

declare(strict_types=1);

use App\Actions\Commits\ParseCommitMessageAction;
use App\Constants\CommitType;

describe('ParseCommitMessageAction', function () {
    describe('conventional commits', function () {
        it('parses simple conventional commit', function () {
            $action = new ParseCommitMessageAction('feat: add user authentication');
            $result = $action->execute();

            expect($result->type)->toBe(CommitType::Feat)
                ->and($result->scope)->toBeNull()
                ->and($result->description)->toBe('add user authentication')
                ->and($result->isConventional)->toBeTrue()
                ->and($result->isBreakingChange)->toBeFalse();
        });

        it('parses conventional commit with scope', function () {
            $action = new ParseCommitMessageAction('fix(auth): resolve login issue');
            $result = $action->execute();

            expect($result->type)->toBe(CommitType::Fix)
                ->and($result->scope)->toBe('auth')
                ->and($result->description)->toBe('resolve login issue')
                ->and($result->isConventional)->toBeTrue();
        });

        it('parses breaking change with exclamation mark', function () {
            $action = new ParseCommitMessageAction('feat!: new API structure');
            $result = $action->execute();

            expect($result->type)->toBe(CommitType::Feat)
                ->and($result->isBreakingChange)->toBeTrue()
                ->and($result->isConventional)->toBeTrue();
        });

        it('parses breaking change with scope and exclamation', function () {
            $action = new ParseCommitMessageAction('refactor(api)!: restructure endpoints');
            $result = $action->execute();

            expect($result->type)->toBe(CommitType::Refactor)
                ->and($result->scope)->toBe('api')
                ->and($result->isBreakingChange)->toBeTrue();
        });

        it('parses all conventional commit types', function (string $prefix, CommitType $expected) {
            $action = new ParseCommitMessageAction("{$prefix}: test description");
            $result = $action->execute();

            expect($result->type)->toBe($expected)
                ->and($result->isConventional)->toBeTrue();
        })->with([
            ['feat', CommitType::Feat],
            ['fix', CommitType::Fix],
            ['docs', CommitType::Docs],
            ['style', CommitType::Style],
            ['refactor', CommitType::Refactor],
            ['perf', CommitType::Perf],
            ['test', CommitType::Test],
            ['build', CommitType::Build],
            ['ci', CommitType::Ci],
            ['chore', CommitType::Chore],
            ['revert', CommitType::Revert],
        ]);
    });

    describe('merge commits', function () {
        it('detects merge commit', function () {
            $action = new ParseCommitMessageAction('Merge branch \'feature\' into main');
            $result = $action->execute();

            expect($result->isMerge)->toBeTrue()
                ->and($result->type)->toBe(CommitType::Other)
                ->and($result->isConventional)->toBeFalse();
        });

        it('detects merge pull request', function () {
            $action = new ParseCommitMessageAction('Merge pull request #123 from user/branch');
            $result = $action->execute();

            expect($result->isMerge)->toBeTrue()
                ->and($result->type)->toBe(CommitType::Other);
        });
    });

    describe('external references', function () {
        it('extracts GitHub issue references', function () {
            $action = new ParseCommitMessageAction('fix: resolve issue #123');
            $result = $action->execute();

            expect($result->externalRefs)->toContain(['type' => 'github', 'id' => '#123']);
        });

        it('extracts multiple GitHub references', function () {
            $action = new ParseCommitMessageAction('fix: resolve #123 and #456');
            $result = $action->execute();

            expect($result->externalRefs)->toHaveCount(2)
                ->and($result->externalRefs)->toContain(['type' => 'github', 'id' => '#123'])
                ->and($result->externalRefs)->toContain(['type' => 'github', 'id' => '#456']);
        });

        it('extracts JIRA ticket references', function () {
            $action = new ParseCommitMessageAction('fix: PROJ-123 resolve database issue');
            $result = $action->execute();

            expect($result->externalRefs)->toContain(['type' => 'jira', 'id' => 'PROJ-123']);
        });

        it('extracts Linear ticket references', function () {
            $action = new ParseCommitMessageAction('feat: implement eng-456 feature');
            $result = $action->execute();

            expect($result->externalRefs)->toContain(['type' => 'linear', 'id' => 'ENG-456']);
        });

        it('extracts mixed references', function () {
            $action = new ParseCommitMessageAction('fix: resolve #123 for JIRA-456');
            $result = $action->execute();

            expect($result->externalRefs)->toHaveCount(2);
            expect($result->hasExternalRefs())->toBeTrue();
        });

        it('deduplicates references', function () {
            $action = new ParseCommitMessageAction('fix: #123 and #123 again');
            $result = $action->execute();

            expect($result->externalRefs)->toHaveCount(1);
        });
    });

    describe('non-conventional commits', function () {
        it('infers type from message keywords', function () {
            $action = new ParseCommitMessageAction('Add new feature for users');
            $result = $action->execute();

            expect($result->isConventional)->toBeFalse()
                ->and($result->type)->toBe(CommitType::Feat);
        });

        it('returns Other for unrecognized messages', function () {
            $action = new ParseCommitMessageAction('Random commit message');
            $result = $action->execute();

            expect($result->type)->toBe(CommitType::Other)
                ->and($result->isConventional)->toBeFalse();
        });
    });

    describe('edge cases', function () {
        it('handles multiline commit messages', function () {
            $message = "feat(api): add new endpoint\n\nThis is the body of the commit.";
            $action = new ParseCommitMessageAction($message);
            $result = $action->execute();

            expect($result->type)->toBe(CommitType::Feat)
                ->and($result->description)->toBe('add new endpoint');
        });

        it('handles empty scope', function () {
            $action = new ParseCommitMessageAction('fix(): something');
            $result = $action->execute();

            expect($result->scope)->toBeNull();
        });

        it('handles complex scopes', function () {
            $action = new ParseCommitMessageAction('feat(api/v2): new endpoint');
            $result = $action->execute();

            expect($result->scope)->toBe('api/v2');
        });
    });
});
