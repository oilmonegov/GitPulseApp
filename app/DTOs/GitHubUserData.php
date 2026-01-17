<?php

declare(strict_types=1);

namespace App\DTOs;

use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * Data Transfer Object for GitHub user information.
 */
final readonly class GitHubUserData
{
    public function __construct(
        public string $id,
        public string $username,
        public ?string $name,
        public ?string $email,
        public ?string $avatar,
        public ?string $token,
    ) {}

    /**
     * Create from a Socialite user.
     */
    public static function fromSocialite(SocialiteUser $user): self
    {
        return new self(
            id: $user->getId(),
            username: $user->getNickname() ?? '',
            name: $user->getName(),
            email: $user->getEmail(),
            avatar: $user->getAvatar(),
            token: $user->token,
        );
    }

    /**
     * Get the display name (name or username).
     */
    public function displayName(): string
    {
        return $this->name ?? $this->username;
    }

    /**
     * Convert to array for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'github_id' => $this->id,
            'github_username' => $this->username,
            'github_token' => $this->token,
            'avatar_url' => $this->avatar,
        ];
    }
}
