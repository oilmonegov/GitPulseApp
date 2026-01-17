<?php

declare(strict_types=1);

namespace App\DTOs\Commits;

use App\Constants\CommitType;

/**
 * Data Transfer Object for parsed commit message data.
 *
 * Contains the extracted type, scope, description, and external references
 * from a commit message using conventional commit format or NLP-based inference.
 */
final readonly class ParsedCommitData
{
    /**
     * @param  array<array{type: string, id: string}>  $externalRefs
     */
    public function __construct(
        public CommitType $type,
        public ?string $scope,
        public string $description,
        public array $externalRefs,
        public bool $isBreakingChange,
        public bool $isConventional,
        public bool $isMerge,
    ) {}

    /**
     * Create an instance for a conventional commit.
     *
     * @param  array<array{type: string, id: string}>  $externalRefs
     */
    public static function conventional(
        CommitType $type,
        ?string $scope,
        string $description,
        array $externalRefs = [],
        bool $isBreakingChange = false,
    ): self {
        return new self(
            type: $type,
            scope: $scope,
            description: $description,
            externalRefs: $externalRefs,
            isBreakingChange: $isBreakingChange,
            isConventional: true,
            isMerge: false,
        );
    }

    /**
     * Create an instance for a non-conventional commit (NLP-inferred).
     *
     * @param  array<array{type: string, id: string}>  $externalRefs
     */
    public static function inferred(
        CommitType $type,
        string $description,
        array $externalRefs = [],
        bool $isMerge = false,
    ): self {
        return new self(
            type: $type,
            scope: null,
            description: $description,
            externalRefs: $externalRefs,
            isBreakingChange: false,
            isConventional: false,
            isMerge: $isMerge,
        );
    }

    /**
     * Create an instance for a merge commit.
     *
     * @param  array<array{type: string, id: string}>  $externalRefs
     */
    public static function merge(
        string $description,
        array $externalRefs = [],
    ): self {
        return new self(
            type: CommitType::Other,
            scope: null,
            description: $description,
            externalRefs: $externalRefs,
            isBreakingChange: false,
            isConventional: false,
            isMerge: true,
        );
    }

    /**
     * Convert to array for model storage.
     *
     * @return array{
     *     commit_type: CommitType,
     *     scope: string|null,
     *     external_refs: array<array{type: string, id: string}>|null,
     *     is_merge: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'commit_type' => $this->type,
            'scope' => $this->scope,
            'external_refs' => $this->externalRefs ?: null,
            'is_merge' => $this->isMerge,
        ];
    }

    /**
     * Check if the commit has any external references.
     */
    public function hasExternalRefs(): bool
    {
        return count($this->externalRefs) > 0;
    }

    /**
     * Get external references by type.
     *
     * @return array<array{type: string, id: string}>
     */
    public function getRefsByType(string $type): array
    {
        return array_filter(
            $this->externalRefs,
            fn (array $ref) => $ref['type'] === $type,
        );
    }
}
