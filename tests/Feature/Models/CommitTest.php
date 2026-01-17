<?php

declare(strict_types=1);

use App\Constants\CommitType;
use App\Models\Commit;
use App\Models\Repository;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

describe('Commit Model', function (): void {
    it('can be created with factory', function (): void {
        $commit = Commit::factory()->create();

        expect($commit)->toBeInstanceOf(Commit::class)
            ->and($commit->id)->toBeInt()
            ->and($commit->sha)->toBeString()
            ->and($commit->message)->toBeString();
    });

    it('belongs to a repository', function (): void {
        $repository = Repository::factory()->create();
        $commit = Commit::factory()->for($repository)->create();

        expect($commit->repository)->toBeInstanceOf(Repository::class)
            ->and($commit->repository->id)->toBe($repository->id);
    });

    it('belongs to a user', function (): void {
        $user = User::factory()->create();
        $commit = Commit::factory()->for($user)->create();

        expect($commit->user)->toBeInstanceOf(User::class)
            ->and($commit->user->id)->toBe($user->id);
    });

    it('casts commit_type to enum', function (): void {
        $commit = Commit::factory()->feature()->create();

        expect($commit->commit_type)->toBeInstanceOf(CommitType::class)
            ->and($commit->commit_type)->toBe(CommitType::Feat);
    });

    it('casts dates to immutable datetime', function (): void {
        $commit = Commit::factory()->create();

        expect($commit->committed_at)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($commit->created_at)->toBeInstanceOf(DateTimeImmutable::class)
            ->and($commit->updated_at)->toBeInstanceOf(DateTimeImmutable::class);
    });

    it('casts JSON fields correctly', function (): void {
        $commit = Commit::factory()->withFiles()->withExternalRefs()->create();

        expect($commit->files)->toBeArray()
            ->and($commit->external_refs)->toBeArray();
    });

    it('casts numbers correctly', function (): void {
        $commit = Commit::factory()->create([
            'additions' => 100,
            'deletions' => 50,
            'files_changed' => 10,
        ]);

        expect($commit->additions)->toBeInt()->toBe(100)
            ->and($commit->deletions)->toBeInt()->toBe(50)
            ->and($commit->files_changed)->toBeInt()->toBe(10);
    });

    describe('scopes', function (): void {
        it('filters commits by type', function (): void {
            Commit::factory()->count(2)->feature()->create();
            Commit::factory()->fix()->create();

            $features = Commit::ofType(CommitType::Feat)->get();

            expect($features)->toHaveCount(2);
        });

        it('filters merge commits', function (): void {
            Commit::factory()->count(2)->merge()->create();
            Commit::factory()->create(['is_merge' => false]);

            $merges = Commit::merge()->get();

            expect($merges)->toHaveCount(2);
        });

        it('filters non-merge commits', function (): void {
            Commit::factory()->merge()->create();
            Commit::factory()->count(2)->create(['is_merge' => false]);

            $nonMerges = Commit::nonMerge()->get();

            expect($nonMerges)->toHaveCount(2);
        });

        it('filters commits by date', function (): void {
            $today = Carbon::today();
            Commit::factory()->count(2)->committedOn($today)->create();
            Commit::factory()->committedOn($today->copy()->subDay())->create();

            $todayCommits = Commit::onDate($today)->get();

            expect($todayCommits)->toHaveCount(2);
        });

        it('filters commits between dates', function (): void {
            $start = Carbon::now()->subDays(7);
            $end = Carbon::now();

            Commit::factory()->count(3)->committedOn(Carbon::now()->subDays(3))->create();
            Commit::factory()->committedOn(Carbon::now()->subDays(10))->create();

            $commits = Commit::betweenDates($start, $end)->get();

            expect($commits)->toHaveCount(3);
        });

        it('filters high impact commits', function (): void {
            Commit::factory()->count(2)->highImpact()->create();
            Commit::factory()->lowImpact()->create();

            $highImpact = Commit::highImpact(5.0)->get();

            expect($highImpact)->toHaveCount(2);
        });

        it('orders commits by recent', function (): void {
            $old = Commit::factory()->committedOn(Carbon::now()->subDays(5))->create();
            $new = Commit::factory()->committedOn(Carbon::now())->create();

            $commits = Commit::recent()->get();

            expect($commits->first()->id)->toBe($new->id)
                ->and($commits->last()->id)->toBe($old->id);
        });
    });

    describe('accessors', function (): void {
        it('returns short SHA', function (): void {
            $commit = Commit::factory()->create([
                'sha' => 'abc1234567890def1234567890abc1234567890d',
            ]);

            expect($commit->short_sha)->toBe('abc1234');
        });

        it('calculates total lines changed', function (): void {
            $commit = Commit::factory()->create([
                'additions' => 100,
                'deletions' => 50,
            ]);

            expect($commit->total_lines_changed)->toBe(150);
        });

        it('extracts commit title', function (): void {
            $commit = Commit::factory()->create([
                'message' => "feat: add new feature\n\nThis is the body.",
            ]);

            expect($commit->title)->toBe('feat: add new feature');
        });

        it('extracts commit body', function (): void {
            $commit = Commit::factory()->create([
                'message' => "feat: add new feature\n\nThis is the body.",
            ]);

            expect($commit->body)->toBe('This is the body.');
        });

        it('returns null for commit without body', function (): void {
            $commit = Commit::factory()->create([
                'message' => 'feat: add new feature',
            ]);

            expect($commit->body)->toBeNull();
        });

        it('generates GitHub URL from repository', function (): void {
            $repository = Repository::factory()->create([
                'full_name' => 'owner/repo',
            ]);
            $commit = Commit::factory()->for($repository)->create([
                'sha' => 'abc123',
                'url' => null,
            ]);

            expect($commit->github_url)->toBe('https://github.com/owner/repo/commit/abc123');
        });

        it('uses URL field if set', function (): void {
            $commit = Commit::factory()->create([
                'url' => 'https://github.com/owner/repo/commit/custom',
            ]);

            expect($commit->github_url)->toBe('https://github.com/owner/repo/commit/custom');
        });
    });

    describe('methods', function (): void {
        it('checks for external refs', function (): void {
            $withRefs = Commit::factory()->withExternalRefs()->create();
            $withoutRefs = Commit::factory()->create(['external_refs' => null]);

            expect($withRefs->hasExternalRefs())->toBeTrue()
                ->and($withoutRefs->hasExternalRefs())->toBeFalse();
        });
    });

    describe('factory states', function (): void {
        it('creates merge commit', function (): void {
            $commit = Commit::factory()->merge()->create();

            expect($commit->is_merge)->toBeTrue()
                ->and($commit->commit_type)->toBe(CommitType::Other);
        });

        it('creates feature commit', function (): void {
            $commit = Commit::factory()->feature()->create();

            expect($commit->commit_type)->toBe(CommitType::Feat)
                ->and($commit->message)->toContain('feat:');
        });

        it('creates fix commit', function (): void {
            $commit = Commit::factory()->fix()->create();

            expect($commit->commit_type)->toBe(CommitType::Fix)
                ->and($commit->message)->toContain('fix:');
        });

        it('creates docs commit', function (): void {
            $commit = Commit::factory()->docs()->create();

            expect($commit->commit_type)->toBe(CommitType::Docs)
                ->and($commit->message)->toContain('docs:');
        });

        it('creates high impact commit', function (): void {
            $commit = Commit::factory()->highImpact()->create();

            expect($commit->additions)->toBeGreaterThanOrEqual(200)
                ->and($commit->impact_score)->toBeGreaterThanOrEqual(7.0);
        });

        it('creates low impact commit', function (): void {
            $commit = Commit::factory()->lowImpact()->create();

            expect($commit->additions)->toBeLessThanOrEqual(20)
                ->and($commit->impact_score)->toBeLessThanOrEqual(2.0);
        });

        it('creates commit on specific date', function (): void {
            $date = Carbon::parse('2025-01-15 10:00:00');
            $commit = Commit::factory()->committedOn($date)->create();

            expect($commit->committed_at->toDateString())->toBe('2025-01-15');
        });

        it('creates commit with external refs', function (): void {
            $commit = Commit::factory()->withExternalRefs()->create();

            expect($commit->external_refs)->toBeArray()
                ->and($commit->external_refs)->not->toBeEmpty();
        });

        it('creates commit with files', function (): void {
            $commit = Commit::factory()->withFiles()->create();

            expect($commit->files)->toBeArray()
                ->and($commit->files)->not->toBeEmpty();
        });
    });
});
