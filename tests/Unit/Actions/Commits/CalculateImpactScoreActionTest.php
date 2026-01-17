<?php

declare(strict_types=1);

use App\Actions\Commits\CalculateImpactScoreAction;
use App\Constants\CommitType;
use App\DTOs\Commits\ParsedCommitData;
use Illuminate\Support\Carbon;

describe('CalculateImpactScoreAction', function () {
    function createParsedData(
        CommitType $type = CommitType::Feat,
        bool $isMerge = false,
        bool $hasRefs = false,
    ): ParsedCommitData {
        $refs = $hasRefs ? [['type' => 'github', 'id' => '#123']] : [];

        if ($isMerge) {
            return ParsedCommitData::merge('Merge branch', $refs);
        }

        return ParsedCommitData::conventional(
            type: $type,
            scope: null,
            description: 'test commit',
            externalRefs: $refs,
        );
    }

    describe('impact score calculation', function () {
        it('calculates score for a typical feature commit', function () {
            $parsed = createParsedData(CommitType::Feat, false, true);

            $action = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 100,
                deletions: 20,
                filesChanged: 5,
                committedAt: Carbon::create(2024, 1, 15, 10, 0, 0),
            );

            $score = $action->execute();

            // Score should be positive and reasonable
            expect($score)->toBeGreaterThan(0)
                ->and($score)->toBeLessThanOrEqual(15);
        });

        it('calculates higher score for larger commits', function () {
            $parsed = createParsedData(CommitType::Feat);

            $smallCommit = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 10,
                deletions: 5,
                filesChanged: 1,
                committedAt: Carbon::now(),
            );

            $largeCommit = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 500,
                deletions: 100,
                filesChanged: 20,
                committedAt: Carbon::now(),
            );

            expect($largeCommit->execute())->toBeGreaterThan($smallCommit->execute());
        });

        it('scores merge commits higher', function () {
            $regular = createParsedData(CommitType::Feat, isMerge: false);
            $merge = createParsedData(CommitType::Other, isMerge: true);

            $regularAction = new CalculateImpactScoreAction(
                parsedData: $regular,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::now(),
            );

            $mergeAction = new CalculateImpactScoreAction(
                parsedData: $merge,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::now(),
            );

            // Merge commits have higher merge factor
            expect($mergeAction->execute())->toBeGreaterThan($regularAction->execute());
        });

        it('scores commits with external refs higher', function () {
            $withRefs = createParsedData(CommitType::Fix, false, hasRefs: true);
            $withoutRefs = createParsedData(CommitType::Fix, false, hasRefs: false);

            $withRefsAction = new CalculateImpactScoreAction(
                parsedData: $withRefs,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::now(),
            );

            $withoutRefsAction = new CalculateImpactScoreAction(
                parsedData: $withoutRefs,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::now(),
            );

            expect($withRefsAction->execute())->toBeGreaterThan($withoutRefsAction->execute());
        });
    });

    describe('commit type weights', function () {
        it('gives features highest weight', function () {
            $feat = createParsedData(CommitType::Feat);
            $chore = createParsedData(CommitType::Chore);

            $featAction = new CalculateImpactScoreAction(
                parsedData: $feat,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::now(),
            );

            $choreAction = new CalculateImpactScoreAction(
                parsedData: $chore,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::now(),
            );

            expect($featAction->execute())->toBeGreaterThan($choreAction->execute());
        });

        it('respects type weight ordering', function () {
            $types = [
                CommitType::Feat,     // 1.0
                CommitType::Fix,      // 0.8
                CommitType::Refactor, // 0.7
                CommitType::Perf,     // 0.7
                CommitType::Test,     // 0.6
                CommitType::Docs,     // 0.5
                CommitType::Build,    // 0.4
                CommitType::Ci,       // 0.4
                CommitType::Style,    // 0.3
                CommitType::Chore,    // 0.3
            ];

            $scores = [];

            foreach ($types as $type) {
                $parsed = createParsedData($type);
                $action = new CalculateImpactScoreAction(
                    parsedData: $parsed,
                    additions: 50,
                    deletions: 10,
                    filesChanged: 3,
                    committedAt: Carbon::now(),
                );
                $scores[$type->value] = $action->execute();
            }

            // Feat should have highest score, Style/Chore lowest
            expect($scores['feat'])->toBeGreaterThan($scores['fix'])
                ->and($scores['fix'])->toBeGreaterThanOrEqual($scores['refactor'])
                ->and($scores['test'])->toBeGreaterThan($scores['docs'])
                ->and($scores['docs'])->toBeGreaterThan($scores['style']);
        });
    });

    describe('focus time factor', function () {
        it('gives bonus for peak hours (9-17)', function () {
            $parsed = createParsedData(CommitType::Feat);

            $peakHours = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::create(2024, 1, 15, 14, 0, 0), // 2 PM
            );

            $offHours = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::create(2024, 1, 15, 20, 0, 0), // 8 PM
            );

            expect($peakHours->execute())->toBeGreaterThan($offHours->execute());
        });

        it('penalizes late night commits (23-5)', function () {
            $parsed = createParsedData(CommitType::Feat);

            $normalHours = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::create(2024, 1, 15, 20, 0, 0), // 8 PM
            );

            $lateNight = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 50,
                deletions: 10,
                filesChanged: 3,
                committedAt: Carbon::create(2024, 1, 15, 2, 0, 0), // 2 AM
            );

            expect($normalHours->execute())->toBeGreaterThan($lateNight->execute());
        });
    });

    describe('repository average', function () {
        it('uses repository average when provided', function () {
            $parsed = createParsedData(CommitType::Feat);

            // With low repo average, same lines count as more impactful
            $withLowAvg = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 100,
                deletions: 20,
                filesChanged: 3,
                committedAt: Carbon::now(),
                repositoryAvgLines: 30,
            );

            // With high repo average, same lines count as less impactful
            $withHighAvg = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 100,
                deletions: 20,
                filesChanged: 3,
                committedAt: Carbon::now(),
                repositoryAvgLines: 200,
            );

            expect($withLowAvg->execute())->toBeGreaterThan($withHighAvg->execute());
        });
    });

    describe('breakdown', function () {
        it('returns factor breakdown', function () {
            $parsed = createParsedData(CommitType::Feat, false, true);

            $action = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 100,
                deletions: 20,
                filesChanged: 5,
                committedAt: Carbon::create(2024, 1, 15, 10, 0, 0),
            );

            $breakdown = $action->getBreakdown();

            expect($breakdown)->toHaveKeys([
                'lines_changed',
                'files_touched',
                'commit_type',
                'merge_commit',
                'external_refs',
                'focus_time',
            ]);

            foreach ($breakdown as $factor => $data) {
                expect($data)->toHaveKeys(['weight', 'score', 'weighted']);
            }
        });
    });

    describe('score boundaries', function () {
        it('returns minimum score for empty commit', function () {
            $parsed = createParsedData(CommitType::Chore);

            $action = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 0,
                deletions: 0,
                filesChanged: 0,
                committedAt: Carbon::create(2024, 1, 15, 3, 0, 0), // Late night
            );

            $score = $action->execute();

            expect($score)->toBeGreaterThanOrEqual(0);
        });

        it('caps lines changed factor at 2.0', function () {
            $parsed = createParsedData(CommitType::Feat);

            // Massive commit
            $action = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 10000,
                deletions: 5000,
                filesChanged: 100,
                committedAt: Carbon::now(),
            );

            $breakdown = $action->getBreakdown();

            // Lines changed factor should be capped at 2.0
            expect($breakdown['lines_changed']['score'])->toBe(2.0);
        });

        it('caps files touched factor at 1.5', function () {
            $parsed = createParsedData(CommitType::Feat);

            $action = new CalculateImpactScoreAction(
                parsedData: $parsed,
                additions: 100,
                deletions: 50,
                filesChanged: 100, // Many files
                committedAt: Carbon::now(),
            );

            $breakdown = $action->getBreakdown();

            // Files touched factor should be capped at 1.5
            expect($breakdown['files_touched']['score'])->toBe(1.5);
        });
    });
});
