<?php

declare(strict_types=1);

use App\Constants\CommitType;

describe('CommitType Enum', function (): void {
    it('has all conventional commit types', function (): void {
        $cases = CommitType::cases();
        $values = array_column($cases, 'value');

        expect($values)->toContain('feat')
            ->and($values)->toContain('fix')
            ->and($values)->toContain('docs')
            ->and($values)->toContain('style')
            ->and($values)->toContain('refactor')
            ->and($values)->toContain('perf')
            ->and($values)->toContain('test')
            ->and($values)->toContain('build')
            ->and($values)->toContain('ci')
            ->and($values)->toContain('chore')
            ->and($values)->toContain('revert')
            ->and($values)->toContain('other');
    });

    it('returns display name for all types', function (CommitType $type, string $expectedName): void {
        expect($type->displayName())->toBe($expectedName);
    })->with([
        [CommitType::Feat, 'Feature'],
        [CommitType::Fix, 'Bug Fix'],
        [CommitType::Docs, 'Documentation'],
        [CommitType::Style, 'Code Style'],
        [CommitType::Refactor, 'Refactor'],
        [CommitType::Perf, 'Performance'],
        [CommitType::Test, 'Test'],
        [CommitType::Build, 'Build'],
        [CommitType::Ci, 'CI/CD'],
        [CommitType::Chore, 'Chore'],
        [CommitType::Revert, 'Revert'],
        [CommitType::Other, 'Other'],
    ]);

    it('returns weight for all types', function (CommitType $type): void {
        $weight = $type->weight();

        expect($weight)->toBeFloat()
            ->and($weight)->toBeGreaterThanOrEqual(0.3)
            ->and($weight)->toBeLessThanOrEqual(1.0);
    })->with(CommitType::cases());

    it('has correct weight ordering for impact', function (): void {
        expect(CommitType::Feat->weight())->toBeGreaterThan(CommitType::Fix->weight())
            ->and(CommitType::Fix->weight())->toBeGreaterThan(CommitType::Refactor->weight())
            ->and(CommitType::Refactor->weight())->toBeGreaterThanOrEqual(CommitType::Test->weight())
            ->and(CommitType::Test->weight())->toBeGreaterThan(CommitType::Docs->weight())
            ->and(CommitType::Docs->weight())->toBeGreaterThan(CommitType::Chore->weight());
    });

    it('returns emoji for all types', function (CommitType $type): void {
        $emoji = $type->emoji();

        expect($emoji)->toBeString()
            ->and(mb_strlen($emoji))->toBeGreaterThanOrEqual(1);
    })->with(CommitType::cases());

    it('returns color classes for all types', function (CommitType $type): void {
        $color = $type->color();

        expect($color)->toBeString()
            ->and($color)->toContain('text-')
            ->and($color)->toContain('bg-');
    })->with(CommitType::cases());

    it('returns options array for select inputs', function (): void {
        $options = CommitType::options();

        expect($options)->toBeArray()
            ->and($options)->toHaveCount(12)
            ->and($options['feat'])->toBe('Feature')
            ->and($options['fix'])->toBe('Bug Fix');
    });

    it('creates type from string using fromString', function (): void {
        expect(CommitType::fromString('feat'))->toBe(CommitType::Feat)
            ->and(CommitType::fromString('FEAT'))->toBe(CommitType::Feat)
            ->and(CommitType::fromString('Fix'))->toBe(CommitType::Fix)
            ->and(CommitType::fromString('unknown'))->toBe(CommitType::Other);
    });

    it('can be created from string value', function (): void {
        $type = CommitType::from('feat');

        expect($type)->toBe(CommitType::Feat);
    });

    it('throws exception for invalid value', function (): void {
        CommitType::from('invalid');
    })->throws(ValueError::class);

    it('returns null for tryFrom with invalid value', function (): void {
        $type = CommitType::tryFrom('invalid');

        expect($type)->toBeNull();
    });

    it('is a backed enum', function (): void {
        $reflection = new ReflectionEnum(CommitType::class);

        expect($reflection->isBacked())->toBeTrue()
            ->and($reflection->getBackingType()->getName())->toBe('string');
    });
});
