<?php

declare(strict_types=1);

use App\Constants\OAuthProvider;

describe('OAuthProvider Enum', function (): void {
    it('has GitHub as a case', function (): void {
        expect(OAuthProvider::GitHub->value)->toBe('github');
    });

    it('returns display name for GitHub', function (): void {
        expect(OAuthProvider::GitHub->displayName())->toBe('GitHub');
    });

    it('returns icon name for GitHub', function (): void {
        expect(OAuthProvider::GitHub->iconName())->toBe('Github');
    });

    it('returns scopes for GitHub', function (): void {
        $scopes = OAuthProvider::GitHub->scopes();

        expect($scopes)->toBeArray()
            ->and($scopes)->toContain('read:user')
            ->and($scopes)->toContain('user:email')
            ->and($scopes)->toContain('repo')
            ->and($scopes)->toContain('admin:repo_hook');
    });

    it('can be created from string value', function (): void {
        $provider = OAuthProvider::from('github');

        expect($provider)->toBe(OAuthProvider::GitHub);
    });

    it('throws exception for invalid value', function (): void {
        OAuthProvider::from('invalid');
    })->throws(ValueError::class);

    it('returns null for tryFrom with invalid value', function (): void {
        $provider = OAuthProvider::tryFrom('invalid');

        expect($provider)->toBeNull();
    });

    it('is a backed enum', function (): void {
        $reflection = new ReflectionEnum(OAuthProvider::class);

        expect($reflection->isBacked())->toBeTrue()
            ->and($reflection->getBackingType()->getName())->toBe('string');
    });
});
