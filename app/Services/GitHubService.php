<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\GitHub\CommitData;
use App\Integrations\GitHub\GitHubConnector;
use App\Integrations\GitHub\Requests\CreateWebhookRequest;
use App\Integrations\GitHub\Requests\DeleteWebhookRequest;
use App\Integrations\GitHub\Requests\GetAuthenticatedUserRequest;
use App\Integrations\GitHub\Requests\GetCommitRequest;
use App\Integrations\GitHub\Requests\GetRepositoryRequest;
use App\Integrations\GitHub\Requests\ListUserRepositoriesRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Exceptions\Request\RequestException;

/**
 * Service for interacting with the GitHub API.
 */
final class GitHubService
{
    private GitHubConnector $connector;

    public function __construct(
        private readonly ?string $token = null,
    ) {
        $this->connector = new GitHubConnector($this->token);
    }

    /**
     * Create a service instance for a specific user.
     */
    public static function forUser(User $user): self
    {
        return new self($user->github_token);
    }

    /**
     * Fetch detailed commit information from the API.
     *
     * @return CommitData|null Returns null if the commit couldn't be fetched
     */
    public function getCommit(string $owner, string $repo, string $sha): ?CommitData
    {
        try {
            $response = $this->connector->send(
                new GetCommitRequest($owner, $repo, $sha),
            );

            return CommitData::fromApi($response->json());
        } catch (FatalRequestException|RequestException $e) {
            Log::warning('Failed to fetch commit from GitHub API', [
                'owner' => $owner,
                'repo' => $repo,
                'sha' => $sha,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch repository information from the API.
     *
     * @return array<string, mixed>|null
     */
    public function getRepository(string $owner, string $repo): ?array
    {
        try {
            $response = $this->connector->send(
                new GetRepositoryRequest($owner, $repo),
            );

            return $response->json();
        } catch (FatalRequestException|RequestException $e) {
            Log::error('Failed to fetch repository', [
                'error' => $e->getMessage(),
                'owner' => $owner,
                'repo' => $repo,
            ]);

            return null;
        }
    }

    /**
     * List repositories for the authenticated user.
     *
     * @return array<array<string, mixed>>
     */
    public function listUserRepositories(int $perPage = 100, int $page = 1): array
    {
        try {
            $response = $this->connector->send(
                new ListUserRepositoriesRequest($perPage, $page),
            );

            return $response->json() ?? [];
        } catch (FatalRequestException|RequestException $e) {
            Log::error('Failed to list user repositories', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create a webhook for a repository.
     *
     * @return array{id: string, secret: string}|null
     */
    public function createWebhook(string $owner, string $repo, string $webhookUrl): ?array
    {
        $secret = bin2hex(random_bytes(32));

        try {
            $response = $this->connector->send(
                new CreateWebhookRequest($owner, $repo, $webhookUrl, $secret),
            );

            $data = $response->json();

            return [
                'id' => (string) $data['id'],
                'secret' => $secret,
            ];
        } catch (FatalRequestException|RequestException $e) {
            Log::error('GitHub API webhook creation failed', [
                'error' => $e->getMessage(),
                'owner' => $owner,
                'repo' => $repo,
            ]);

            return null;
        }
    }

    /**
     * Delete a webhook from a repository.
     */
    public function deleteWebhook(string $owner, string $repo, string $hookId): bool
    {
        try {
            $response = $this->connector->send(
                new DeleteWebhookRequest($owner, $repo, $hookId),
            );

            return $response->successful() || $response->status() === 404;
        } catch (RequestException $e) {
            if ($e->getResponse()->status() === 404) {
                return true;
            }

            Log::error('Failed to delete webhook', [
                'error' => $e->getMessage(),
                'owner' => $owner,
                'repo' => $repo,
                'hook_id' => $hookId,
            ]);

            return false;
        } catch (FatalRequestException $e) {
            Log::error('Failed to delete webhook', [
                'error' => $e->getMessage(),
                'owner' => $owner,
                'repo' => $repo,
                'hook_id' => $hookId,
            ]);

            return false;
        }
    }

    /**
     * Get the authenticated user's information.
     *
     * @return array<string, mixed>|null
     */
    public function getAuthenticatedUser(): ?array
    {
        try {
            $response = $this->connector->send(
                new GetAuthenticatedUserRequest,
            );

            return $response->json();
        } catch (FatalRequestException|RequestException $e) {
            Log::error('Failed to get authenticated user', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if the current token has access to a repository.
     */
    public function hasRepositoryAccess(string $owner, string $repo): bool
    {
        try {
            $response = $this->connector->send(
                new GetRepositoryRequest($owner, $repo),
            );

            return $response->successful();
        } catch (FatalRequestException|RequestException) {
            return false;
        }
    }
}
