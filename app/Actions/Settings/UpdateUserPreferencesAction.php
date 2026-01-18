<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Contracts\Action;
use App\Models\User;

/**
 * Merges new preferences into user's preferences JSON.
 */
final class UpdateUserPreferencesAction implements Action
{
    /**
     * @param  array<string, mixed>  $newPreferences
     */
    public function __construct(
        private readonly User $user,
        private readonly array $newPreferences,
    ) {}

    public function execute(): bool
    {
        /** @var array<string, mixed> $currentPreferences */
        $currentPreferences = is_array($this->user->preferences) ? $this->user->preferences : [];
        $mergedPreferences = array_replace_recursive($currentPreferences, $this->newPreferences);

        $this->user->update([
            'preferences' => $mergedPreferences,
        ]);

        return true;
    }
}
