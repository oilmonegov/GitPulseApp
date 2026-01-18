<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Contracts\Action;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Exports user data in the requested format.
 */
final class ExportUserDataAction implements Action
{
    /**
     * @param  'json'|'csv'  $format
     */
    public function __construct(
        private readonly User $user,
        private readonly string $format = 'json',
    ) {}

    /**
     * @return array{filename: string, content: string, mime_type: string}
     */
    public function execute(): array
    {
        $data = $this->gatherUserData();

        return match ($this->format) {
            'csv' => $this->exportAsCsv($data),
            default => $this->exportAsJson($data),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherUserData(): array
    {
        $commits = $this->user->commits()
            ->with('repository:id,name,full_name')
            ->orderBy('committed_at', 'desc')
            ->get();

        return [
            'user' => [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'github_username' => $this->user->github_username,
                'created_at' => $this->user->created_at?->toIso8601String(),
            ],
            'statistics' => [
                'total_commits' => $commits->count(),
                'total_repositories' => $this->user->repositories()->count(),
                'total_additions' => $commits->sum('additions'),
                'total_deletions' => $commits->sum('deletions'),
                'average_impact_score' => round($commits->avg('impact_score') ?? 0, 2),
            ],
            'commits' => $commits->map(fn (\App\Models\Commit $commit): array => [
                'sha' => $commit->sha,
                'message' => $commit->message,
                'repository' => $commit->repository?->full_name,
                'additions' => $commit->additions,
                'deletions' => $commit->deletions,
                'impact_score' => $commit->impact_score,
                'commit_type' => $commit->commit_type->value,
                'committed_at' => $commit->committed_at->toIso8601String(),
            ])->toArray(),
            'exported_at' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array{filename: string, content: string, mime_type: string}
     */
    private function exportAsJson(array $data): array
    {
        $filename = sprintf('gitpulse-export-%s.json', Carbon::now()->format('Y-m-d'));

        return [
            'filename' => $filename,
            'content' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            'mime_type' => 'application/json',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @return array{filename: string, content: string, mime_type: string}
     */
    private function exportAsCsv(array $data): array
    {
        $filename = sprintf('gitpulse-commits-%s.csv', Carbon::now()->format('Y-m-d'));

        $lines = [];
        $lines[] = 'sha,message,repository,additions,deletions,impact_score,category,committed_at';

        foreach ($data['commits'] as $commit) {
            $lines[] = sprintf(
                '"%s","%s","%s",%d,%d,%s,"%s","%s"',
                $commit['sha'],
                str_replace('"', '""', $commit['message'] ?? ''),
                $commit['repository'] ?? '',
                $commit['additions'] ?? 0,
                $commit['deletions'] ?? 0,
                $commit['impact_score'] ?? 0,
                $commit['category'] ?? '',
                $commit['committed_at'] ?? '',
            );
        }

        return [
            'filename' => $filename,
            'content' => implode("\n", $lines),
            'mime_type' => 'text/csv',
        ];
    }
}
