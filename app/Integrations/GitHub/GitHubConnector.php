<?php

declare(strict_types=1);

namespace App\Integrations\GitHub;

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AlwaysThrowOnErrors;

final class GitHubConnector extends Connector
{
    use AlwaysThrowOnErrors;

    private const USER_AGENT = 'GitPulse/1.0';

    public function __construct(
        private readonly ?string $token = null,
    ) {}

    public function resolveBaseUrl(): string
    {
        return 'https://api.github.com';
    }

    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github.v3+json',
            'User-Agent' => self::USER_AGENT,
        ];
    }

    protected function defaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }

    protected function defaultAuth(): ?TokenAuthenticator
    {
        if ($this->token === null) {
            return null;
        }

        return new TokenAuthenticator($this->token);
    }
}
