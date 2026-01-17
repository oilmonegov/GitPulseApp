# F2: Testing

## Test Coverage Requirements

- **Unit Tests**: 95% coverage for all action classes
- **Edge Cases**: Non-standard commit formats, unicode, special characters

---

## Unit Tests

### `tests/Unit/Actions/ParseCommitMessageTest.php`

```php
<?php

declare(strict_types=1);

use App\Actions\Commits\ParseCommitMessage;
use App\Enums\CommitType;

describe('ParseCommitMessage', function () {
    beforeEach(function () {
        $this->action = new ParseCommitMessage();
    });

    describe('Conventional Commits Parsing', function () {
        it('parses simple conventional commit', function () {
            $result = $this->action->execute('feat: add user authentication');

            expect($result->type)->toBe(CommitType::FEAT);
            expect($result->scope)->toBeNull();
            expect($result->description)->toBe('add user authentication');
            expect($result->is_breaking)->toBeFalse();
        });

        it('parses conventional commit with scope', function () {
            $result = $this->action->execute('fix(auth): resolve token expiry issue');

            expect($result->type)->toBe(CommitType::FIX);
            expect($result->scope)->toBe('auth');
            expect($result->description)->toBe('resolve token expiry issue');
        });

        it('parses breaking change with exclamation mark', function () {
            $result = $this->action->execute('feat!: remove deprecated API');

            expect($result->type)->toBe(CommitType::FEAT);
            expect($result->is_breaking)->toBeTrue();
        });

        it('parses breaking change with scope and exclamation', function () {
            $result = $this->action->execute('feat(api)!: change response format');

            expect($result->type)->toBe(CommitType::FEAT);
            expect($result->scope)->toBe('api');
            expect($result->is_breaking)->toBeTrue();
        });

        it('parses all commit types', function () {
            $types = ['feat', 'fix', 'chore', 'docs', 'refactor', 'test', 'style', 'perf'];

            foreach ($types as $type) {
                $result = $this->action->execute("{$type}: description");
                expect($result->type->value)->toBe($type);
            }
        });
    });

    describe('External Reference Extraction', function () {
        it('extracts GitHub issue references', function () {
            $result = $this->action->execute('fix: resolve issue #123');

            expect($result->external_refs)->toContain('#123');
        });

        it('extracts multiple GitHub references', function () {
            $result = $this->action->execute('feat: implement #123 and #456');

            expect($result->external_refs)->toContain('#123');
            expect($result->external_refs)->toContain('#456');
        });

        it('extracts JIRA ticket references', function () {
            $result = $this->action->execute('fix: resolve JIRA-123 issue');

            expect($result->external_refs)->toContain('JIRA-123');
        });

        it('extracts Linear ticket references', function () {
            $result = $this->action->execute('feat: implement ENG-456');

            expect($result->external_refs)->toContain('ENG-456');
        });

        it('extracts mixed references', function () {
            $result = $this->action->execute('fix: resolve #123 JIRA-456 ENG-789');

            expect($result->external_refs)->toHaveCount(3);
        });

        it('removes duplicate references', function () {
            $result = $this->action->execute('fix: #123 and #123 again');

            expect($result->external_refs)->toHaveCount(1);
        });
    });

    describe('Non-Conventional Messages', function () {
        it('returns null type for non-conventional messages', function () {
            $result = $this->action->execute('Fixed the login bug');

            expect($result->type)->toBeNull();
            expect($result->description)->toBe('Fixed the login bug');
        });

        it('detects BREAKING CHANGE in body', function () {
            $message = "Update API\n\nBREAKING CHANGE: New response format";
            $result = $this->action->execute($message);

            expect($result->is_breaking)->toBeTrue();
        });

        it('extracts first line as description', function () {
            $message = "Main description\n\nDetailed body text";
            $result = $this->action->execute($message);

            expect($result->description)->toBe('Main description');
        });
    });
});
```

### `tests/Unit/Actions/CategorizeCommitTest.php`

```php
<?php

declare(strict_types=1);

use App\Actions\Commits\CategorizeCommit;
use App\Enums\CommitType;

describe('CategorizeCommit', function () {
    beforeEach(function () {
        $this->action = new CategorizeCommit();
    });

    describe('Feature Detection', function () {
        it('categorizes "add" as feature', function () {
            expect($this->action->execute('Add user profile page'))->toBe(CommitType::FEAT);
        });

        it('categorizes "implement" as feature', function () {
            expect($this->action->execute('Implement OAuth2 login'))->toBe(CommitType::FEAT);
        });

        it('categorizes "new" as feature', function () {
            expect($this->action->execute('New dashboard widget'))->toBe(CommitType::FEAT);
        });

        it('categorizes "create" as feature', function () {
            expect($this->action->execute('Create API endpoint'))->toBe(CommitType::FEAT);
        });
    });

    describe('Fix Detection', function () {
        it('categorizes "fix" as fix', function () {
            expect($this->action->execute('Fix login error'))->toBe(CommitType::FIX);
        });

        it('categorizes "bug" as fix', function () {
            expect($this->action->execute('Bug in checkout flow'))->toBe(CommitType::FIX);
        });

        it('categorizes "resolve" as fix', function () {
            expect($this->action->execute('Resolve memory leak'))->toBe(CommitType::FIX);
        });

        it('categorizes "patch" as fix', function () {
            expect($this->action->execute('Patch security vulnerability'))->toBe(CommitType::FIX);
        });
    });

    describe('Refactor Detection', function () {
        it('categorizes "refactor" as refactor', function () {
            expect($this->action->execute('Refactor authentication module'))->toBe(CommitType::REFACTOR);
        });

        it('categorizes "cleanup" as refactor', function () {
            expect($this->action->execute('Cleanup unused imports'))->toBe(CommitType::REFACTOR);
        });

        it('categorizes "simplify" as refactor', function () {
            expect($this->action->execute('Simplify validation logic'))->toBe(CommitType::REFACTOR);
        });
    });

    describe('Performance Detection', function () {
        it('categorizes "optimize" as perf', function () {
            expect($this->action->execute('Optimize database queries'))->toBe(CommitType::PERF);
        });

        it('categorizes "performance" as perf', function () {
            expect($this->action->execute('Performance improvements'))->toBe(CommitType::PERF);
        });

        it('categorizes "cache" as perf', function () {
            expect($this->action->execute('Cache API responses'))->toBe(CommitType::PERF);
        });
    });

    describe('Test Detection', function () {
        it('categorizes "test" as test', function () {
            expect($this->action->execute('Test user registration'))->toBe(CommitType::TEST);
        });

        it('categorizes "coverage" as test', function () {
            expect($this->action->execute('Increase coverage'))->toBe(CommitType::TEST);
        });
    });

    describe('Documentation Detection', function () {
        it('categorizes "docs" as docs', function () {
            expect($this->action->execute('Update docs'))->toBe(CommitType::DOCS);
        });

        it('categorizes "readme" as docs', function () {
            expect($this->action->execute('Update README'))->toBe(CommitType::DOCS);
        });
    });

    describe('Style Detection', function () {
        it('categorizes "format" as style', function () {
            expect($this->action->execute('Format code'))->toBe(CommitType::STYLE);
        });

        it('categorizes "lint" as style', function () {
            expect($this->action->execute('Lint fixes'))->toBe(CommitType::STYLE);
        });
    });

    describe('Chore Detection', function () {
        it('categorizes "bump" as chore', function () {
            expect($this->action->execute('Bump version to 1.2.0'))->toBe(CommitType::CHORE);
        });

        it('categorizes "update dependencies" as chore', function () {
            expect($this->action->execute('Update dependencies'))->toBe(CommitType::CHORE);
        });

        it('categorizes merge commits as chore', function () {
            expect($this->action->execute('Merge pull request #123'))->toBe(CommitType::CHORE);
            expect($this->action->execute('Merge branch feature/auth'))->toBe(CommitType::CHORE);
        });
    });

    describe('Unknown Messages', function () {
        it('returns OTHER for ambiguous messages', function () {
            expect($this->action->execute('Initial commit'))->toBe(CommitType::OTHER);
        });

        it('returns OTHER for gibberish', function () {
            expect($this->action->execute('asdfasdf'))->toBe(CommitType::OTHER);
        });
    });
});
```

### `tests/Unit/Actions/CalculateImpactScoreTest.php`

```php
<?php

declare(strict_types=1);

use App\Actions\Commits\CalculateImpactScore;
use App\Enums\CommitType;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;

describe('CalculateImpactScore', function () {
    beforeEach(function () {
        $this->action = new CalculateImpactScore();
        $this->user = User::factory()->create();
        $this->repository = Repository::factory()->for($this->user)->create();
    });

    describe('Commit Type Weighting', function () {
        it('scores feature commits higher than chore commits', function () {
            $featureCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create([
                    'commit_type' => CommitType::FEAT,
                    'additions' => 100,
                    'deletions' => 20,
                    'files_changed' => 5,
                ]);

            $choreCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create([
                    'commit_type' => CommitType::CHORE,
                    'additions' => 100,
                    'deletions' => 20,
                    'files_changed' => 5,
                ]);

            $featureScore = $this->action->execute($featureCommit);
            $choreScore = $this->action->execute($choreCommit);

            expect($featureScore)->toBeGreaterThan($choreScore);
        });

        it('scores fix commits higher than docs commits', function () {
            $fixCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['commit_type' => CommitType::FIX]);

            $docsCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['commit_type' => CommitType::DOCS]);

            expect($this->action->execute($fixCommit))
                ->toBeGreaterThan($this->action->execute($docsCommit));
        });
    });

    describe('Merge Commit Bonus', function () {
        it('gives bonus points for merge commits', function () {
            $regularCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['is_merge' => false]);

            $mergeCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['is_merge' => true]);

            expect($this->action->execute($mergeCommit))
                ->toBeGreaterThan($this->action->execute($regularCommit));
        });
    });

    describe('Lines Changed Normalization', function () {
        it('normalizes against repository average', function () {
            // Create existing commits to establish average
            Commit::factory()
                ->count(10)
                ->for($this->repository)
                ->for($this->user)
                ->create(['additions' => 50, 'deletions' => 10]);

            $smallCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['additions' => 10, 'deletions' => 5]);

            $largeCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['additions' => 200, 'deletions' => 50]);

            expect($this->action->execute($largeCommit))
                ->toBeGreaterThan($this->action->execute($smallCommit));
        });
    });

    describe('External References', function () {
        it('scores commits with references higher', function () {
            $withRefs = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['external_refs' => ['#123', 'JIRA-456']]);

            $withoutRefs = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['external_refs' => null]);

            expect($this->action->execute($withRefs))
                ->toBeGreaterThan($this->action->execute($withoutRefs));
        });
    });

    describe('Focus Time Scoring', function () {
        it('gives bonus for morning focus hours', function () {
            $morningCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['committed_at' => now()->setHour(10)]);

            $lateNightCommit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create(['committed_at' => now()->setHour(3)]);

            expect($this->action->execute($morningCommit))
                ->toBeGreaterThan($this->action->execute($lateNightCommit));
        });
    });

    describe('Score Range', function () {
        it('produces scores in reasonable range', function () {
            $commit = Commit::factory()
                ->for($this->repository)
                ->for($this->user)
                ->create();

            $score = $this->action->execute($commit);

            expect($score)->toBeGreaterThanOrEqual(0);
            expect($score)->toBeLessThanOrEqual(20); // Max reasonable score
        });
    });
});
```

---

## Edge Case Tests

### `tests/Unit/Actions/ParseCommitMessageEdgeCasesTest.php`

```php
<?php

declare(strict_types=1);

use App\Actions\Commits\ParseCommitMessage;

describe('ParseCommitMessage Edge Cases', function () {
    beforeEach(function () {
        $this->action = new ParseCommitMessage();
    });

    it('handles empty message', function () {
        $result = $this->action->execute('');

        expect($result->description)->toBe('');
        expect($result->type)->toBeNull();
    });

    it('handles message with only whitespace', function () {
        $result = $this->action->execute('   ');

        expect($result->description)->toBe('');
    });

    it('handles unicode characters', function () {
        $result = $this->action->execute('feat: 添加用户认证 🎉');

        expect($result->type->value)->toBe('feat');
        expect($result->description)->toContain('添加');
    });

    it('handles very long messages', function () {
        $longDescription = str_repeat('a', 1000);
        $result = $this->action->execute("feat: {$longDescription}");

        expect($result->type->value)->toBe('feat');
        expect(strlen($result->description))->toBe(1000);
    });

    it('handles multi-line commit messages', function () {
        $message = "feat: add feature\n\nThis is the body.\n\nFooter: value";
        $result = $this->action->execute($message);

        expect($result->type->value)->toBe('feat');
        expect($result->description)->toBe('add feature');
    });

    it('handles scope with special characters', function () {
        $result = $this->action->execute('fix(user-auth/oauth): resolve issue');

        expect($result->scope)->toBe('user-auth/oauth');
    });

    it('handles case variations in type', function () {
        $result = $this->action->execute('FEAT: uppercase type');

        expect($result->type->value)->toBe('feat');
    });

    it('handles extra spaces after colon', function () {
        $result = $this->action->execute('feat:    extra spaces');

        expect($result->description)->toBe('extra spaces');
    });
});
```
