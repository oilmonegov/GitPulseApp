<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    /** @use HasFactory<\Database\Factories\RepositoryFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'github_id',
        'name',
        'full_name',
        'description',
        'default_branch',
        'language',
        'webhook_id',
        'webhook_secret',
        'is_active',
        'is_private',
        'last_sync_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'webhook_secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_private' => 'boolean',
            'last_sync_at' => 'immutable_datetime',
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
     * Get the user that owns the repository.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the commits for the repository.
     *
     * @return HasMany<Commit, $this>
     */
    public function commits(): HasMany
    {
        return $this->hasMany(Commit::class);
    }

    /**
     * Scope to only active repositories.
     *
     * @param  Builder<Repository>  $query
     *
     * @return Builder<Repository>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to only public repositories.
     *
     * @param  Builder<Repository>  $query
     *
     * @return Builder<Repository>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_private', false);
    }

    /**
     * Scope to only private repositories.
     *
     * @param  Builder<Repository>  $query
     *
     * @return Builder<Repository>
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_private', true);
    }

    /**
     * Scope to repositories with webhooks configured.
     *
     * @param  Builder<Repository>  $query
     *
     * @return Builder<Repository>
     */
    public function scopeWithWebhook(Builder $query): Builder
    {
        return $query->whereNotNull('webhook_id');
    }

    /**
     * Check if the repository has a webhook configured.
     */
    public function hasWebhook(): bool
    {
        return ! is_null($this->webhook_id);
    }

    /**
     * Get the repository's GitHub URL.
     */
    public function getGitHubUrlAttribute(): string
    {
        return "https://github.com/{$this->full_name}";
    }

    /**
     * Get the total number of commits.
     */
    public function getTotalCommitsAttribute(): int
    {
        return $this->commits()->count();
    }
}
