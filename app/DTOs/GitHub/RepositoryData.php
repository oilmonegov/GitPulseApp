<?php

declare(strict_types=1);

namespace App\DTOs\GitHub;

/**
 * Data Transfer Object for GitHub repository information from webhooks.
 */
final readonly class RepositoryData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $fullName,
        public ?string $description,
        public string $defaultBranch,
        public ?string $language,
        public bool $isPrivate,
        public string $htmlUrl,
    ) {}

    /**
     * Create from webhook payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromWebhook(array $data): self
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            name: $data['name'] ?? '',
            fullName: $data['full_name'] ?? '',
            description: $data['description'] ?? null,
            defaultBranch: $data['default_branch'] ?? 'main',
            language: $data['language'] ?? null,
            isPrivate: $data['private'] ?? false,
            htmlUrl: $data['html_url'] ?? '',
        );
    }

    /**
     * Convert to array for model creation/update.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'github_id' => $this->id,
            'name' => $this->name,
            'full_name' => $this->fullName,
            'description' => $this->description,
            'default_branch' => $this->defaultBranch,
            'language' => $this->language,
            'is_private' => $this->isPrivate,
        ];
    }
}
