<?php

declare(strict_types=1);

namespace App\Models;

use App\Constants\CommitType;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property \Carbon\CarbonImmutable $committed_at
 * @property \App\Constants\CommitType $commit_type
 */
class Commit extends Model
{
    /** @use HasFactory<\Database\Factories\CommitFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'repository_id',
        'user_id',
        'sha',
        'message',
        'author_name',
        'author_email',
        'committed_at',
        'additions',
        'deletions',
        'files_changed',
        'files',
        'commit_type',
        'scope',
        'impact_score',
        'external_refs',
        'is_merge',
        'url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'committed_at' => 'immutable_datetime',
            'additions' => 'integer',
            'deletions' => 'integer',
            'files_changed' => 'integer',
            'files' => 'array',
            'commit_type' => CommitType::class,
            'impact_score' => 'decimal:2',
            'external_refs' => 'array',
            'is_merge' => 'boolean',
            'created_at' => 'immutable_datetime',
            'updated_at' => 'immutable_datetime',
        ];
    }

    /**
     * Prepare a date for array / JSON serialization.
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\TH:i:s.u\Z');
    }

    /**
     * Get the repository that owns the commit.
     *
     * @return BelongsTo<Repository, $this>
     */
    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }

    /**
     * Get the user that owns the commit.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to commits by type.
     *
     * @param  Builder<Commit>  $query
     *
     * @return Builder<Commit>
     */
    public function scopeOfType(Builder $query, CommitType $type): Builder
    {
        return $query->where('commit_type', $type->value);
    }

    /**
     * Scope to merge commits.
     *
     * @param  Builder<Commit>  $query
     *
     * @return Builder<Commit>
     */
    public function scopeMerge(Builder $query): Builder
    {
        return $query->where('is_merge', true);
    }

    /**
     * Scope to non-merge commits.
     *
     * @param  Builder<Commit>  $query
     *
     * @return Builder<Commit>
     */
    public function scopeNonMerge(Builder $query): Builder
    {
        return $query->where('is_merge', false);
    }

    /**
     * Scope to commits on a specific date.
     *
     * @param  Builder<Commit>  $query
     *
     * @return Builder<Commit>
     */
    public function scopeOnDate(Builder $query, Carbon $date): Builder
    {
        return $query->whereDate('committed_at', $date);
    }

    /**
     * Scope to commits within a date range.
     *
     * @param  Builder<Commit>  $query
     *
     * @return Builder<Commit>
     */
    public function scopeBetweenDates(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('committed_at', [$start, $end]);
    }

    /**
     * Scope to commits with high impact (score >= threshold).
     *
     * @param  Builder<Commit>  $query
     *
     * @return Builder<Commit>
     */
    public function scopeHighImpact(Builder $query, float $threshold = 5.0): Builder
    {
        return $query->where('impact_score', '>=', $threshold);
    }

    /**
     * Scope to order by most recent committed_at.
     *
     * @param  Builder<Commit>  $query
     *
     * @return Builder<Commit>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('committed_at');
    }

    /**
     * Get the short SHA (first 7 characters).
     */
    public function getShortShaAttribute(): string
    {
        return substr($this->sha, 0, 7);
    }

    /**
     * Get the total lines changed (additions + deletions).
     */
    public function getTotalLinesChangedAttribute(): int
    {
        return $this->additions + $this->deletions;
    }

    /**
     * Get the commit title (first line of message).
     */
    public function getTitleAttribute(): string
    {
        $lines = explode("\n", $this->message);

        return trim($lines[0]);
    }

    /**
     * Get the commit body (message without first line).
     */
    public function getBodyAttribute(): ?string
    {
        $lines = explode("\n", $this->message, 2);

        if (count($lines) < 2) {
            return null;
        }

        return trim($lines[1]) ?: null;
    }

    /**
     * Check if the commit has external references (issues, PRs, etc.).
     */
    public function hasExternalRefs(): bool
    {
        return ! empty($this->external_refs);
    }

    /**
     * Get the GitHub URL for this commit.
     */
    public function getGitHubUrlAttribute(): ?string
    {
        if ($this->url) {
            return $this->url;
        }

        if ($this->repository) {
            return "https://github.com/{$this->repository->full_name}/commit/{$this->sha}";
        }

        return null;
    }
}
