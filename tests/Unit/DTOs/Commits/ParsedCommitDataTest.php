<?php

declare(strict_types=1);

use App\Constants\CommitType;
use App\DTOs\Commits\ParsedCommitData;

describe('ParsedCommitData', function () {
    describe('factory methods', function () {
        it('creates conventional commit data', function () {
            $data = ParsedCommitData::conventional(
                type: CommitType::Feat,
                scope: 'auth',
                description: 'add login',
                externalRefs: [['type' => 'github', 'id' => '#123']],
                isBreakingChange: true,
            );

            expect($data->type)->toBe(CommitType::Feat)
                ->and($data->scope)->toBe('auth')
                ->and($data->description)->toBe('add login')
                ->and($data->externalRefs)->toHaveCount(1)
                ->and($data->isBreakingChange)->toBeTrue()
                ->and($data->isConventional)->toBeTrue()
                ->and($data->isMerge)->toBeFalse();
        });

        it('creates inferred commit data', function () {
            $data = ParsedCommitData::inferred(
                type: CommitType::Fix,
                description: 'fix login bug',
                externalRefs: [],
                isMerge: false,
            );

            expect($data->type)->toBe(CommitType::Fix)
                ->and($data->scope)->toBeNull()
                ->and($data->isConventional)->toBeFalse()
                ->and($data->isBreakingChange)->toBeFalse()
                ->and($data->isMerge)->toBeFalse();
        });

        it('creates merge commit data', function () {
            $data = ParsedCommitData::merge(
                description: 'Merge branch feature',
                externalRefs: [['type' => 'github', 'id' => '#456']],
            );

            expect($data->type)->toBe(CommitType::Other)
                ->and($data->isMerge)->toBeTrue()
                ->and($data->isConventional)->toBeFalse()
                ->and($data->isBreakingChange)->toBeFalse();
        });
    });

    describe('toArray', function () {
        it('converts to array for model storage', function () {
            $data = ParsedCommitData::conventional(
                type: CommitType::Feat,
                scope: 'api',
                description: 'new endpoint',
                externalRefs: [['type' => 'github', 'id' => '#123']],
            );

            $array = $data->toArray();

            expect($array)->toHaveKeys(['commit_type', 'scope', 'external_refs', 'is_merge'])
                ->and($array['commit_type'])->toBe(CommitType::Feat)
                ->and($array['scope'])->toBe('api')
                ->and($array['external_refs'])->toHaveCount(1)
                ->and($array['is_merge'])->toBeFalse();
        });

        it('returns null for empty external refs', function () {
            $data = ParsedCommitData::conventional(
                type: CommitType::Fix,
                scope: null,
                description: 'fix bug',
                externalRefs: [],
            );

            $array = $data->toArray();

            expect($array['external_refs'])->toBeNull();
        });
    });

    describe('helper methods', function () {
        it('checks for external refs', function () {
            $withRefs = ParsedCommitData::conventional(
                type: CommitType::Feat,
                scope: null,
                description: 'test',
                externalRefs: [['type' => 'github', 'id' => '#1']],
            );

            $withoutRefs = ParsedCommitData::conventional(
                type: CommitType::Feat,
                scope: null,
                description: 'test',
                externalRefs: [],
            );

            expect($withRefs->hasExternalRefs())->toBeTrue()
                ->and($withoutRefs->hasExternalRefs())->toBeFalse();
        });

        it('filters refs by type', function () {
            $data = ParsedCommitData::conventional(
                type: CommitType::Fix,
                scope: null,
                description: 'fix',
                externalRefs: [
                    ['type' => 'github', 'id' => '#1'],
                    ['type' => 'github', 'id' => '#2'],
                    ['type' => 'jira', 'id' => 'PROJ-123'],
                ],
            );

            $githubRefs = $data->getRefsByType('github');
            $jiraRefs = $data->getRefsByType('jira');
            $linearRefs = $data->getRefsByType('linear');

            expect($githubRefs)->toHaveCount(2)
                ->and($jiraRefs)->toHaveCount(1)
                ->and($linearRefs)->toBeEmpty();
        });
    });
});
